<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class IntegrasiPusatService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.central_mock.url'),
            '/'
        );
    }

    public function kirimLaporanHarian(array $data): array
    {
        return $this->kirim('/api/v1/laporan-harian', $data);
    }

    public function kirimTransaksi(array $data): array
    {
        return $this->kirim('/api/v1/transaksi', $data);
    }

    public function kirimPenerimaanBarang(array $data): array
    {
        return $this->kirim('/api/v1/penerimaan-barang', $data);
    }

    protected function kirim(string $endpoint, array $data): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post($this->baseUrl . $endpoint, $data);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Sistem pusat menolak data. HTTP ' . $response->status()
                );
            }

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke sistem pusat.',
                0,
                $e
            );
        }
    }

    public function kirimKas(array $data): array
    {
        return $this->kirim('/api/v1/kas', $data);
    }

}