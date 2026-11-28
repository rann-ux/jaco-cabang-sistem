# Dokumentasi API Endpoint Sistem Informasi Operasional Terintegrasi Cabang PT Jaco Nusantara

## 1. Pendahuluan

Dokumentasi ini menjelaskan antarmuka pemrograman aplikasi (Application Programming Interface/API) pada Sistem Informasi Operasional Terintegrasi Cabang PT Jaco Nusantara. API digunakan untuk mendukung pertukaran data dan komunikasi antarmodul dalam aplikasi cabang serta integrasi dengan sistem pusat.

Dokumentasi disusun berdasarkan implementasi aktual pada repository aplikasi, meliputi daftar endpoint, metode HTTP, parameter, validasi, hak akses pengguna, serta mekanisme komunikasi dengan sistem pusat.

## 2. Ruang Lingkup

API yang didokumentasikan mencakup:

1. API internal aplikasi cabang untuk pengelolaan data cabang, pelanggan, faktur, pembayaran, stok, penerimaan barang, mutasi stok, kas, dan laporan harian.
2. API sinkronisasi master data produk dan promo.
3. API integrasi keluar dari aplikasi cabang menuju Central Mock untuk pengiriman data transaksi, penerimaan barang, dan laporan harian.
4. API Central Mock untuk menerima data dari aplikasi cabang dan menyediakan pemeriksaan status layanan.

Dokumentasi ini menggambarkan endpoint yang telah ditemukan pada implementasi aplikasi. Endpoint yang belum tersedia tidak dianggap sebagai fitur yang telah selesai diimplementasikan.

## 3. Autentikasi dan Hak Akses

### 3.1 Autentikasi

Endpoint API internal aplikasi cabang menggunakan Keycloak sebagai penyedia autentikasi. Seluruh route API versi 1 dikelompokkan dalam middleware `keycloak`, sehingga permintaan harus melewati proses validasi token sebelum diproses oleh controller.

Token autentikasi menggunakan mekanisme JSON Web Token (JWT) dengan algoritma RS256.

### 3.2 Role Pengguna

Sistem menerapkan Role-Based Access Control (RBAC) untuk membatasi akses berdasarkan peran pengguna. Role yang digunakan meliputi:

| Role | Keterangan |
|---|---|
| `admin_cabang` | Mengelola operasional dan administrasi cabang |
| `sales_cabang` | Menangani transaksi penjualan dan pembayaran |
| `gudang_cabang` | Menangani penerimaan dan mutasi stok |
| `manajer_cabang` | Mengakses informasi operasional dan laporan cabang |
| `admin_pusat` | Mengelola data pusat, sinkronisasi master data, dan verifikasi pembayaran |

### 3.3 Format Komunikasi

API internal menggunakan format JSON untuk pertukaran data. Endpoint diakses melalui prefix `/api/v1`, sedangkan metode HTTP disesuaikan dengan operasi yang dilakukan, seperti GET, POST, PUT, PATCH, dan DELETE.

## 4. Daftar Endpoint Internal Aplikasi Cabang

Seluruh endpoint pada bagian ini menggunakan prefix `/api/v1` dan dilindungi oleh middleware `keycloak`. Hak akses ditentukan berdasarkan role pengguna yang telah diautentikasi.

### 4.1 Modul Faktur dan Pembayaran (M1)

| Method | Endpoint | Hak Akses | Fungsi |
|---|---|---|---|
| POST | `/faktur` | `sales_cabang` | Membuat faktur, detail transaksi, PO pengantaran, dan mengurangi stok |
| POST | `/faktur/{id}/pembayaran` | `sales_cabang` | Mencatat pembayaran faktur |
| PATCH | `/pembayaran/{id}/verifikasi-sales` | `sales_cabang` | Memverifikasi pembayaran oleh sales |
| PATCH | `/pembayaran/{id}/cek-pusat` | `admin_pusat` | Memperbarui status pemeriksaan pembayaran oleh pusat |

### 4.2 Modul Persediaan dan Gudang (M2)

| Method | Endpoint | Hak Akses | Fungsi |
|---|---|---|---|
| POST | `/penerimaan-barang` | `gudang_cabang` | Mencatat penerimaan barang dan menambah stok |
| POST | `/mutasi-stok` | `gudang_cabang` | Mencatat mutasi stok masuk atau keluar |

### 4.3 Modul Master Data Cabang dan Pelanggan (M6)

