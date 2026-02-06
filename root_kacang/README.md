# Kacang Rebus — Ledger-Based Inventory System

Kacang Rebus adalah aplikasi pencatatan stok dan biaya produksi berbasis **ledger (transaction-based)**.
Aplikasi ini **tidak menyimpan stok dalam kolom**, melainkan menghitung stok dari akumulasi transaksi.

Pendekatan ini memastikan sistem:

- Audit-friendly
- Konsisten secara akuntansi
- Tahan terhadap race condition
- Mudah ditelusuri secara historis

---

## Prinsip Inti

1. Tidak ada update langsung ke kolom `stock`
2. Semua pergerakan barang dicatat sebagai transaksi (ledger)
3. Nilai stok = `SUM(quantity)`
4. Barang keluar **selalu bernilai negatif**
5. Semua proses bisnis lewat Service Layer

---

## Entitas Utama

### Product

Produk jadi yang dihasilkan dari produksi dan dijual ke pelanggan.

- Stok dihitung dari `product_transactions`
- Tidak ada kolom `stock` aktif

```php
$product->stock();
```

---

### Material

Bahan baku produksi.

- Menggunakan `stock_movements`
- Bisa bertipe `is_stocked = true`

```php
$material->stock();
```

---

### Production

Mewakili satu proses produksi.

Field penting:

- `date`
- `product_id`
- `output_quantity`
- `total_cost`
- `status` (`draft`, `completed`)

---

### Sale

Transaksi penjualan produk jadi.

---

## Ledger / Tabel Transaksi

### StockMovement (Material Ledger)

Digunakan untuk semua pergerakan **bahan baku**.

| Kolom | Keterangan |
|------|-----------|
| material_id | ID material |
| quantity | + masuk, − keluar |
| type | in / out |
| reference_type | INITIAL / PRODUCTION |
| reference_id | ID referensi |

---

### ProductTransaction (Product Ledger)

Digunakan untuk semua pergerakan **produk jadi**.

| Kolom | Keterangan |
|------|-----------|
| product_id | ID produk |
| quantity | + masuk, − keluar |
| type | in / out |
| reference_type | PRODUCTION / SALE |
| reference_id | ID produksi / sale |

---

## Alur Produksi

Eksekusi dilakukan oleh `ProductionService::execute()`.

Langkah:

1. Validasi status `draft`
2. Catat StockMovement OUT
3. Hitung biaya
4. Catat ProductTransaction IN
5. Status menjadi `completed`

---

## Alur Penjualan

Eksekusi dilakukan oleh `ConfirmSaleService::confirm()`.

Langkah:

1. Ambil sale items
2. Catat ProductTransaction OUT
3. Stok berkurang via ledger

---

## Aturan Wajib

- Jangan update stok langsung
- Semua keluar bernilai negatif
- Semua transaksi wajib punya reference
- Service layer adalah satu-satunya pintu eksekusi

---

Dokumentasi ini adalah kontrak sistem.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
