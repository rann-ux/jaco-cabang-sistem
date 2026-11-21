USE jaco_cabang;
SELECT 'Cabang' AS tabel, COUNT(*) AS jumlah_baris FROM Cabang
UNION ALL SELECT 'Produk', COUNT(*) FROM Produk
UNION ALL SELECT 'Promo', COUNT(*) FROM Promo
UNION ALL SELECT 'Pelanggan', COUNT(*) FROM Pelanggan
UNION ALL SELECT 'Stok', COUNT(*) FROM Stok;

-- Skenario A8: stok produk id 1 di seluruh cabang (Jambi = 0, cabang lain tersedia)
SELECT c.nama_cabang, p.nama_produk, s.jumlah, s.batas_minimum
FROM Stok s JOIN Cabang c ON c.id_cabang=s.id_cabang JOIN Produk p ON p.id_produk=s.id_produk
WHERE s.id_produk = 1;

-- Peringatan stok minimum
SELECT c.nama_cabang, p.nama_produk, s.jumlah, s.batas_minimum
FROM Stok s JOIN Cabang c ON c.id_cabang=s.id_cabang JOIN Produk p ON p.id_produk=s.id_produk
WHERE s.jumlah <= s.batas_minimum;