| Method | Endpoint | Hak Akses | Fungsi |
|---|---|---|---|
| GET | `/cabang` | Seluruh role | Melihat daftar cabang |
| GET | `/cabang/{id}` | Seluruh role | Melihat detail cabang |
| POST | `/cabang` | `admin_pusat` | Menambahkan data cabang |
| PUT | `/cabang/{id}` | `admin_pusat` | Memperbarui data cabang |
| DELETE | `/cabang/{id}` | `admin_pusat` | Menghapus data cabang |
| GET | `/pelanggan` | Seluruh role | Melihat daftar pelanggan |
| GET | `/pelanggan/{id}` | Seluruh role | Melihat detail pelanggan |
| POST | `/pelanggan` | `admin_cabang` | Menambahkan pelanggan |
| PUT | `/pelanggan/{id}` | `admin_cabang` | Memperbarui data pelanggan |
| DELETE | `/pelanggan/{id}` | `admin_cabang` | Menghapus data pelanggan |

Keterangan: Seluruh role terdiri atas `sales_cabang`, `admin_cabang`, `gudang_cabang`, `manajer_cabang`, dan `admin_pusat`.

### 4.4 Modul Sinkronisasi Master Data (M7)

| Method | Endpoint | Hak Akses | Fungsi |
|---|---|---|---|
| POST | `/sinkronisasi/produk` | `admin_pusat` | Menerima dan memperbarui data master produk |
| POST | `/sinkronisasi/promo` | `admin_pusat` | Menerima dan memperbarui data master promo |

### 4.5 Modul Kas dan Laporan Harian (M4)

| Method | Endpoint | Hak Akses | Fungsi |
|---|---|---|---|
| GET | `/kas` | `admin_cabang`, `manajer_cabang`, `admin_pusat` | Melihat daftar transaksi kas |
| POST | `/kas` | `admin_cabang` | Mencatat transaksi kas keluar atau setoran |
| GET | `/kas/{id}` | `admin_cabang`, `manajer_cabang`, `admin_pusat` | Melihat detail transaksi kas |
| GET | `/laporan-harian` | `admin_cabang`, `manajer_cabang`, `admin_pusat` | Melihat daftar laporan harian |
| POST | `/laporan-harian` | `admin_cabang` | Membuat laporan harian |
| GET | `/laporan-harian/{id}` | `admin_cabang`, `manajer_cabang`, `admin_pusat` | Melihat detail dan ringkasan laporan |
| PATCH | `/laporan-harian/{id}/kirim` | `admin_cabang` | Mengirim laporan harian ke sistem pusat |

**Catatan:** Daftar di atas memuat 25 route aktual yang ditemukan pada implementasi aplikasi. Endpoint dituliskan relatif terhadap prefix `/api/v1`.

## 5. Integrasi Aplikasi Cabang dengan Sistem Pusat

### 5.1 Mekanisme Integrasi

Komunikasi antara aplikasi cabang dan sistem pusat dilakukan melalui layanan `IntegrasiPusatService`. Layanan ini menggunakan HTTP POST dengan format JSON untuk mengirimkan data ke Central Mock.

Alamat layanan pusat diatur melalui konfigurasi `services.central_mock.url`, dengan alamat default `http://central-mock:3000`.

Pengiriman data menggunakan batas waktu koneksi 10 detik. Apabila sistem pusat memberikan respons HTTP yang tidak sukses atau terjadi kegagalan koneksi, layanan akan menghasilkan exception untuk ditangani oleh controller pemanggil.

### 5.2 Endpoint Integrasi Keluar

| Kode | Method | Endpoint Sistem Pusat | Sumber Pemanggilan |
|---|---|---|---|
| A1 | POST | `/api/v1/laporan-harian` | `LaporanHarianController@kirim` |
| A2 | POST | `/api/v1/transaksi` | `FakturController@store` |
| A7 | POST | `/api/v1/penerimaan-barang` | `PenerimaanBarangController@store` |

Endpoint tersebut merupakan komunikasi keluar dari aplikasi cabang menuju Central Mock.

### 5.3 Data yang Dikirim

| Endpoint | Data yang Dikirim |
|---|---|
| `/api/v1/transaksi` | `id_transaksi`, `id_cabang`, `total`, `tanggal` |
| `/api/v1/laporan-harian` | `id_laporan`, `id_cabang`, `tanggal` |
| `/api/v1/penerimaan-barang` | `id_penerimaan`, `id_cabang`, `tanggal` |

### 5.4 Endpoint Penerimaan Central Mock

