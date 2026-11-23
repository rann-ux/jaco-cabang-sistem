# Jaco Cabang Sistem

Jaco Cabang Sistem merupakan aplikasi berbasis API untuk mendukung pengelolaan operasional cabang, meliputi data cabang, pelanggan, produk, stok, transaksi penjualan, pembayaran, penerimaan barang, kas, dan laporan harian. Sistem dirancang untuk mendukung komunikasi antara aplikasi cabang dan sistem pusat melalui mekanisme integrasi API.

## Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12 |
| Bahasa pemrograman | PHP 8.2 |
| Database | MySQL 8.4 |
| API Gateway | Kong 3.9 |
| Autentikasi | Keycloak 26 dan JWT |
| Central Mock | Node.js 20 dan Express 4 |
| Frontend asset builder | Vite 7 dan Tailwind CSS 4 |
| Containerization | Docker dan Docker Compose |

## Arsitektur Sistem

Sistem dijalankan menggunakan Docker Compose dengan beberapa layanan yang saling terhubung melalui jaringan internal Docker.

- **Laravel Application:** menangani proses bisnis dan menyediakan endpoint API.
- **Nginx:** menjadi web server untuk aplikasi Laravel.
- **MySQL:** menyimpan data operasional cabang.
- **Keycloak:** mengelola autentikasi pengguna dan penerbitan token JWT.
- **Kong API Gateway:** menjadi pintu masuk permintaan API sebelum diteruskan ke aplikasi Laravel.
- **Central Mock:** menyimulasikan sistem pusat untuk pengujian integrasi data.

## Persyaratan Sistem

Sebelum menjalankan proyek, pastikan perangkat telah memiliki:

- Git.
- PHP 8.2 atau lebih baru.
- Composer 2.x.
- Node.js 22 dan npm.
- Docker Desktop dengan Docker Compose.
- Koneksi internet untuk mengunduh dependensi dan image Docker.

## Instalasi dan Konfigurasi

### 1. Clone Repository

Unduh repository proyek menggunakan perintah:

```bash
git clone https://github.com/rann-ux/jaco-cabang-sistem.git
```

Masuk ke direktori proyek:

```bash
cd jaco-cabang-sistem
```

### 2. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`.

```bash
cp .env.example .env
```

Pada Windows PowerShell, gunakan:

```powershell
Copy-Item .env.example .env
```

Sesuaikan konfigurasi database, Keycloak, dan Central Mock pada file `.env`.

Contoh konfigurasi:

```env
APP_NAME="Jaco Cabang Sistem"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=jaco_db
DB_USERNAME=jaco_user
DB_PASSWORD=local_app_password

MYSQL_PASSWORD=local_app_password
MYSQL_ROOT_PASSWORD=local_root_password

KEYCLOAK_DB_PASSWORD=local_keycloak_password
KEYCLOAK_ADMIN_PASSWORD=local_keycloak_admin_password

KEYCLOAK_BASE_URL=http://keycloak:8080
KEYCLOAK_REALM=jaco-cabang
KEYCLOAK_ISSUER=http://localhost:8081/realms/jaco-cabang
KEYCLOAK_JWKS_URI=http://keycloak:8080/realms/jaco-cabang/protocol/openid-connect/certs
KEYCLOAK_CLIENT_ID=jaco-cabang-api

CENTRAL_MOCK_URL=http://central-mock:3000
```

Nilai password di atas merupakan contoh untuk pengembangan lokal. Gunakan kredensial yang sesuai dengan konfigurasi masing-masing lingkungan dan jangan menggunakan password contoh untuk produksi.

### 3. Instalasi Dependensi PHP

Jalankan perintah berikut untuk memasang dependensi Laravel:

```bash
composer install
```

### 4. Instalasi Dependensi Frontend

Pasang dependensi frontend:

```bash
npm install
```

Bangun aset frontend menggunakan Vite:

```bash
npm run build
```

### 5. Membuat Application Key

