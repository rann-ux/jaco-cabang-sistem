<?php

return [
    'base_url' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM'),
    'issuer' => env('KEYCLOAK_ISSUER'),
    'jwks_uri' => env('KEYCLOAK_JWKS_URI'),
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
];