Central Mock merupakan layanan tiruan sistem pusat yang dibangun menggunakan Node.js dan Express. Layanan ini menyediakan endpoint berikut:

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/health` | Memeriksa status layanan Central Mock |
| POST | `/api/v1/transaksi` | Menerima data transaksi dari cabang |
| POST | `/api/v1/laporan-harian` | Menerima data laporan harian |
| POST | `/api/v1/penerimaan-barang` | Menerima konfirmasi penerimaan barang |

Setiap endpoint POST melakukan pemeriksaan terhadap kelengkapan field yang diwajibkan. Jika data tidak lengkap, Central Mock memberikan respons HTTP 422. Jika data diterima, layanan memberikan respons HTTP 201 dengan status sinkronisasi `diterima`.

### 5.5 Batasan Implementasi Integrasi

Berdasarkan kode Central Mock yang tersedia, layanan saat ini hanya melakukan validasi payload dan memberikan respons penerimaan data. Belum terdapat mekanisme penyimpanan data ke database pusat.

Selain itu, implementasi integrasi belum menunjukkan adanya antrean pengiriman, retry otomatis, maupun autentikasi khusus antara aplikasi cabang dan Central Mock. Pengiriman dilakukan secara langsung dan menunggu respons dari layanan tujuan.

## 6. Detail Request dan Respons Endpoint

### 6.1 Modul Faktur dan Pembayaran

#### 6.1.1 Membuat Faktur

- **Method:** POST
- **Endpoint:** `/api/v1/faktur`
- **Role:** `sales_cabang`
- **Controller:** `FakturController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Wajib, harus terdaftar pada tabel cabang |
| `id_pelanggan` | Integer | Wajib, harus terdaftar pada tabel pelanggan |
| `id_karyawan` | Integer | Wajib, harus terdaftar pada tabel karyawan |
| `tanggal` | Date | Wajib |
| `skema_pembayaran` | String | Opsional, `sekali_bayar` atau `cicilan` |
| `items` | Array | Wajib, minimal satu item |
| `items.*.id_produk` | Integer | Wajib, produk harus terdaftar dan tidak boleh duplikat dalam satu request |
| `items.*.jumlah` | Integer | Wajib, minimal 1 |

**Proses:**

1. Memvalidasi data faktur dan item transaksi.
2. Memastikan karyawan aktif dan berasal dari cabang yang dipilih.
3. Memeriksa ketersediaan stok pada cabang dan produk terkait.
4. Membuat faktur beserta detail transaksi.
5. Mengurangi stok dan mencatat mutasi stok keluar.
6. Membuat PO pengantaran secara otomatis.
7. Mengirim ringkasan transaksi ke sistem pusat melalui `IntegrasiPusatService`.

Proses penyimpanan faktur, detail, pengurangan stok, mutasi stok, dan pembuatan PO dilakukan dalam satu transaksi database.

**Respons:**

- HTTP 201 jika faktur berhasil dibuat.
- Respons memuat pesan, data faktur, status sinkronisasi pusat, dan respons pusat.
- Jika pengiriman ke pusat gagal, faktur lokal tetap tersimpan dan `sinkronisasi_pusat` bernilai `gagal`.

#### 6.1.2 Mencatat Pembayaran Faktur

- **Method:** POST
- **Endpoint:** `/api/v1/faktur/{id}/pembayaran`
- **Role:** `sales_cabang`
- **Controller:** `PembayaranController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `metode` | String | Wajib, `tunai`, `debit`, atau `transfer` |
| `jumlah` | Numeric | Wajib, lebih dari 0 |
| `tanggal` | Date | Opsional |

**Proses:**

1. Memeriksa keberadaan dan status faktur.
2. Menghitung jumlah pembayaran yang sudah diverifikasi maupun yang masih menunggu.
3. Memastikan pembayaran tidak melebihi sisa tagihan.
4. Untuk skema `sekali_bayar`, jumlah pembayaran harus sama dengan sisa tagihan.
5. Menyimpan pembayaran dengan status verifikasi sales dan pemeriksaan pusat bernilai `false`.

**Respons:** HTTP 201 jika pembayaran berhasil dicatat.

#### 6.1.3 Verifikasi Pembayaran oleh Sales

- **Method:** PATCH
- **Endpoint:** `/api/v1/pembayaran/{id}/verifikasi-sales`
- **Role:** `sales_cabang`
- **Controller:** `PembayaranController@verifySales`

Endpoint ini digunakan untuk memverifikasi pembayaran yang telah dicatat.

Sistem memeriksa agar pembayaran tidak diverifikasi lebih dari satu kali dan total pembayaran terverifikasi tidak melebihi nilai faktur. Setelah verifikasi berhasil, sistem memperbarui status faktur dan secara otomatis membuat transaksi kas masuk.

**Respons:** HTTP 200 jika verifikasi berhasil.

#### 6.1.4 Pemeriksaan Pembayaran oleh Pusat

- **Method:** PATCH
- **Endpoint:** `/api/v1/pembayaran/{id}/cek-pusat`
- **Role:** `admin_pusat`
- **Controller:** `PembayaranController@cekPusat`

Endpoint ini digunakan untuk memperbarui status pemeriksaan pembayaran oleh pihak pusat.

Sistem mensyaratkan pembayaran telah diverifikasi oleh sales dan belum pernah diperiksa oleh pusat. Jika validasi terpenuhi, sistem memperbarui `status_cek_pusat` menjadi `true` dan mengisi `tgl_cek_pusat`.

**Respons:** HTTP 200 jika pemeriksaan berhasil.

**Catatan:** Implementasi controller ini memperbarui status pemeriksaan pada database cabang. Belum ditemukan pemanggilan API eksternal ke sistem pusat dalam proses tersebut.

### 6.2 Modul Persediaan dan Gudang (M2)

#### 6.2.1 Mencatat Penerimaan Barang

- **Method:** POST
- **Endpoint:** `/api/v1/penerimaan-barang`
- **Role:** `gudang_cabang`
- **Controller:** `PenerimaanBarangController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Wajib, harus terdaftar pada tabel cabang |
| `id_produk` | Integer | Wajib, harus terdaftar pada tabel produk |
| `id_karyawan` | Integer | Wajib, harus terdaftar pada tabel karyawan |
| `jumlah` | Integer | Wajib, minimal 1 |
| `tanggal` | Date | Opsional, default tanggal saat ini |
| `no_dokumen_pusat` | String | Wajib, maksimal 30 karakter |