Jalankan perintah:

```bash
php artisan key:generate
```

Perintah tersebut akan menghasilkan application key dan menyimpannya pada file `.env`.

### 6. Menjalankan Docker Compose

Bangun image dan jalankan seluruh layanan:

```bash
docker compose up -d --build
```

Periksa status container:

```bash
docker compose ps
```

Pastikan layanan Laravel, Nginx, MySQL, Keycloak, Kong, dan Central Mock berjalan.

### 7. Menjalankan Database Migration dan Seeder

Jalankan migration untuk membuat tabel database:

```bash
docker compose exec app php artisan migrate
```

Kemudian jalankan seeder untuk memasukkan data awal:

```bash
docker compose exec app php artisan db:seed
```

Seeder menyediakan data awal berupa cabang, karyawan, pelanggan, produk, dan stok.

Untuk memeriksa status migration:

```bash
docker compose exec app php artisan migrate:status
```

### 8. Mengakses Aplikasi

Setelah seluruh layanan berjalan, aplikasi dapat diakses melalui alamat berikut:

| Layanan | Alamat |
|---|---|
| Laravel Application | http://localhost:8080 |
| Kong API Gateway | http://localhost:8000 |
| Keycloak | http://localhost:8081 |
| Central Mock Health Check | Internal Docker: `http://central-mock:3000/health` |

## Modul dan Endpoint API

Seluruh endpoint menggunakan prefix `/api/v1` dan format pertukaran data JSON. Permintaan API dilindungi menggunakan token JWT melalui middleware autentikasi Keycloak dan pemeriksaan role.

| Modul | Method | Endpoint | Fungsi |
|---|---|---|---|
| Cabang | GET | `/api/v1/cabang` | Menampilkan data cabang |
| Cabang | POST | `/api/v1/cabang` | Menambahkan cabang |
| Cabang | GET | `/api/v1/cabang/{id}` | Melihat detail cabang |
| Cabang | PUT | `/api/v1/cabang/{id}` | Memperbarui cabang |
| Cabang | DELETE | `/api/v1/cabang/{id}` | Menghapus cabang |
| Faktur | POST | `/api/v1/faktur` | Membuat faktur penjualan |
| Pembayaran | POST | `/api/v1/faktur/{id}/pembayaran` | Mencatat pembayaran |
| Kas | GET | `/api/v1/kas` | Menampilkan transaksi kas |
| Kas | POST | `/api/v1/kas` | Mencatat transaksi kas |
| Kas | GET | `/api/v1/kas/{id}` | Melihat detail transaksi kas |
| Laporan Harian | GET | `/api/v1/laporan-harian` | Menampilkan laporan |
| Laporan Harian | POST | `/api/v1/laporan-harian` | Membuat laporan |
| Laporan Harian | GET | `/api/v1/laporan-harian/{id}` | Melihat detail laporan |
| Laporan Harian | PATCH | `/api/v1/laporan-harian/{id}/kirim` | Mengirim laporan ke pusat |
| Mutasi Stok | POST | `/api/v1/mutasi-stok` | Mencatat mutasi stok |
| Pelanggan | GET | `/api/v1/pelanggan` | Menampilkan pelanggan |
| Pelanggan | POST | `/api/v1/pelanggan` | Menambahkan pelanggan |
| Pelanggan | GET | `/api/v1/pelanggan/{id}` | Melihat detail pelanggan |
| Pelanggan | PUT | `/api/v1/pelanggan/{id}` | Memperbarui pelanggan |
| Pelanggan | DELETE | `/api/v1/pelanggan/{id}` | Menghapus pelanggan |
| Pembayaran | PATCH | `/api/v1/pembayaran/{id}/cek-pusat` | Memeriksa pembayaran dari pusat |
| Pembayaran | PATCH | `/api/v1/pembayaran/{id}/verifikasi-sales` | Verifikasi pembayaran oleh Sales |
| Penerimaan Barang | POST | `/api/v1/penerimaan-barang` | Mencatat penerimaan barang |
| Sinkronisasi | POST | `/api/v1/sinkronisasi/produk` | Sinkronisasi data produk |
| Sinkronisasi | POST | `/api/v1/sinkronisasi/promo` | Sinkronisasi data promosi |

