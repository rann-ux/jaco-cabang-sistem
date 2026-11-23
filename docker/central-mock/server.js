const express = require("express");

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());

// Health check
app.get("/health", (req, res) => {
    res.status(200).json({
        success: true,
        service: "Jaco Central Mock Service",
        message: "Central Mock Service berjalan."
    });
});

// Menerima data transaksi dari cabang
app.post("/api/v1/transaksi", (req, res) => {
    const { id_transaksi, id_cabang, total, tanggal } = req.body;

    if (!id_transaksi || !id_cabang || total === undefined || !tanggal) {
        return res.status(422).json({
            success: false,
            message: "Data transaksi belum lengkap."
        });
    }

    return res.status(201).json({
        success: true,
        message: "Data transaksi berhasil diterima oleh sistem pusat.",
        data: {
            id_transaksi,
            id_cabang,
            total,
            tanggal,
            status_sinkronisasi: "diterima"
        }
    });
});

// Menerima laporan harian dari cabang
app.post("/api/v1/laporan-harian", (req, res) => {
    const { id_laporan, id_cabang, tanggal } = req.body;

    if (!id_laporan || !id_cabang || !tanggal) {
        return res.status(422).json({
            success: false,
            message: "Data laporan harian belum lengkap."
        });
    }

    return res.status(201).json({
        success: true,
        message: "Laporan harian berhasil diterima oleh sistem pusat.",
        data: {
            id_laporan,
            id_cabang,
            tanggal,
            status_sinkronisasi: "diterima"
        }
    });
});

// Menerima konfirmasi penerimaan barang dari cabang
app.post("/api/v1/penerimaan-barang", (req, res) => {
    const { id_penerimaan, id_cabang, tanggal } = req.body;

    if (!id_penerimaan || !id_cabang || !tanggal) {
        return res.status(422).json({
            success: false,
            message: "Data penerimaan barang belum lengkap."
        });
    }

    return res.status(201).json({
        success: true,
        message: "Konfirmasi penerimaan barang berhasil diterima.",
        data: {
            id_penerimaan,
            id_cabang,
            tanggal,
            status_sinkronisasi: "diterima"
        }
    });
});

app.listen(PORT, "0.0.0.0", () => {
    console.log(`Jaco Central Mock Service berjalan pada port ${PORT}`);
});