**Proses:**

1. Memvalidasi data penerimaan barang.
2. Memastikan karyawan aktif dan berasal dari cabang yang dipilih.
3. Memeriksa duplikasi berdasarkan nomor dokumen pusat, cabang, dan produk.
4. Memeriksa atau membuat data stok untuk produk pada cabang terkait.
5. Menyimpan data penerimaan barang.
6. Menambah jumlah stok sesuai kuantitas yang diterima.
7. Mencatat mutasi stok dengan jenis `masuk`.
8. Mengirim informasi penerimaan barang ke Central Mock melalui `IntegrasiPusatService`.

Proses pencatatan penerimaan, penambahan stok, dan pembuatan mutasi stok dilakukan dalam satu transaksi database.

**Respons:**

- HTTP 201 jika penerimaan barang berhasil dicatat.
- Respons memuat pesan, data penerimaan, status sinkronisasi pusat, dan respons pusat.
- Jika pengiriman ke pusat gagal, data penerimaan lokal tetap tersimpan dan `sinkronisasi_pusat` bernilai `gagal`.
- Jika ditemukan penerimaan duplikat, sistem memberikan respons HTTP 422.

#### 6.2.2 Mencatat Mutasi Stok

- **Method:** POST
- **Endpoint:** `/api/v1/mutasi-stok`
- **Role:** `gudang_cabang`
- **Controller:** `MutasiStokController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Wajib, harus terdaftar pada tabel cabang |
| `id_produk` | Integer | Wajib, harus terdaftar pada tabel produk |
| `id_karyawan` | Integer | Wajib, harus terdaftar pada tabel karyawan |
| `jenis` | String | Wajib, `masuk` atau `keluar` |
| `jumlah` | Integer | Wajib, minimal 1 |
| `tanggal` | Date | Opsional, default tanggal saat ini |
| `referensi_dokumen` | String | Opsional, maksimal 50 karakter |

**Proses:**

1. Memvalidasi data mutasi stok.
2. Memastikan karyawan aktif dan berasal dari cabang yang dipilih.
3. Memeriksa keberadaan data stok berdasarkan cabang dan produk.
4. Mengunci data stok selama proses perubahan.
5. Jika jenis mutasi `masuk`, sistem menambah jumlah stok.
6. Jika jenis mutasi `keluar`, sistem memeriksa ketersediaan stok sebelum mengurangi jumlahnya.
7. Memperbarui jumlah stok dan tanggal pembaruan.
8. Menyimpan catatan mutasi stok beserta relasi data terkait.

**Respons:** HTTP 201 jika mutasi stok berhasil dicatat.

Jika stok tidak ditemukan atau jumlah stok tidak mencukupi untuk mutasi keluar, sistem menolak proses tersebut.

**Catatan:** Penerimaan barang secara otomatis menghasilkan mutasi stok masuk. Oleh karena itu, satu penerimaan barang tidak boleh dicatat kembali melalui endpoint mutasi stok karena dapat menyebabkan penambahan stok ganda.

### 6.3 Modul Master Data Cabang dan Pelanggan (M6)

#### 6.3.1 Mengelola Data Cabang

Endpoint pengelolaan data cabang menggunakan controller `CabangController`.

| Method | Endpoint | Role | Fungsi |
|---|---|---|---|
| GET | `/api/v1/cabang` | Seluruh role | Menampilkan daftar cabang |
| GET | `/api/v1/cabang/{id}` | Seluruh role | Menampilkan detail cabang |
| POST | `/api/v1/cabang` | `admin_pusat` | Menambahkan cabang |
| PUT | `/api/v1/cabang/{id}` | `admin_pusat` | Memperbarui data cabang |
| DELETE | `/api/v1/cabang/{id}` | `admin_pusat` | Menghapus data cabang |

**Parameter Request untuk POST dan PUT:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `nama_cabang` | String | Wajib saat POST, maksimal 100 karakter |
| `jenis` | String | Wajib saat POST, `showroom`, `kantor`, atau `gudang` |
| `alamat` | String | Wajib saat POST |

Pada metode PUT, ketiga field dapat dikirim secara opsional, tetapi setiap field yang dikirim harus memenuhi ketentuan validasinya.

**Proses:**

- GET daftar cabang menampilkan data berdasarkan urutan `id_cabang`.
- GET detail cabang menampilkan data cabang berdasarkan ID.
- POST menyimpan data cabang baru.
- PUT memperbarui data cabang yang telah tersedia.
- DELETE menghapus data cabang berdasarkan ID.

**Respons:**

- HTTP 201 untuk penambahan data.
- HTTP 200 untuk pengambilan, pembaruan, dan penghapusan data.
- Jika data tidak ditemukan, sistem mengembalikan respons kesalahan sesuai mekanisme Laravel.

#### 6.3.2 Mengelola Data Pelanggan

Endpoint pengelolaan pelanggan menggunakan controller `PelangganController`.

| Method | Endpoint | Role | Fungsi |
|---|---|---|---|
| GET | `/api/v1/pelanggan` | Seluruh role | Menampilkan daftar pelanggan |
| GET | `/api/v1/pelanggan/{id}` | Seluruh role | Menampilkan detail pelanggan |
| POST | `/api/v1/pelanggan` | `admin_cabang` | Menambahkan pelanggan |
| PUT | `/api/v1/pelanggan/{id}` | `admin_cabang` | Memperbarui data pelanggan |
| DELETE | `/api/v1/pelanggan/{id}` | `admin_cabang` | Menghapus data pelanggan |

**Parameter Request untuk POST dan PUT:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `nama` | String | Wajib saat POST, maksimal 100 karakter |
| `alamat` | String | Wajib saat POST |
| `no_hp` | String | Wajib saat POST, maksimal 20 karakter |

Pada metode PUT, setiap field dapat dikirim secara opsional. Field yang dikirim tetap harus memenuhi ketentuan validasinya.

**Proses:**

- GET daftar pelanggan menampilkan data pelanggan berdasarkan urutan `id_pelanggan`.
- GET detail pelanggan menampilkan data berdasarkan ID.
- POST menyimpan pelanggan baru.
- PUT memperbarui data pelanggan.
- DELETE menghapus data pelanggan berdasarkan ID.

**Respons:**

- HTTP 201 untuk penambahan pelanggan.
- HTTP 200 untuk pengambilan, pembaruan, dan penghapusan data.
- Jika data tidak ditemukan, sistem mengembalikan respons kesalahan sesuai mekanisme Laravel.

### 6.4 Modul Sinkronisasi Master Data (M7)

#### 6.4.1 Sinkronisasi Data Produk

- **Method:** POST
- **Endpoint:** `/api/v1/sinkronisasi/produk`
- **Role:** `admin_pusat`
- **Controller:** `SinkronisasiMasterDataController@syncProduk`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `data` | Array | Wajib, minimal satu data produk |
| `data.*.kode_produk` | String | Wajib, maksimal 30 karakter |
| `data.*.nama_produk` | String | Wajib, maksimal 150 karakter |
| `data.*.kategori` | String | Wajib, maksimal 50 karakter |
| `data.*.harga` | Numeric | Wajib, minimal 0 |
| `data.*.masa_garansi` | Integer | Wajib, minimal 0 |

**Proses:**

1. Memvalidasi seluruh data produk yang dikirim.
2. Memeriksa produk berdasarkan `kode_produk`.
3. Memperbarui data produk apabila kode produk telah tersedia.
4. Membuat data produk baru apabila kode produk belum tersedia.
5. Menjalankan proses penyimpanan dalam transaksi database.
6. Menghitung jumlah data yang dibuat dan diperbarui.

**Respons:** HTTP 200 jika proses sinkronisasi berhasil. Respons memuat jumlah data yang dibuat (`created`), diperbarui (`updated`), dan total data yang diproses (`total`).

#### 6.4.2 Sinkronisasi Data Promo

- **Method:** POST
- **Endpoint:** `/api/v1/sinkronisasi/promo`
- **Role:** `admin_pusat`
- **Controller:** `SinkronisasiMasterDataController@syncPromo`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `data` | Array | Wajib, minimal satu data promo |
| `data.*.kode_promo` | String | Wajib, maksimal 30 karakter |
| `data.*.deskripsi` | String | Wajib, maksimal 255 karakter |
| `data.*.jenis_potongan` | String | Wajib, `persen` atau `nominal` |
| `data.*.nilai` | Numeric | Wajib, lebih dari 0 |
| `data.*.tgl_mulai` | Date | Wajib |
| `data.*.tgl_selesai` | Date | Wajib, sama dengan atau setelah tanggal mulai |
| `data.*.status` | String | Wajib, maksimal 20 karakter |

Untuk promo dengan jenis potongan `persen`, nilai potongan tidak boleh melebihi 100.

**Proses:**

1. Memvalidasi data promo.
2. Memeriksa promo berdasarkan `kode_promo`.
3. Memperbarui data promo apabila kode telah tersedia.
4. Membuat data promo baru apabila kode belum tersedia.
5. Menjalankan proses penyimpanan dalam transaksi database.
6. Menghitung jumlah data yang dibuat dan diperbarui.

**Respons:** HTTP 200 jika proses sinkronisasi berhasil. Respons memuat jumlah data yang dibuat (`created`), diperbarui (`updated`), dan total data yang diproses (`total`).

**Catatan:** Kedua endpoint sinkronisasi tersebut menerima data master melalui API aplikasi cabang. Pada implementasi yang diperiksa, belum ditemukan mekanisme pemanggilan endpoint ini secara langsung dari sistem pusat maupun pengaturan jadwal sinkronisasi otomatis.

### 6.5 Modul Kas dan Laporan Harian (M4)

#### 6.5.1 Melihat Transaksi Kas

- **Method:** GET
- **Endpoint:** `/api/v1/kas`
- **Role:** `admin_cabang`, `manajer_cabang`, `admin_pusat`
- **Controller:** `KasController@index`

Endpoint ini menampilkan daftar transaksi kas dengan relasi data cabang, karyawan, dan faktur.

**Parameter Query Opsional:**

| Parameter | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Harus merujuk pada cabang yang tersedia |
| `tanggal` | Date | Memfilter transaksi berdasarkan tanggal |
| `jenis` | String | `masuk`, `keluar`, atau `setoran` |

Data transaksi ditampilkan berdasarkan tanggal secara menurun.

**Respons:** HTTP 200 dengan daftar transaksi kas sesuai filter yang diberikan.

#### 6.5.2 Mencatat Transaksi Kas

- **Method:** POST
- **Endpoint:** `/api/v1/kas`
- **Role:** `admin_cabang`
- **Controller:** `KasController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Wajib, harus terdaftar pada tabel cabang |
| `id_karyawan` | Integer | Wajib, harus terdaftar pada tabel karyawan |
| `jenis` | String | Wajib, `keluar` atau `setoran` |
| `jumlah` | Numeric | Wajib, lebih dari 0 |
| `tanggal` | Date | Opsional |
| `keterangan` | String | Opsional, maksimal 255 karakter |