## Autentikasi dan Hak Akses

Sistem menggunakan Keycloak untuk mengelola identitas pengguna dan menerbitkan token JWT. Token dikirimkan pada setiap permintaan API melalui header `Authorization` dengan format Bearer Token.

Contoh:

```http
Authorization: Bearer <access_token>
```

Laravel memvalidasi token JWT dan memeriksa role pengguna melalui middleware. Pengguna hanya dapat mengakses endpoint sesuai dengan hak akses yang diberikan.

Role yang digunakan dalam sistem:

| Role | Keterangan |
|---|---|
| `sales_cabang` | Pengguna dengan tugas operasional penjualan |
| `admin_cabang` | Pengelola administrasi cabang |
| `gudang_cabang` | Pengelola stok dan penerimaan barang |
| `manajer_cabang` | Pengelola dan pemantau operasional cabang |
| `admin_pusat` | Pengelola data dan sinkronisasi dari sistem pusat |

## Akun Pengujian

Berikut akun pengujian yang disediakan pada konfigurasi realm Keycloak:

| Username | Role |
|---|---|
| `test.admin` | Admin Cabang |
| `test.sales` | Sales |
| `test.gudang` | Petugas Gudang |
| `test.manajer` | Manajer Cabang |
| `test.pusat` | Admin Pusat |

Password akun pengujian ditetapkan melalui konfigurasi pengguna pada Keycloak. Akun tersebut tidak dibuat oleh Laravel DatabaseSeeder. Untuk memperoleh kredensial pengujian, gunakan konfigurasi Keycloak yang disediakan oleh administrator proyek.

## Integrasi Sistem Pusat

Aplikasi menggunakan Central Mock untuk menyimulasikan komunikasi antara sistem cabang dan sistem pusat.

Endpoint yang tersedia pada Central Mock:

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/health` | Memeriksa layanan |
| POST | `/api/v1/transaksi` | Menerima data transaksi |
| POST | `/api/v1/laporan-harian` | Menerima laporan harian |
| POST | `/api/v1/penerimaan-barang` | Menerima konfirmasi penerimaan barang |

Central Mock melakukan validasi sederhana dan memberikan respons penerimaan data. Layanan ini digunakan untuk pengujian integrasi dan belum menyimpan data ke database sistem pusat yang sebenarnya.

## Perintah Docker yang Berguna

Melihat status seluruh container:

```bash
docker compose ps
```

Melihat log seluruh layanan:

```bash
docker compose logs -f
```

Melihat log aplikasi Laravel:

```bash
docker compose logs -f app
```

Menghentikan layanan:

```bash
docker compose stop
```

Menjalankan kembali layanan:

```bash
docker compose start
```

Menjalankan ulang container:

```bash
docker compose restart
```

Menghentikan dan menghapus container tanpa menghapus volume database:

```bash
docker compose down
```

**Catatan:** Hindari penggunaan `docker compose down -v` karena perintah tersebut menghapus volume, termasuk data MySQL.

## Repository dan Branch

Repository: https://github.com/rann-ux/jaco-cabang-sistem

Pengembangan proyek menggunakan branch utama dan branch pengembangan fitur untuk memisahkan pekerjaan berdasarkan kebutuhan implementasi.

## Catatan Pengembangan

- Sistem berfokus pada layanan backend berbasis REST API.
- Autentikasi dan otorisasi menggunakan Keycloak, JWT, dan middleware Laravel.
- Kong digunakan sebagai API Gateway.
- Central Mock digunakan untuk simulasi integrasi sistem pusat.
- Pengujian endpoint dapat dilakukan menggunakan Postman atau aplikasi pengujian API sejenis.