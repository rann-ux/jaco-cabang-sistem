<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel produks
        DB::statement("ALTER TABLE produks ADD CONSTRAINT chk_produk_harga_nonneg CHECK (harga >= 0)");
        DB::statement("ALTER TABLE produks ADD CONSTRAINT chk_produk_masa_garansi_nonneg CHECK (masa_garansi >= 0)");

        // Tabel promos
        DB::statement("ALTER TABLE promos ADD CONSTRAINT chk_promo_nilai_positive CHECK (nilai > 0)");
        DB::statement("ALTER TABLE promos ADD CONSTRAINT chk_promo_persen_max CHECK (jenis_potongan != 'persen' OR nilai <= 100)");
        DB::statement("ALTER TABLE promos ADD CONSTRAINT chk_promo_tanggal CHECK (tgl_selesai >= tgl_mulai)");

        // Tabel stoks
        DB::statement("ALTER TABLE stoks ADD CONSTRAINT chk_stok_jumlah_nonneg CHECK (jumlah >= 0)");
        DB::statement("ALTER TABLE stoks ADD CONSTRAINT chk_stok_minimum_nonneg CHECK (batas_minimum >= 0)");

        // Tabel mutasi_stoks
        DB::statement("ALTER TABLE mutasi_stoks ADD CONSTRAINT chk_mutasi_jumlah_positive CHECK (jumlah > 0)");

        // Tabel penerimaan_barangs
        DB::statement("ALTER TABLE penerimaan_barangs ADD CONSTRAINT chk_penerimaan_jumlah CHECK (jumlah > 0)");

        // Tabel detail_fakturs
        DB::statement("ALTER TABLE detail_fakturs ADD CONSTRAINT chk_detail_jumlah_positive CHECK (jumlah > 0)");
        DB::statement("ALTER TABLE detail_fakturs ADD CONSTRAINT chk_detail_harga_nonneg CHECK (harga_satuan >= 0)");
        DB::statement("ALTER TABLE detail_fakturs ADD CONSTRAINT chk_detail_subtotal CHECK (subtotal = jumlah * harga_satuan)");

        // Tabel kas_transaksis
        DB::statement("ALTER TABLE kas_transaksis ADD CONSTRAINT chk_kas_jumlah_positive CHECK (jumlah > 0)");
    }

    public function down(): void
    {
        // Hapus CHECK pada tabel kas_transaksis
        DB::statement("ALTER TABLE kas_transaksis DROP CHECK chk_kas_jumlah_positive");

        // Hapus CHECK pada tabel detail_fakturs
        DB::statement("ALTER TABLE detail_fakturs DROP CHECK chk_detail_subtotal");
        DB::statement("ALTER TABLE detail_fakturs DROP CHECK chk_detail_harga_nonneg");
        DB::statement("ALTER TABLE detail_fakturs DROP CHECK chk_detail_jumlah_positive");

        // Hapus CHECK pada tabel penerimaan_barangs
        DB::statement("ALTER TABLE penerimaan_barangs DROP CHECK chk_penerimaan_jumlah");

        // Hapus CHECK pada tabel mutasi_stoks
        DB::statement("ALTER TABLE mutasi_stoks DROP CHECK chk_mutasi_jumlah_positive");

        // Hapus CHECK pada tabel stoks
        DB::statement("ALTER TABLE stoks DROP CHECK chk_stok_minimum_nonneg");
        DB::statement("ALTER TABLE stoks DROP CHECK chk_stok_jumlah_nonneg");

        // Hapus CHECK pada tabel promos
        DB::statement("ALTER TABLE promos DROP CHECK chk_promo_tanggal");
        DB::statement("ALTER TABLE promos DROP CHECK chk_promo_persen_max");
        DB::statement("ALTER TABLE promos DROP CHECK chk_promo_nilai_positive");

        // Hapus CHECK pada tabel produks
        DB::statement("ALTER TABLE produks DROP CHECK chk_produk_masa_garansi_nonneg");
        DB::statement("ALTER TABLE produks DROP CHECK chk_produk_harga_nonneg");
    }

};