**Proses:**

1. Memvalidasi data transaksi kas.
2. Memastikan karyawan aktif dan berasal dari cabang yang dipilih.
3. Menyimpan transaksi kas dengan `id_faktur` bernilai `null`.

Transaksi kas masuk yang berasal dari pembayaran faktur dibuat secara otomatis ketika sales melakukan verifikasi pembayaran. Oleh karena itu, endpoint POST ini hanya menerima transaksi kas keluar atau setoran.

**Respons:** HTTP 201 jika transaksi kas berhasil dicatat.

#### 6.5.3 Melihat Detail Transaksi Kas

- **Method:** GET
- **Endpoint:** `/api/v1/kas/{id}`
- **Role:** `admin_cabang`, `manajer_cabang`, `admin_pusat`
- **Controller:** `KasController@show`

Endpoint ini menampilkan detail transaksi kas berdasarkan ID, termasuk relasi cabang, karyawan, dan faktur.

**Respons:** HTTP 200 jika data ditemukan.

#### 6.5.4 Melihat Daftar Laporan Harian

- **Method:** GET
- **Endpoint:** `/api/v1/laporan-harian`
- **Role:** `admin_cabang`, `manajer_cabang`, `admin_pusat`
- **Controller:** `LaporanHarianController@index`

**Parameter Query Opsional:**

