<?php

namespace Tests\Feature;

use App\Http\Middleware\KeycloakAuth;
use Closure;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MasterDataTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Membuat tabel sementara khusus pengujian SQLite.
        Schema::create('produks', function (Blueprint $table) {
            $table->increments('id_produk');
            $table->string('kode_produk', 30)->unique();
            $table->string('nama_produk', 150);
            $table->string('kategori', 50);
            $table->decimal('harga', 15, 2);
            $table->integer('masa_garansi')->default(0);
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->increments('id_promo');
            $table->string('kode_promo', 30)->unique();
            $table->string('deskripsi', 255);
            $table->string('jenis_potongan', 20);
            $table->decimal('nilai', 15, 2);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('status', 20)->default('aktif');
        });

        Schema::create('cabangs', function (Blueprint $table) {
            $table->increments('id_cabang');
            $table->string('nama_cabang', 100);
            $table->string('jenis', 20);
            $table->text('alamat');
        });

        Schema::create('pelanggans', function (Blueprint $table) {
            $table->increments('id_pelanggan');
            $table->string('nama', 100);
            $table->text('alamat');
            $table->string('no_hp', 20);
        });

        Schema::create('karyawans', function (Blueprint $table) {
            $table->increments('id_karyawan');
            $table->string('keycloak_id', 36)->nullable()->unique();
            $table->unsignedInteger('id_cabang');
            $table->string('nama', 100);
            $table->string('peran', 30);
            $table->string('status', 20)->default('aktif');
        });

        Schema::create('stoks', function (Blueprint $table) {
            $table->increments('id_stok');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_produk');
            $table->integer('jumlah')->default(0);
            $table->integer('batas_minimum')->default(0);
            $table->dateTime('tgl_update')->useCurrent();

            $table->unique(['id_cabang', 'id_produk']);
        });

        Schema::create('penerimaan_barangs', function (Blueprint $table) {
            $table->increments('id_penerimaan');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_produk');
            $table->unsignedInteger('id_karyawan');
            $table->integer('jumlah');
            $table->dateTime('tanggal')->useCurrent();
            $table->string('no_dokumen_pusat', 30);

            $table->unique([
                'no_dokumen_pusat',
                'id_cabang',
                'id_produk',
            ]);
        });

        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->increments('id_mutasi');
            $table->unsignedInteger('id_stok');
            $table->unsignedInteger('id_karyawan');
            $table->string('jenis', 20);
            $table->integer('jumlah');
            $table->dateTime('tanggal')->useCurrent();
            $table->string('referensi_dokumen', 50)->nullable();
        });


        Schema::create('fakturs', function (Blueprint $table) {
            $table->increments('id_faktur');
            $table->string('no_faktur', 30)->unique();
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_pelanggan');
            $table->unsignedInteger('id_karyawan');
            $table->unsignedInteger('id_promo')->nullable();
            $table->dateTime('tanggal')->useCurrent();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('skema_pembayaran', 20)
                ->default('sekali_bayar');
            $table->string('status', 20)->default('belum_dibayar');
            $table->index(['id_cabang', 'tanggal']);
        });

        Schema::create('detail_fakturs', function (Blueprint $table) {
            $table->increments('id_detail');
            $table->unsignedInteger('id_faktur');
            $table->unsignedInteger('id_produk');
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('po_pengantarans', function (Blueprint $table) {
            $table->increments('id_po');
            $table->unsignedInteger('id_faktur')->unique();
            $table->date('tanggal');
            $table->string('status', 20)->default('baru');
        });

        Schema::create('pembayarans', function (Blueprint $table) {
            $table->increments('id_bayar');
            $table->unsignedInteger('id_faktur');
            $table->string('metode', 20);
            $table->decimal('jumlah', 15, 2);
            $table->dateTime('tanggal')->useCurrent();
            $table->boolean('status_verifikasi_sales')->default(false);
            $table->boolean('status_cek_pusat')->default(false);
            $table->dateTime('tgl_cek_pusat')->nullable();
        });

        Schema::create('kas_transaksis', function (Blueprint $table) {
            $table->increments('id_kas');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_karyawan');
            $table->unsignedInteger('id_faktur')->nullable();
            $table->dateTime('tanggal')->useCurrent();
            $table->string('jenis', 20);
            $table->decimal('jumlah', 15, 2);
            $table->string('keterangan', 255)->nullable();
            $table->index(['id_cabang', 'tanggal']);
        });

        Schema::create('laporan_harians', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_karyawan');
            $table->date('tanggal');
            $table->string('jenis_laporan');
            $table->string('status_kirim', 20)->default('belum_kirim');
            $table->dateTime('tgl_kirim')->nullable();

            $table->unique(
                ['id_cabang', 'tanggal', 'jenis_laporan'],
                'laporan_harians_id_cabang_tanggal_jenis_laporan_unique'
            );
        });


        $this->setKeycloakRoles(['admin_pusat']);
    }

    private function setKeycloakRoles(
        array $roles,
        ?int $idKaryawan = null,
        ?int $idCabang = null
    ): void {
        $this->app->instance(
            KeycloakAuth::class,
            new class($roles, $idKaryawan, $idCabang) extends KeycloakAuth {
                public function __construct(
                    private array $roles,
                    private ?int $idKaryawan,
                    private ?int $idCabang
                ) {
                }

                public function handle(
                    Request $request,
                    Closure $next
                ) {
                    $request->attributes->set(
                        'keycloak_roles',
                        $this->roles
                    );

                    if ($this->idKaryawan !== null) {
                        $request->attributes->set(
                            'id_karyawan',
                            $this->idKaryawan
                        );
                    }

                    if ($this->idCabang !== null) {
                        $request->attributes->set(
                            'id_cabang',
                            $this->idCabang
                        );
                    }

                    return $next($request);
                }
            }
        );
    }

    public function test_admin_pusat_can_sync_products(): void
    {
        $response = $this->postJson('/api/v1/sinkronisasi/produk', [
            'data' => [
                [
                    'kode_produk' => 'PRD001',
                    'nama_produk' => 'Laptop',
                    'kategori' => 'Elektronik',
                    'harga' => 7500000,
                    'masa_garansi' => 12,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseHas('produks', [
            'kode_produk' => 'PRD001',
            'nama_produk' => 'Laptop',
        ]);
    }

    public function test_cash_transaction_remains_saved_when_central_integration_fails(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        Http::fake([
            '*' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'setoran',
            'jumlah' => 500000,
            'tanggal' => '2026-11-27',
            'keterangan' => 'Setoran kas harian',
        ]);

        $response->assertCreated()
            ->assertJsonPath('sinkronisasi_pusat', 'gagal')
            ->assertJsonPath('respons_pusat', null);

        $this->assertDatabaseHas('kas_transaksis', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'setoran',
            'jumlah' => 500000,
        ]);
    }

    public function test_admin_pusat_can_sync_promos(): void
    {
        $response = $this->postJson('/api/v1/sinkronisasi/promo', [
            'data' => [
                [
                    'kode_promo' => 'PROMO001',
                    'deskripsi' => 'Diskon akhir tahun',
                    'jenis_potongan' => 'persen',
                    'nilai' => 15,
                    'tgl_mulai' => '2026-12-01',
                    'tgl_selesai' => '2026-12-31',
                    'status' => 'aktif',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseHas('promos', [
            'kode_promo' => 'PROMO001',
            'nilai' => 15,
        ]);
    }

    public function test_product_with_negative_price_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/sinkronisasi/produk', [
            'data' => [
                [
                    'kode_produk' => 'PRD002',
                    'nama_produk' => 'Produk Uji',
                    'kategori' => 'Elektronik',
                    'harga' => -1000,
                    'masa_garansi' => 12,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('data.0.harga');

        $this->assertDatabaseMissing('produks', [
            'kode_produk' => 'PRD002',
        ]);
    }

    public function test_promo_percentage_above_100_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/sinkronisasi/promo', [
            'data' => [
                [
                    'kode_promo' => 'PROMO002',
                    'deskripsi' => 'Promo tidak valid',
                    'jenis_potongan' => 'persen',
                    'nilai' => 150,
                    'tgl_mulai' => '2026-12-01',
                    'tgl_selesai' => '2026-12-31',
                    'status' => 'aktif',
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('data.0.nilai');

        $this->assertDatabaseMissing('promos', [
            'kode_promo' => 'PROMO002',
        ]);
    }

    public function test_sales_role_cannot_sync_products(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->postJson('/api/v1/sinkronisasi/produk', [
            'data' => [
                [
                    'kode_produk' => 'PRD003',
                    'nama_produk' => 'Produk Uji',
                    'kategori' => 'Elektronik',
                    'harga' => 1000000,
                    'masa_garansi' => 12,
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_admin_pusat_can_manage_cabang(): void
    {
        // Menambahkan cabang
        $response = $this->postJson('/api/v1/cabang', [
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama_cabang', 'Cabang Jambi');

        $id = $response->json('data.id_cabang');

        // Melihat detail cabang
        $this->getJson("/api/v1/cabang/{$id}")
            ->assertOk()
            ->assertJsonPath('data.nama_cabang', 'Cabang Jambi');

        // Mengubah data cabang
        $this->putJson("/api/v1/cabang/{$id}", [
            'nama_cabang' => 'Cabang Jambi Baru',
        ])->assertOk()
            ->assertJsonPath('data.nama_cabang', 'Cabang Jambi Baru');

        // Menghapus cabang
        $this->deleteJson("/api/v1/cabang/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('cabangs', [
            'id_cabang' => $id,
        ]);
    }

    public function test_admin_cabang_can_manage_pelanggan(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        // Menambahkan pelanggan
        $response = $this->postJson('/api/v1/pelanggan', [
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'Pelanggan Uji');

        $id = $response->json('data.id_pelanggan');

        // Melihat detail pelanggan
        $this->getJson("/api/v1/pelanggan/{$id}")
            ->assertOk()
            ->assertJsonPath('data.nama', 'Pelanggan Uji');

        // Mengubah data pelanggan
        $this->putJson("/api/v1/pelanggan/{$id}", [
            'nama' => 'Pelanggan Baru',
        ])->assertOk()
            ->assertJsonPath('data.nama', 'Pelanggan Baru');

        // Menghapus pelanggan
        $this->deleteJson("/api/v1/pelanggan/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('pelanggans', [
            'id_pelanggan' => $id,
        ]);
    }

    public function test_sales_role_can_read_master_data(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $this->getJson('/api/v1/cabang')
            ->assertOk();

        $this->getJson('/api/v1/pelanggan')
            ->assertOk();
    }

    public function test_role_cannot_manage_master_data_without_permission(): void
    {
        // Admin cabang tidak boleh mengelola data cabang.
        $this->setKeycloakRoles(['admin_cabang']);

        $this->postJson('/api/v1/cabang', [
            'nama_cabang' => 'Cabang Uji',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ])->assertForbidden();

        // Admin pusat tidak boleh mengelola data pelanggan.
        $this->setKeycloakRoles(['admin_pusat']);

        $this->postJson('/api/v1/pelanggan', [
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ])->assertForbidden();
    }

    public function test_sync_existing_product_updates_data(): void
    {
        $payload = [
            'data' => [
                [
                    'kode_produk' => 'PRD004',
                    'nama_produk' => 'Laptop Lama',
                    'kategori' => 'Elektronik',
                    'harga' => 5000000,
                    'masa_garansi' => 12,
                ],
            ],
        ];

        $this->postJson('/api/v1/sinkronisasi/produk', $payload)
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $payload['data'][0]['nama_produk'] = 'Laptop Terbaru';
        $payload['data'][0]['harga'] = 7000000;

        $this->postJson('/api/v1/sinkronisasi/produk', $payload)
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('produks', [
            'kode_produk' => 'PRD004',
            'nama_produk' => 'Laptop Terbaru',
            'harga' => 7000000,
        ]);

        $this->assertDatabaseCount('produks', 1);
    }

    public function test_sync_existing_promo_updates_data(): void
    {
        $payload = [
            'data' => [
                [
                    'kode_promo' => 'PROMO004',
                    'deskripsi' => 'Promo Lama',
                    'jenis_potongan' => 'persen',
                    'nilai' => 10,
                    'tgl_mulai' => '2026-12-01',
                    'tgl_selesai' => '2026-12-31',
                    'status' => 'aktif',
                ],
            ],
        ];

        $this->postJson('/api/v1/sinkronisasi/promo', $payload)
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $payload['data'][0]['deskripsi'] = 'Promo Terbaru';
        $payload['data'][0]['nilai'] = 20;

        $this->postJson('/api/v1/sinkronisasi/promo', $payload)
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('promos', [
            'kode_promo' => 'PROMO004',
            'deskripsi' => 'Promo Terbaru',
            'nilai' => 20,
        ]);

        $this->assertDatabaseCount('promos', 1);
    }

    public function test_gudang_can_receive_goods_and_increase_stock(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        // Simulasi respons dari sistem pusat.
        Http::fake([
            '*' => Http::response([
                'message' => 'Penerimaan berhasil diterima pusat.',
            ], 201),
        ]);

        // Menyiapkan data cabang.
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        // Menyiapkan data produk.
        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD010',
            'nama_produk' => 'Laptop Uji',
            'kategori' => 'Elektronik',
            'harga' => 7500000,
            'masa_garansi' => 12,
        ], 'id_produk');

        // Menyiapkan karyawan aktif pada cabang tersebut.
        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        // Mengirim penerimaan barang.
        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 10,
            'tanggal' => '2026-11-26',
            'no_dokumen_pusat' => 'DOC-TEST-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('sinkronisasi_pusat', 'berhasil')
            ->assertJsonPath('data.jumlah', 10);

        // Memastikan stok bertambah.
        $this->assertDatabaseHas('stoks', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
        ]);

        // Memastikan penerimaan tercatat.
        $this->assertDatabaseHas('penerimaan_barangs', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'no_dokumen_pusat' => 'DOC-TEST-001',
        ]);

        // Memastikan mutasi stok masuk tercatat.
        $this->assertDatabaseHas('mutasi_stoks', [
            'jenis' => 'masuk',
            'jumlah' => 10,
            'referensi_dokumen' => 'DOC-TEST-001',
        ]);

        Http::assertSentCount(1);
    }

    public function test_gudang_can_record_stock_in_and_out(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        // Menyiapkan cabang.
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        // Menyiapkan produk.
        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD011',
            'nama_produk' => 'Monitor Uji',
            'kategori' => 'Elektronik',
            'harga' => 2000000,
            'masa_garansi' => 12,
        ], 'id_produk');

        // Menyiapkan karyawan gudang aktif.
        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        // Stok awal 10 unit.
        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        // Mutasi masuk 5 unit.
        $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'masuk',
            'jumlah' => 5,
            'referensi_dokumen' => 'MUT-TEST-001',
        ])->assertCreated();

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 15,
        ]);

        // Mutasi keluar 4 unit.
        $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 4,
            'referensi_dokumen' => 'MUT-TEST-002',
        ])->assertCreated();

        // Stok akhir harus 11 unit.
        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 11,
        ]);

        // Memastikan kedua mutasi tercatat.
        $this->assertDatabaseHas('mutasi_stoks', [
            'id_stok' => $idStok,
            'jenis' => 'masuk',
            'jumlah' => 5,
        ]);

        $this->assertDatabaseHas('mutasi_stoks', [
            'id_stok' => $idStok,
            'jenis' => 'keluar',
            'jumlah' => 4,
        ]);

        $this->assertDatabaseCount('mutasi_stoks', 2);
    }

    public function test_stock_out_is_rejected_when_stock_is_insufficient(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD012',
            'nama_produk' => 'Keyboard Uji',
            'kategori' => 'Elektronik',
            'harga' => 300000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 5,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 8,
            'referensi_dokumen' => 'MUT-TEST-003',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        // Stok tetap 5 karena transaksi ditolak.
        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 5,
        ]);

        // Tidak boleh ada catatan mutasi baru.
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_duplicate_goods_receipt_is_rejected(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        Http::fake([
            '*' => Http::response([
                'message' => 'Penerimaan berhasil diterima pusat.',
            ], 201),
        ]);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD013',
            'nama_produk' => 'Mouse Uji',
            'kategori' => 'Elektronik',
            'harga' => 150000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $payload = [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 10,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-002',
        ];

        // Penerimaan pertama berhasil.
        $this->postJson('/api/v1/penerimaan-barang', $payload)
            ->assertCreated();

        // Penerimaan kedua dengan dokumen yang sama ditolak.
        $this->postJson('/api/v1/penerimaan-barang', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('no_dokumen_pusat');

        // Stok hanya bertambah satu kali.
        $this->assertDatabaseHas('stoks', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
        ]);

        $this->assertDatabaseCount('penerimaan_barangs', 1);
        $this->assertDatabaseCount('mutasi_stoks', 1);

        Http::assertSentCount(1);
    }

    public function test_goods_receipt_is_rejected_when_employee_belongs_to_another_branch(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        // Menyiapkan dua cabang.
        $idCabangA = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangB = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Palembang',
            'jenis' => 'gudang',
            'alamat' => 'Kota Palembang',
        ], 'id_cabang');

        // Menyiapkan produk.
        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD013',
            'nama_produk' => 'Monitor Uji',
            'kategori' => 'Elektronik',
            'harga' => 2000000,
            'masa_garansi' => 12,
        ], 'id_produk');

        // Karyawan terdaftar pada Cabang A.
        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangA,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        // Karyawan Cabang A digunakan untuk penerimaan Cabang B.
        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabangB,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 5,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-004',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        // Memastikan stok tidak bertambah.
        $this->assertDatabaseCount('stoks', 0);

        // Memastikan penerimaan dan mutasi tidak tercatat.
        $this->assertDatabaseCount('penerimaan_barangs', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_goods_receipt_is_rejected_when_employee_is_inactive(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD014',
            'nama_produk' => 'Printer Uji',
            'kategori' => 'Elektronik',
            'harga' => 1500000,
            'masa_garansi' => 12,
        ], 'id_produk');

        // Karyawan berstatus tidak aktif.
        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Mantan Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'nonaktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 5,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-005',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        $this->assertDatabaseCount('stoks', 0);
        $this->assertDatabaseCount('penerimaan_barangs', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_stock_mutation_is_rejected_when_employee_belongs_to_another_branch(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabangA = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangB = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Palembang',
            'jenis' => 'gudang',
            'alamat' => 'Kota Palembang',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD015',
            'nama_produk' => 'Mouse Uji',
            'kategori' => 'Elektronik',
            'harga' => 150000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangA,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabangB,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabangB,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 3,
            'referensi_dokumen' => 'MUT-TEST-004',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 10,
        ]);

        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_stock_mutation_is_rejected_when_employee_is_inactive(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD016',
            'nama_produk' => 'Speaker Uji',
            'kategori' => 'Elektronik',
            'harga' => 500000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'nonaktif',
        ], 'id_karyawan');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 3,
            'referensi_dokumen' => 'MUT-TEST-005',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 10,
        ]);

        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_stock_mutation_is_rejected_when_stock_record_does_not_exist(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD017',
            'nama_produk' => 'Headset Uji',
            'kategori' => 'Elektronik',
            'harga' => 250000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        // Tidak membuat catatan stok untuk produk ini.

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 3,
            'referensi_dokumen' => 'MUT-TEST-006',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_produk');

        $this->assertDatabaseCount('stoks', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_sales_role_cannot_receive_goods(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => 1,
            'id_produk' => 1,
            'id_karyawan' => 1,
            'jumlah' => 5,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-006',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('penerimaan_barangs', 0);
        $this->assertDatabaseCount('stoks', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_sales_role_cannot_record_stock_mutation(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => 1,
            'id_produk' => 1,
            'id_karyawan' => 1,
            'jenis' => 'masuk',
            'jumlah' => 5,
            'referensi_dokumen' => 'MUT-TEST-007',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('mutasi_stoks', 0);
        $this->assertDatabaseCount('stoks', 0);
    }

    public function test_goods_receipt_is_saved_locally_when_central_integration_fails(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        // Simulasi sistem pusat mengembalikan HTTP 500.
        Http::fake([
            '*' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD018',
            'nama_produk' => 'Laptop Integrasi Uji',
            'kategori' => 'Elektronik',
            'harga' => 7500000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 10,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-008',
        ]);

        // Transaksi lokal tetap berhasil.
        $response->assertCreated()
            ->assertJsonPath('sinkronisasi_pusat', 'gagal')
            ->assertJsonPath('data.jumlah', 10);

        // Memastikan stok tetap bertambah.
        $this->assertDatabaseHas('stoks', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
        ]);

        // Memastikan penerimaan tetap tercatat.
        $this->assertDatabaseHas('penerimaan_barangs', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'no_dokumen_pusat' => 'DOC-TEST-008',
        ]);

        // Memastikan mutasi stok masuk tetap tercatat.
        $this->assertDatabaseHas('mutasi_stoks', [
            'jenis' => 'masuk',
            'jumlah' => 10,
            'referensi_dokumen' => 'DOC-TEST-008',
        ]);

        Http::assertSentCount(1);
    }

    public function test_goods_receipt_rejects_zero_quantity(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD019',
            'nama_produk' => 'Produk Uji',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => 0,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-009',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('penerimaan_barangs', 0);
        $this->assertDatabaseCount('stoks', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_goods_receipt_rejects_negative_quantity(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD020',
            'nama_produk' => 'Produk Uji Negatif',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/penerimaan-barang', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jumlah' => -5,
            'tanggal' => '2026-11-27',
            'no_dokumen_pusat' => 'DOC-TEST-010',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('penerimaan_barangs', 0);
        $this->assertDatabaseCount('stoks', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_stock_mutation_rejects_zero_quantity(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD021',
            'nama_produk' => 'Produk Mutasi Uji',
            'kategori' => 'Elektronik',
            'harga' => 200000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 0,
            'referensi_dokumen' => 'MUT-TEST-008',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 10,
        ]);

        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_stock_mutation_rejects_negative_quantity(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'gudang',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD022',
            'nama_produk' => 'Produk Mutasi Negatif',
            'kategori' => 'Elektronik',
            'harga' => 200000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/mutasi-stok', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => -5,
            'referensi_dokumen' => 'MUT-TEST-009',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 10,
        ]);

        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_sales_can_create_invoice_and_decrease_stock(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        Http::fake();

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD100',
            'nama_produk' => 'Produk Penjualan Uji',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'skema_pembayaran' => 'sekali_bayar',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 2,
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('fakturs', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ]);

        $this->assertDatabaseHas('detail_fakturs', [
            'id_produk' => $idProduk,
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'subtotal' => 200000,
        ]);

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 8,
        ]);

        $this->assertDatabaseHas('mutasi_stoks', [
            'id_stok' => $idStok,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 2,
        ]);

        $this->assertDatabaseCount('po_pengantarans', 1);
    }

    private function buatFakturUntukTestB2(): array
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Uji B2',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji B2',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji B2',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'INV-UJI-B2',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-28 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        return [
            'id_faktur' => $idFaktur,
            'id_cabang' => $idCabang,
        ];
    }

    public function test_sales_can_view_invoice_from_own_branch(): void
    {
        $faktur = $this->buatFakturUntukTestB2();

        $this->setKeycloakRoles([
            'sales_cabang',
        ], null, $faktur['id_cabang']);

        $response = $this->getJson(
            '/api/v1/faktur/' . $faktur['id_faktur']
        );

        $response->assertOk()
            ->assertJsonPath('data.id_faktur', $faktur['id_faktur']);
    }

    public function test_sales_cannot_view_invoice_from_another_branch(): void
    {
        $faktur = $this->buatFakturUntukTestB2();

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Lain',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $this->setKeycloakRoles([
            'sales_cabang',
        ], null, $idCabangLain);

        $response = $this->getJson(
            '/api/v1/faktur/' . $faktur['id_faktur']
        );

        $response->assertForbidden();
    }

    public function test_invoice_detail_returns_404_when_invoice_does_not_exist(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Uji B2',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $this->setKeycloakRoles([
            'sales_cabang',
        ], null, $idCabang);

        $response = $this->getJson('/api/v1/faktur/99999');

        $response->assertNotFound();
    }

    public function test_invoice_is_rejected_when_stock_is_insufficient(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD101',
            'nama_produk' => 'Produk Stok Terbatas',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idStok = DB::table('stoks')->insertGetId([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 2,
            'batas_minimum' => 1,
            'tgl_update' => '2026-11-27 10:00:00',
        ], 'id_stok');

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 5,
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Stok tidak mencukupi. Stok tersedia: 2',
            ]);

        $this->assertDatabaseCount('fakturs', 0);
        $this->assertDatabaseCount('detail_fakturs', 0);
        $this->assertDatabaseCount('po_pengantarans', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);

        $this->assertDatabaseHas('stoks', [
            'id_stok' => $idStok,
            'jumlah' => 2,
        ]);
    }

    public function test_invoice_is_rejected_when_employee_belongs_to_another_branch(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Palembang',
            'jenis' => 'showroom',
            'alamat' => 'Kota Palembang',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangLain,
            'nama' => 'Sales Cabang Lain',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD102',
            'nama_produk' => 'Produk Uji',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        DB::table('stoks')->insert([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ]);

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        $this->assertDatabaseCount('fakturs', 0);
        $this->assertDatabaseCount('detail_fakturs', 0);
        $this->assertDatabaseCount('po_pengantarans', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_invoice_is_rejected_when_employee_is_inactive(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Nonaktif',
            'peran' => 'sales_cabang',
            'status' => 'nonaktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD103',
            'nama_produk' => 'Produk Uji',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        DB::table('stoks')->insert([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ]);

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('id_karyawan');

        $this->assertDatabaseCount('fakturs', 0);
        $this->assertDatabaseCount('detail_fakturs', 0);
        $this->assertDatabaseCount('po_pengantarans', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }
    
    public function test_sales_can_record_full_payment_for_single_payment_invoice(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-001',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'tunai',
                'jumlah' => 200000,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('pembayarans', [
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 200000,
            'status_verifikasi_sales' => 0,
            'status_cek_pusat' => 0,
        ]);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'status' => 'belum_dibayar',
        ]);
    }

    public function test_single_payment_invoice_rejects_partial_payment(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-002',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'tunai',
                'jumlah' => 100000,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('pembayarans', 0);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'status' => 'belum_dibayar',
        ]);
    }

    public function test_sales_can_record_partial_payment_for_installment_invoice(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-003',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'cicilan',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'transfer',
                'jumlah' => 100000,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('pembayarans', [
            'id_faktur' => $idFaktur,
            'metode' => 'transfer',
            'jumlah' => 100000,
            'status_verifikasi_sales' => 0,
            'status_cek_pusat' => 0,
        ]);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'status' => 'belum_dibayar',
        ]);
    }

    public function test_sales_can_verify_payment_and_record_cash_transaction(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-004',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 200000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => false,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/verifikasi-sales"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => 1,
        ]);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'status' => 'lunas',
        ]);

        $this->assertDatabaseHas('kas_transaksis', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'id_faktur' => $idFaktur,
            'jenis' => 'masuk',
            'jumlah' => 200000,
        ]);
    }

    public function test_partial_payment_verification_updates_invoice_status(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-005',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'cicilan',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'transfer',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => false,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/verifikasi-sales"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => 1,
        ]);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'status' => 'dibayar_sebagian',
        ]);

        $this->assertDatabaseHas('kas_transaksis', [
            'id_faktur' => $idFaktur,
            'jenis' => 'masuk',
            'jumlah' => 100000,
        ]);
    }

    public function test_payment_is_rejected_when_amount_exceeds_remaining_invoice_balance(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-006',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'cicilan',
            'status' => 'dibayar_sebagian',
        ], 'id_faktur');

        DB::table('pembayarans')->insert([
            'id_faktur' => $idFaktur,
            'metode' => 'transfer',
            'jumlah' => 150000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => true,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ]);

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'tunai',
                'jumlah' => 100000,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('pembayarans', 1);

        $this->assertDatabaseHas('fakturs', [
            'id_faktur' => $idFaktur,
            'total' => 200000,
        ]);
    }

    public function test_admin_pusat_can_check_sales_verified_payment(): void
    {
        $this->setKeycloakRoles(['admin_pusat']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-007',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 200000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => true,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/cek-pusat"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => 1,
            'status_cek_pusat' => 1,
        ]);

        $this->assertNotNull(
            DB::table('pembayarans')
                ->where('id_bayar', $idPembayaran)
                ->value('tgl_cek_pusat')
        );
    }

    public function test_admin_pusat_cannot_check_payment_before_sales_verification(): void
    {
        $this->setKeycloakRoles(['admin_pusat']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-008',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 200000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => false,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/cek-pusat"
        );

        $response->assertUnprocessable();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => 0,
            'status_cek_pusat' => 0,
            'tgl_cek_pusat' => null,
        ]);
    }

    public function test_sales_cannot_verify_payment_twice(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB001-20261127-009',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 10:00:00',
            'total' => 200000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'lunas',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 200000,
            'tanggal' => '2026-11-27 11:00:00',
            'status_verifikasi_sales' => true,
            'status_cek_pusat' => false,
            'tgl_cek_pusat' => null,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/verifikasi-sales"
        );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('kas_transaksis', 0);

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => 1,
        ]);
    }

    public function test_gudang_role_cannot_create_invoice(): void
    {
        $this->setKeycloakRoles(['gudang_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Petugas Gudang',
            'peran' => 'gudang_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD104',
            'nama_produk' => 'Produk Uji',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        DB::table('stoks')->insert([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
            'batas_minimum' => 2,
            'tgl_update' => '2026-11-27 10:00:00',
        ]);

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('fakturs', 0);
        $this->assertDatabaseHas('stoks', [
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 10,
        ]);
    }

    public function test_admin_pusat_cannot_verify_payment_as_sales(): void
    {
        $this->setKeycloakRoles(['admin_pusat']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB00120261127001',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'total' => 100000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
            'status_verifikasi_sales' => false,
            'status_cek_pusat' => false,
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/verifikasi-sales"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_verifikasi_sales' => false,
        ]);

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_admin_pusat_cannot_check_payment_twice(): void
    {
        $this->setKeycloakRoles(['admin_pusat']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB00120261127001',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'total' => 100000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'lunas',
        ], 'id_faktur');

        $idPembayaran = DB::table('pembayarans')->insertGetId([
            'id_faktur' => $idFaktur,
            'metode' => 'tunai',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
            'status_verifikasi_sales' => true,
            'status_cek_pusat' => true,
            'tgl_cek_pusat' => '2026-11-27 10:00:00',
        ], 'id_bayar');

        $response = $this->patchJson(
            "/api/v1/pembayaran/{$idPembayaran}/cek-pusat"
        );

        $response->assertUnprocessable();

        $this->assertDatabaseHas('pembayarans', [
            'id_bayar' => $idPembayaran,
            'status_cek_pusat' => true,
            'tgl_cek_pusat' => '2026-11-27 10:00:00',
        ]);
    }

    public function test_sales_role_cannot_check_payment_at_central_endpoint(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->patchJson('/api/v1/pembayaran/1/cek-pusat');

        $response->assertForbidden();
    }

    public function test_invoice_is_rejected_when_stock_record_does_not_exist(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);
        Http::fake();

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD105',
            'nama_produk' => 'Produk Tanpa Stok',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $response = $this->postJson('/api/v1/faktur', [
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'items' => [
                [
                    'id_produk' => $idProduk,
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.id_produk');

        $this->assertDatabaseCount('fakturs', 0);
        $this->assertDatabaseCount('detail_fakturs', 0);
        $this->assertDatabaseCount('mutasi_stoks', 0);
    }

    public function test_payment_rejects_zero_amount(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB00120261127002',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'total' => 100000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'tunai',
                'jumlah' => 0,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_payment_rejects_negative_amount(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Uji',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Uji',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idFaktur = DB::table('fakturs')->insertGetId([
            'no_faktur' => 'CB00120261127003',
            'id_cabang' => $idCabang,
            'id_pelanggan' => $idPelanggan,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'total' => 100000,
            'skema_pembayaran' => 'sekali_bayar',
            'status' => 'belum_dibayar',
        ], 'id_faktur');

        $response = $this->postJson(
            "/api/v1/faktur/{$idFaktur}/pembayaran",
            [
                'metode' => 'tunai',
                'jumlah' => -50000,
                'tanggal' => '2026-11-27',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_admin_cabang_can_record_cash_out_transaction(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 250000,
            'tanggal' => '2026-11-27',
            'keterangan' => 'Pembelian perlengkapan kantor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Transaksi kas berhasil dicatat.')
            ->assertJsonPath('data.jenis', 'keluar')
            ->assertJsonPath('data.jumlah', '250000.00')
            ->assertJsonPath('data.keterangan', 'Pembelian perlengkapan kantor');

        $this->assertDatabaseCount('kas_transaksis', 1);
        $this->assertDatabaseHas('kas_transaksis', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'id_faktur' => null,
            'jenis' => 'keluar',
            'jumlah' => 250000,
        ]);
    }

    public function test_cash_transaction_is_sent_to_central_successfully(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message' => 'Transaksi kas berhasil diterima oleh sistem pusat.',
            ], 201),
        ]);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 250000,
            'tanggal' => '2026-11-27',
            'keterangan' => 'Pembelian perlengkapan kantor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('sinkronisasi_pusat', 'berhasil')
            ->assertJsonPath('respons_pusat.success', true);

        $this->assertDatabaseCount('kas_transaksis', 1);

        Http::assertSent(function ($request) use ($idCabang) {
            return $request->url() === 'http://central-mock:3000/api/v1/kas'
                && $request->method() === 'POST'
                && $request['id_cabang'] === $idCabang
                && $request['jenis'] === 'keluar'
                && (float) $request['jumlah'] === 250000.0;
        });
    }

    public function test_admin_cabang_can_record_cash_deposit(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'setoran',
            'jumlah' => 500000,
            'tanggal' => '2026-11-27',
            'keterangan' => 'Setoran kas harian',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.jenis', 'setoran')
            ->assertJsonPath('data.jumlah', '500000.00');

        $this->assertDatabaseHas('kas_transaksis', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'setoran',
            'jumlah' => 500000,
            'keterangan' => 'Setoran kas harian',
        ]);
    }

    public function test_cash_transaction_rejects_zero_amount(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 0,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_cash_transaction_rejects_negative_amount(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => -100000,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('jumlah');

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_cash_transaction_is_rejected_when_employee_belongs_to_another_branch(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangLain,
            'nama' => 'Admin Cabang Lain',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Karyawan tidak aktif atau bukan bagian dari cabang tersebut.'
            );

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_cash_transaction_is_rejected_when_employee_is_inactive(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Tidak Aktif',
            'peran' => 'admin_cabang',
            'status' => 'nonaktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Karyawan tidak aktif atau bukan bagian dari cabang tersebut.'
            );

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_admin_cabang_can_view_cash_transactions(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        DB::table('kas_transaksis')->insert([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'id_faktur' => null,
            'tanggal' => '2026-11-27 10:00:00',
            'jenis' => 'keluar',
            'jumlah' => 150000,
            'keterangan' => 'Pembelian ATK',
        ]);

        $response = $this->getJson('/api/v1/kas');

        $response->assertOk()
            ->assertJsonPath('message', 'Daftar transaksi kas berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis', 'keluar')
            ->assertJsonPath('data.0.keterangan', 'Pembelian ATK');
    }

    public function test_cash_transactions_can_be_filtered(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        DB::table('kas_transaksis')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 10:00:00',
                'jenis' => 'keluar',
                'jumlah' => 150000,
                'keterangan' => 'Pembelian ATK',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-26 10:00:00',
                'jenis' => 'setoran',
                'jumlah' => 500000,
                'keterangan' => 'Setoran kas',
            ],
            [
                'id_cabang' => $idCabangLain,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 11:00:00',
                'jenis' => 'keluar',
                'jumlah' => 200000,
                'keterangan' => 'Transaksi cabang lain',
            ],
        ]);

        $response = $this->getJson('/api/v1/kas?' . http_build_query([
            'id_cabang' => $idCabang,
            'tanggal' => '2026-11-27',
            'jenis' => 'keluar',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keterangan', 'Pembelian ATK');
    }

    public function test_admin_cabang_can_view_cash_transaction_detail(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idKas = DB::table('kas_transaksis')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'id_faktur' => null,
            'tanggal' => '2026-11-27 10:00:00',
            'jenis' => 'keluar',
            'jumlah' => 150000,
            'keterangan' => 'Pembelian ATK',
        ], 'id_kas');

        $response = $this->getJson("/api/v1/kas/{$idKas}");

        $response->assertOk()
            ->assertJsonPath('message', 'Detail transaksi kas berhasil diambil.')
            ->assertJsonPath('data.id_kas', $idKas)
            ->assertJsonPath('data.jenis', 'keluar')
            ->assertJsonPath('data.keterangan', 'Pembelian ATK');
    }

    public function test_sales_cannot_record_cash_transaction(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Cabang',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'keluar',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_sales_cannot_view_cash_transactions(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->getJson('/api/v1/kas');

        $response->assertForbidden();

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_cash_transaction_rejects_manual_cash_in(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/kas', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'jenis' => 'masuk',
            'jumlah' => 100000,
            'tanggal' => '2026-11-27',
        ]);

        $response->assertUnprocessable()
            ->assertInvalid(['jenis']);

        $this->assertDatabaseCount('kas_transaksis', 0);
    }

    public function test_admin_cabang_can_create_daily_report(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Laporan harian berhasil dibuat.')
            ->assertJsonPath('data.id_cabang', $idCabang)
            ->assertJsonPath('data.id_karyawan', $idKaryawan)
            ->assertJsonPath('data.jenis_laporan', 'penjualan')
            ->assertJsonPath('data.status_kirim', 'belum_kirim');

        $this->assertDatabaseHas('laporan_harians', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27 00:00:00',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
        ]);
    }

    public function test_daily_report_rejects_duplicate_entry(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Cabang',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $payload = [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
        ];

        $firstResponse = $this->postJson('/api/v1/laporan-harian', $payload);
        $firstResponse->assertCreated();

        $secondResponse = $this->postJson('/api/v1/laporan-harian', $payload);

        $secondResponse->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Laporan untuk cabang, tanggal, dan jenis tersebut sudah tersedia.'
            );

        $this->assertDatabaseCount('laporan_harians', 1);
    }

    public function test_daily_report_is_rejected_when_employee_belongs_to_another_branch(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangLain,
            'nama' => 'Admin Cabang Lain',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Karyawan tidak aktif atau bukan bagian dari cabang tersebut.'
            );

        $this->assertDatabaseCount('laporan_harians', 0);
    }

    public function test_daily_report_is_rejected_when_employee_is_inactive(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Tidak Aktif',
            'peran' => 'admin_cabang',
            'status' => 'nonaktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Karyawan tidak aktif atau bukan bagian dari cabang tersebut.'
            );

        $this->assertDatabaseCount('laporan_harians', 0);
    }

    public function test_sales_cannot_create_daily_report(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Jambi',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('laporan_harians', 0);
    }

    public function test_admin_cabang_can_view_daily_reports(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        DB::table('laporan_harians')->insert([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ]);

        $response = $this->getJson('/api/v1/laporan-harian');

        $response->assertOk()
            ->assertJsonPath('message', 'Daftar laporan harian berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_laporan', 'penjualan');
    }

    public function test_daily_reports_can_be_filtered(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        DB::table('laporan_harians')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27',
                'jenis_laporan' => 'penjualan',
                'status_kirim' => 'belum_kirim',
                'tgl_kirim' => null,
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-26',
                'jenis_laporan' => 'stok',
                'status_kirim' => 'belum_kirim',
                'tgl_kirim' => null,
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian?id_cabang=' . $idCabang .
            '&tanggal=2026-11-27&jenis_laporan=penjualan'
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis_laporan', 'penjualan')
            ->assertJsonPath('data.0.tanggal', '2026-11-26T17:00:00.000000Z');
    }

    public function test_admin_cabang_can_view_daily_report_detail(): void
    {

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $response = $this->getJson('/api/v1/laporan-harian/' . $idLaporan);

        $response->assertOk()
            ->assertJsonPath('message', 'Detail laporan harian berhasil diambil.')
            ->assertJsonPath('data.id_laporan', $idLaporan)
            ->assertJsonPath('data.jenis_laporan', 'penjualan')
            ->assertJsonStructure([
                'ringkasan',
            ]);
    }

    public function test_admin_cabang_can_send_daily_report_to_central(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        Http::fake([
            '*' => Http::response([
                'message' => 'Laporan berhasil diterima.',
            ], 200),
        ]);

        $response = $this->patchJson(
            '/api/v1/laporan-harian/' . $idLaporan . '/kirim'
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Laporan berhasil dikirim ke sistem pusat.')
            ->assertJsonPath('data.status_kirim', 'terkirim');

        $this->assertDatabaseHas('laporan_harians', [
            'id_laporan' => $idLaporan,
            'status_kirim' => 'terkirim',
        ]);

        $this->assertNotNull(
            DB::table('laporan_harians')
                ->where('id_laporan', $idLaporan)
                ->value('tgl_kirim')
        );
    }

    public function test_daily_report_remains_unsent_when_central_integration_fails(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        Http::fake([
            '*' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        $response = $this->patchJson(
            '/api/v1/laporan-harian/' . $idLaporan . '/kirim'
        );

        $response->assertStatus(502)
            ->assertJsonPath(
                'message',
                'Pengiriman laporan ke sistem pusat gagal.'
            );

        $this->assertDatabaseHas('laporan_harians', [
            'id_laporan' => $idLaporan,
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ]);
    }

    public function test_daily_report_cannot_be_sent_twice(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'terkirim',
            'tgl_kirim' => now(),
        ], 'id_laporan');

        $response = $this->patchJson(
            '/api/v1/laporan-harian/' . $idLaporan . '/kirim'
        );

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Laporan ini sudah pernah dikirim.'
            );

        $this->assertDatabaseHas('laporan_harians', [
            'id_laporan' => $idLaporan,
            'status_kirim' => 'terkirim',
        ]);
    }

    public function test_sales_cannot_send_daily_report_to_central(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Jambi',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        $response = $this->patchJson(
            '/api/v1/laporan-harian/' . $idLaporan . '/kirim'
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('laporan_harians', [
            'id_laporan' => $idLaporan,
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ]);
    }

    public function test_sales_cannot_view_daily_reports(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $response = $this->getJson('/api/v1/laporan-harian');

        $response->assertForbidden();
    }

    public function test_sales_cannot_view_daily_report_detail(): void
    {
        $this->setKeycloakRoles(['sales_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Sales Jambi',
            'peran' => 'sales_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertForbidden();
    }

    public function test_daily_report_rejects_invalid_report_type(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'keuangan',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis_laporan']);

        $this->assertDatabaseCount('laporan_harians', 0);
    }

    public function test_daily_report_rejects_invalid_date(): void
    {
        $this->setKeycloakRoles(['admin_cabang']);

        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $response = $this->postJson('/api/v1/laporan-harian', [
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => 'tanggal-salah',
            'jenis_laporan' => 'penjualan',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal']);

        $this->assertDatabaseCount('laporan_harians', 0);
    }

    public function test_daily_cash_in_report_calculates_correct_summary(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'kas_masuk',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('kas_transaksis')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 09:00:00',
                'jenis' => 'masuk',
                'jumlah' => 500000,
                'keterangan' => 'Penerimaan pertama',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 13:00:00',
                'jenis' => 'masuk',
                'jumlah' => 300000,
                'keterangan' => 'Penerimaan kedua',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 14:00:00',
                'jenis' => 'keluar',
                'jumlah' => 100000,
                'keterangan' => 'Pengeluaran',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jenis', 'masuk')
            ->assertJsonPath('ringkasan.jumlah_transaksi', 2)
            ->assertJsonPath('ringkasan.total', 800000)
            ->assertJsonCount(2, 'ringkasan.transaksi');
    }

    public function test_daily_cash_out_report_calculates_correct_summary(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'kas_keluar',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('kas_transaksis')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 09:00:00',
                'jenis' => 'keluar',
                'jumlah' => 200000,
                'keterangan' => 'Pembelian perlengkapan',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 13:00:00',
                'jenis' => 'keluar',
                'jumlah' => 150000,
                'keterangan' => 'Biaya operasional',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'id_faktur' => null,
                'tanggal' => '2026-11-27 14:00:00',
                'jenis' => 'masuk',
                'jumlah' => 500000,
                'keterangan' => 'Penerimaan kas',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jenis', 'keluar')
            ->assertJsonPath('ringkasan.jumlah_transaksi', 2)
            ->assertJsonPath('ringkasan.total', 350000)
            ->assertJsonCount(2, 'ringkasan.transaksi');
    }

    public function test_daily_sales_report_calculates_correct_summary(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Jambi',
            'no_hp' => '081234567890',
            'alamat' => 'Kota Jambi',
        ], 'id_pelanggan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('fakturs')->insert([
            [
                'no_faktur' => 'INV-TEST-001',
                'id_cabang' => $idCabang,
                'id_pelanggan' => $idPelanggan,
                'id_karyawan' => $idKaryawan,
                'id_promo' => null,
                'tanggal' => '2026-11-27 09:00:00',
                'total' => 1500000,
                'skema_pembayaran' => 'sekali_bayar',
                'status' => 'lunas',
            ],
            [
                'no_faktur' => 'INV-TEST-002',
                'id_cabang' => $idCabang,
                'id_pelanggan' => $idPelanggan,
                'id_karyawan' => $idKaryawan,
                'id_promo' => null,
                'tanggal' => '2026-11-27 13:00:00',
                'total' => 2500000,
                'skema_pembayaran' => 'sekali_bayar',
                'status' => 'lunas',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_faktur', 2)
            ->assertJsonPath('ringkasan.total_penjualan', 4000000)
            ->assertJsonCount(2, 'ringkasan.faktur');
    }

    public function test_daily_stock_report_calculates_correct_summary(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idProduk1 = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-TEST-001',
            'nama_produk' => 'Produk A',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idProduk2 = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-TEST-002',
            'nama_produk' => 'Produk B',
            'kategori' => 'Elektronik',
            'harga' => 200000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idProduk3 = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-TEST-003',
            'nama_produk' => 'Produk C',
            'kategori' => 'Elektronik',
            'harga' => 300000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'stok',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('stoks')->insert([
            [
                'id_cabang' => $idCabang,
                'id_produk' => $idProduk1,
                'jumlah' => 10,
                'batas_minimum' => 5,
            ],
            [
                'id_cabang' => $idCabang,
                'id_produk' => $idProduk2,
                'jumlah' => 3,
                'batas_minimum' => 5,
            ],
            [
                'id_cabang' => $idCabang,
                'id_produk' => $idProduk3,
                'jumlah' => 5,
                'batas_minimum' => 5,
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_jenis_produk', 3)
            ->assertJsonPath('ringkasan.total_unit_stok', 18)
            ->assertJsonPath(
                'ringkasan.jumlah_produk_mencapai_batas_minimum',
                2
            )
            ->assertJsonCount(3, 'ringkasan.stok');
    }

    public function test_daily_stock_report_only_counts_stock_from_its_branch(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idProduk1 = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-LOCAL-001',
            'nama_produk' => 'Produk Lokal',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idProduk2 = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-OTHER-001',
            'nama_produk' => 'Produk Cabang Lain',
            'kategori' => 'Elektronik',
            'harga' => 200000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'stok',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('stoks')->insert([
            [
                'id_cabang' => $idCabang,
                'id_produk' => $idProduk1,
                'jumlah' => 10,
                'batas_minimum' => 5,
            ],
            [
                'id_cabang' => $idCabangLain,
                'id_produk' => $idProduk2,
                'jumlah' => 50,
                'batas_minimum' => 10,
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_jenis_produk', 1)
            ->assertJsonPath('ringkasan.total_unit_stok', 10)
            ->assertJsonPath(
                'ringkasan.jumlah_produk_mencapai_batas_minimum',
                0
            )
            ->assertJsonCount(1, 'ringkasan.stok');
    }

    public function test_daily_stock_report_includes_products_with_zero_stock(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-ZERO-001',
            'nama_produk' => 'Produk Stok Habis',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'stok',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('stoks')->insert([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 0,
            'batas_minimum' => 5,
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_jenis_produk', 1)
            ->assertJsonPath('ringkasan.total_unit_stok', 0)
            ->assertJsonPath(
                'ringkasan.jumlah_produk_mencapai_batas_minimum',
                1
            )
            ->assertJsonPath('ringkasan.stok.0.jumlah', 0);
    }
    
    public function test_daily_stock_report_counts_stock_equal_to_minimum(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idProduk = DB::table('produks')->insertGetId([
            'kode_produk' => 'PRD-MIN-001',
            'nama_produk' => 'Produk Batas Minimum',
            'kategori' => 'Elektronik',
            'harga' => 100000,
            'masa_garansi' => 12,
        ], 'id_produk');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'stok',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('stoks')->insert([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'jumlah' => 5,
            'batas_minimum' => 5,
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_jenis_produk', 1)
            ->assertJsonPath('ringkasan.total_unit_stok', 5)
            ->assertJsonPath(
                'ringkasan.jumlah_produk_mencapai_batas_minimum',
                1
            );
    }

    public function test_daily_sales_report_only_counts_invoices_from_its_branch_and_date(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idPelanggan = DB::table('pelanggans')->insertGetId([
            'nama' => 'Pelanggan Jambi',
            'alamat' => 'Kota Jambi',
            'no_hp' => '081234567890',
        ], 'id_pelanggan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'penjualan',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('fakturs')->insert([
            [
                'no_faktur' => 'INV-LOCAL-001',
                'id_cabang' => $idCabang,
                'id_pelanggan' => $idPelanggan,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27',
                'total' => 1000000,
                'skema_pembayaran' => 'sekali_bayar',
                'status' => 'belum_dibayar',
            ],
            [
                'no_faktur' => 'INV-OTHER-001',
                'id_cabang' => $idCabangLain,
                'id_pelanggan' => $idPelanggan,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27',
                'total' => 2000000,
                'skema_pembayaran' => 'sekali_bayar',
                'status' => 'belum_dibayar',
            ],
            [
                'no_faktur' => 'INV-OLD-001',
                'id_cabang' => $idCabang,
                'id_pelanggan' => $idPelanggan,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-26',
                'total' => 3000000,
                'skema_pembayaran' => 'sekali_bayar',
                'status' => 'belum_dibayar',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_faktur', 1)
            ->assertJsonPath('ringkasan.total_penjualan', 1000000)
            ->assertJsonCount(1, 'ringkasan.faktur')
            ->assertJsonPath('ringkasan.faktur.0.no_faktur', 'INV-LOCAL-001');
    }

    public function test_daily_cash_in_report_only_counts_transactions_from_its_branch_and_date(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'kas_masuk',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('kas_transaksis')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 09:00:00',
                'jenis' => 'masuk',
                'jumlah' => 500000,
                'keterangan' => 'Kas masuk lokal',
            ],
            [
                'id_cabang' => $idCabangLain,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 10:00:00',
                'jenis' => 'masuk',
                'jumlah' => 700000,
                'keterangan' => 'Kas masuk cabang lain',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-26 10:00:00',
                'jenis' => 'masuk',
                'jumlah' => 900000,
                'keterangan' => 'Kas masuk tanggal lain',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 11:00:00',
                'jenis' => 'keluar',
                'jumlah' => 300000,
                'keterangan' => 'Kas keluar lokal',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_transaksi', 1)
            ->assertJsonPath('ringkasan.total', 500000)
            ->assertJsonCount(1, 'ringkasan.transaksi');
    }

    public function test_daily_cash_out_report_only_counts_transactions_from_its_branch_and_date(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idKaryawan,
            $idCabang
        );

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabang,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'kas_keluar',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        DB::table('kas_transaksis')->insert([
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 09:00:00',
                'jenis' => 'keluar',
                'jumlah' => 200000,
                'keterangan' => 'Kas keluar lokal',
            ],
            [
                'id_cabang' => $idCabangLain,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 10:00:00',
                'jenis' => 'keluar',
                'jumlah' => 700000,
                'keterangan' => 'Kas keluar cabang lain',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-26 10:00:00',
                'jenis' => 'keluar',
                'jumlah' => 900000,
                'keterangan' => 'Kas keluar tanggal lain',
            ],
            [
                'id_cabang' => $idCabang,
                'id_karyawan' => $idKaryawan,
                'tanggal' => '2026-11-27 11:00:00',
                'jenis' => 'masuk',
                'jumlah' => 300000,
                'keterangan' => 'Kas masuk lokal',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertOk()
            ->assertJsonPath('ringkasan.jumlah_transaksi', 1)
            ->assertJsonPath('ringkasan.total', 200000)
            ->assertJsonCount(1, 'ringkasan.transaksi');
    }

    public function test_admin_cabang_cannot_view_daily_report_from_another_branch(): void
    {
        $idCabang = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Jambi',
            'jenis' => 'showroom',
            'alamat' => 'Kota Jambi',
        ], 'id_cabang');

        $idCabangLain = DB::table('cabangs')->insertGetId([
            'nama_cabang' => 'Cabang Muaro',
            'jenis' => 'showroom',
            'alamat' => 'Muaro Jambi',
        ], 'id_cabang');

        // Admin yang sedang login berasal dari cabang 1.
        $idAdmin = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabang,
            'nama' => 'Admin Jambi',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $this->setKeycloakRoles(
            ['admin_cabang'],
            $idAdmin,
            $idCabang
        );

        // Laporan yang akan diakses berasal dari cabang lain.
        $idKaryawan = DB::table('karyawans')->insertGetId([
            'id_cabang' => $idCabangLain,
            'nama' => 'Admin Muaro',
            'peran' => 'admin_cabang',
            'status' => 'aktif',
        ], 'id_karyawan');

        $idLaporan = DB::table('laporan_harians')->insertGetId([
            'id_cabang' => $idCabangLain,
            'id_karyawan' => $idKaryawan,
            'tanggal' => '2026-11-27',
            'jenis_laporan' => 'kas_masuk',
            'status_kirim' => 'belum_kirim',
            'tgl_kirim' => null,
        ], 'id_laporan');

        $response = $this->getJson(
            '/api/v1/laporan-harian/' . $idLaporan
        );

        $response->assertForbidden();
    }

    public function test_sales_without_branch_attribute_cannot_view_invoice(): void
    {
        $faktur = $this->buatFakturUntukTestB2();

        $this->setKeycloakRoles([
            'sales_cabang',
        ]);

        $response = $this->getJson(
            '/api/v1/faktur/' . $faktur['id_faktur']
        );

        $response->assertForbidden();
    }
}
