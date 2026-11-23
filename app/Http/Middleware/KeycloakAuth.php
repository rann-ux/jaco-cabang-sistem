<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class KeycloakAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token autentikasi tidak ditemukan.',
            ], 401);
        }

        try {
            $issuer = config('keycloak.issuer');
            $jwksUri = config('keycloak.jwks_uri');
            $clientId = config('keycloak.client_id');

            // Ambil public key dari Keycloak.
            $jwks = Cache::remember('keycloak_jwks', 3600, function () use ($jwksUri) {
                $response = Http::timeout(10)->get($jwksUri);
                $response->throw();

                return $response->json();
            });

            // Ambil kid dari header JWT.
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new \UnexpectedValueException('Format JWT tidak valid.');
            }

            $header = json_decode(
                JWT::urlsafeB64Decode($parts[0])
            );

            if (($header->alg ?? null) !== 'RS256') {
                throw new \UnexpectedValueException('Algoritma JWT tidak valid.');
            }

            $kid = $header->kid ?? null;

            if (!$kid) {
                throw new \UnexpectedValueException('JWT kid tidak ditemukan.');
            }

            // Cari public key berdasarkan kid.
            $keySet = JWK::parseKeySet($jwks, 'RS256');
            $key = $keySet[$kid] ?? null;

            if (!$key) {
                Cache::forget('keycloak_jwks');

                throw new \UnexpectedValueException('Public key tidak ditemukan.');
            }

            // Verifikasi signature dan masa berlaku token.
            JWT::$leeway = 60;
            $payload = JWT::decode($token, $key);

            // Validasi issuer.
            if (($payload->iss ?? null) !== $issuer) {
                throw new \UnexpectedValueException('Issuer tidak valid.');
            }

            // Validasi audience.
            $audiences = (array) ($payload->aud ?? []);

            if (!in_array($clientId, $audiences, true)) {
                throw new \UnexpectedValueException('Audience tidak valid.');
            }

            // Ambil realm roles.
            $roles = $payload->realm_access->roles ?? [];

            $request->attributes->set('keycloak_user', $payload);
            $request->attributes->set('keycloak_roles', $roles);

            return $next($request);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }
    }
}