| Parameter | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Memfilter berdasarkan cabang |
| `tanggal` | Date | Memfilter berdasarkan tanggal laporan |
| `jenis_laporan` | String | `penjualan`, `stok`, `kas_masuk`, atau `kas_keluar` |

Endpoint menampilkan daftar laporan beserta relasi cabang dan karyawan berdasarkan tanggal secara menurun.

**Respons:** HTTP 200 dengan daftar laporan sesuai filter.

#### 6.5.5 Membuat Laporan Harian

- **Method:** POST
- **Endpoint:** `/api/v1/laporan-harian`
- **Role:** `admin_cabang`
- **Controller:** `LaporanHarianController@store`

**Parameter Request:**

| Field | Tipe | Ketentuan |
|---|---|---|
| `id_cabang` | Integer | Wajib, harus terdaftar pada tabel cabang |
| `id_karyawan` | Integer | Wajib, harus terdaftar pada tabel karyawan |
| `tanggal` | Date | Wajib |
| `jenis_laporan` | String | Wajib, `penjualan`, `stok`, `kas_masuk`, atau `kas_keluar` |

**Proses:**

1. Memvalidasi data laporan.
2. Memastikan karyawan aktif dan berasal dari cabang yang dipilih.
3. Memeriksa laporan berdasarkan kombinasi cabang, tanggal, dan jenis laporan.
4. Membuat laporan baru dengan status `belum_kirim` apabila belum tersedia.

**Respons:**

- HTTP 201 jika laporan berhasil dibuat.
- HTTP 409 jika laporan dengan kombinasi cabang, tanggal, dan jenis yang sama sudah tersedia.

#### 6.5.6 Melihat Detail dan Ringkasan Laporan Harian

- **Method:** GET
- **Endpoint:** `/api/v1/laporan-harian/{id}`
- **Role:** `admin_cabang`, `manajer_cabang`, `admin_pusat`
- **Controller:** `LaporanHarianController@show`

Endpoint menampilkan data laporan beserta ringkasan yang dihitung berdasarkan jenis laporan.

Ringkasan yang disediakan meliputi:

- **Penjualan:** jumlah dan total nilai faktur serta daftar transaksi penjualan.
- **Stok:** ringkasan jumlah stok, jenis stok, batas minimum, dan daftar data stok.
- **Kas masuk:** jumlah transaksi dan total kas masuk pada tanggal laporan.
- **Kas keluar:** jumlah transaksi dan total kas keluar pada tanggal laporan.

Ringkasan dihitung ketika endpoint dipanggil, bukan disimpan sebagai hasil perhitungan tetap pada laporan.

**Respons:** HTTP 200 jika laporan ditemukan.

#### 6.5.7 Mengirim Laporan Harian ke Sistem Pusat

- **Method:** PATCH
- **Endpoint:** `/api/v1/laporan-harian/{id}/kirim`
- **Role:** `admin_cabang`
- **Controller:** `LaporanHarianController@kirim`

**Proses:**

1. Memeriksa keberadaan laporan.
2. Memastikan laporan belum pernah dikirim.
3. Mengirim `id_laporan`, `id_cabang`, dan `tanggal` ke Central Mock melalui `IntegrasiPusatService`.
4. Memperbarui status laporan menjadi `terkirim` dan mengisi `tgl_kirim` apabila pengiriman berhasil.

**Respons:**

- HTTP 200 jika laporan berhasil dikirim.
- HTTP 422 jika laporan telah dikirim sebelumnya.
- HTTP 502 jika pengiriman ke sistem pusat gagal.

## 7. Kesesuaian Endpoint dengan Spesifikasi

Bagian ini membandingkan kebutuhan endpoint berdasarkan spesifikasi B1–B16 dan A1–A9 dengan implementasi aktual pada repository.

### 7.1 Pemetaan Endpoint Internal B1–B16

| Kode | Kebutuhan pada Spesifikasi | Implementasi Aktual | Status |
|---|---|---|---|
| B1 | POST `/faktur` | POST `/api/v1/faktur` | Sesuai |
| B2 | GET `/faktur/{id}` | Belum tersedia | Belum diimplementasikan |
| B3 | POST `/faktur/{id}/pembayaran` | POST `/api/v1/faktur/{id}/pembayaran` | Sesuai |
| B4 | GET `/stok?id_produk=` | Belum tersedia | Belum diimplementasikan |
| B5 | POST `/stok/mutasi` | POST `/api/v1/mutasi-stok` | Path berbeda |
| B6 | POST `/po-pengantaran` | PO dibuat otomatis saat faktur dibuat | Mekanisme berbeda |
| B7 | PATCH `/pengantaran/{id}/status` | Belum tersedia | Belum diimplementasikan |
| B8 | POST `/garansi` | Belum tersedia | Belum diimplementasikan |
| B9 | POST `/kas` | POST `/api/v1/kas` | Implementasi terbatas |
| B10 | GET `/laporan/harian?tanggal=` | GET `/api/v1/laporan-harian` | Path berbeda |
| B11 | POST `/absensi` | Belum tersedia | Belum diimplementasikan |
| B12 | GET `/pelanggan` | GET `/api/v1/pelanggan` | Sesuai |
| B13 | POST `/pelanggan` | POST `/api/v1/pelanggan` | Sesuai |
| B14 | GET `/produk` | Belum tersedia | Belum diimplementasikan |
| B15 | GET `/promo` | Belum tersedia | Belum diimplementasikan |
| B16 | GET `/cabang` | GET `/api/v1/cabang` | Sesuai |

Keterangan:

- **Sesuai:** Endpoint tersedia dengan fungsi utama yang sesuai kebutuhan.
- **Path berbeda:** Fungsi tersedia, tetapi alamat endpoint berbeda dari spesifikasi.
- **Mekanisme berbeda:** Kebutuhan fungsi ditangani melalui proses lain.
- **Implementasi terbatas:** Endpoint tersedia, tetapi belum mencakup seluruh kebutuhan yang tercantum pada spesifikasi.
- **Belum diimplementasikan:** Endpoint belum ditemukan pada route aplikasi.

Pada B9, endpoint kas hanya menerima transaksi `keluar` dan `setoran`. Pencatatan kas masuk dilakukan secara otomatis melalui proses verifikasi pembayaran faktur.

Pada B10, endpoint laporan harian menggunakan path `/laporan-harian` dan mendukung filter tanggal melalui parameter query `tanggal`.

### 7.2 Pemetaan Integrasi Sistem Pusat A1–A9

| Kode | Kebutuhan Integrasi | Implementasi Aktual | Status |
|---|---|---|---|
| A1 | Pengiriman laporan harian | POST Central Mock `/api/v1/laporan-harian` | Tersedia |
| A2 | Pengiriman transaksi | POST Central Mock `/api/v1/transaksi` | Tersedia |
| A3 | Integrasi kas | Belum ditemukan integrasi eksternal | Belum diimplementasikan |
| A4 | Sinkronisasi produk | POST internal `/api/v1/sinkronisasi/produk` | Endpoint penerima tersedia |
| A5 | Sinkronisasi promo | POST internal `/api/v1/sinkronisasi/promo` | Endpoint penerima tersedia |
| A6 | Pengiriman barang | Belum ditemukan endpoint integrasi | Belum diimplementasikan |
| A7 | Konfirmasi penerimaan barang | POST Central Mock `/api/v1/penerimaan-barang` | Tersedia |
| A8 | Mutasi stok antarcabang | Belum ditemukan endpoint khusus | Belum diimplementasikan |
| A9 | Pemeriksaan pembayaran oleh pusat | PATCH internal `/api/v1/pembayaran/{id}/cek-pusat` | Pemeriksaan lokal tersedia |

Keterangan:

- A1, A2, dan A7 merupakan komunikasi keluar dari aplikasi cabang menuju Central Mock.
- A4 dan A5 merupakan endpoint internal aplikasi cabang untuk menerima data master produk dan promo. Mekanisme pemanggilan dari sistem pusat belum ditemukan.
- A9 hanya memperbarui status pemeriksaan pembayaran pada database aplikasi cabang. Pemanggilan layanan eksternal belum ditemukan.

## 8. Batasan Implementasi dan Catatan Pengembangan

Berdasarkan pemeriksaan route, controller, service, dan Central Mock, terdapat beberapa batasan implementasi yang perlu diperhatikan:

1. **Endpoint belum tersedia:** Beberapa kebutuhan pada spesifikasi, seperti melihat detail faktur, melihat stok, mengelola pengantaran, klaim garansi, absensi, serta melihat produk dan promo belum memiliki endpoint yang ditemukan pada implementasi saat ini.
2. **Pengelolaan stok:** Pencatatan penerimaan barang dan mutasi stok sudah tersedia. Namun, belum ditemukan endpoint khusus untuk melihat stok maupun melakukan pemindahan stok antarcabang.
3. **Pengelolaan kas:** Transaksi kas keluar dan setoran dapat dicatat melalui endpoint kas. Kas masuk dibuat secara otomatis ketika pembayaran faktur diverifikasi oleh sales.
4. **Sinkronisasi master data:** Endpoint penerimaan data produk dan promo sudah tersedia, tetapi pemanggilan otomatis dari sistem pusat belum ditemukan.
5. **Penyimpanan data pusat:** Central Mock saat ini hanya memvalidasi data dan memberikan respons penerimaan. Data yang diterima belum disimpan ke database pusat.
6. **Penanganan kegagalan integrasi:** Belum terdapat antrean pengiriman dan mekanisme retry otomatis. Penanganan kegagalan dilakukan oleh controller masing-masing.
7. **Autentikasi integrasi eksternal:** Belum ditemukan mekanisme autentikasi khusus antara aplikasi cabang dan Central Mock.

Batasan tersebut menjadi catatan untuk pengembangan selanjutnya agar implementasi dapat disesuaikan dengan kebutuhan sistem dan spesifikasi yang telah ditetapkan.