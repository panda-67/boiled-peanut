# Kacang Rebus — Ledger-Based Inventory System

Kacang Rebus adalah aplikasi pencatatan stok dan penjualan berbasis **ledger (transaction-based)**.  
Sistem ini **tidak menyimpan stok sebagai state mutable**, melainkan menghitungnya dari akumulasi transaksi yang **immutable dan dapat diaudit**.

Pendekatan ini memastikan sistem:

- Audit-friendly  
- Konsisten secara akuntansi  
- Aman terhadap race condition  
- Jelas secara historis dan forensik  

---

## Prinsip Inti Sistem

1. ❌ Tidak ada update langsung ke kolom `stock`
2. ✅ Semua pergerakan dicatat sebagai ledger (transaction)
3. ✅ Nilai stok = `SUM(quantity)` terfilter lokasi & tipe
4. ✅ Barang keluar **selalu bernilai negatif**
5. ✅ Semua proses bisnis **wajib lewat Service Layer**
6. ✅ Status entity dikontrol dengan **Finite State Machine (FSM)**

---

## Entitas Utama

### Product

Produk jadi yang dihasilkan dari produksi dan dijual di titik penjualan.

- Stok dihitung dari `product_transactions`
- Stok **selalu kontekstual terhadap lokasi**

```php
$product->availableAt($location);
$product->reservedAt($location);
```

---

### Material

Bahan baku produksi.

- Menggunakan `stock_movements`
- Hanya material `is_stocked = true` yang memengaruhi stok

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

Produksi **selalu terjadi di Production Location**, bukan Sale Point.

---

### Sale

Mewakili transaksi penjualan di **Sale Point**.

Sale mengikuti **FSM ketat** dan tidak boleh dimodifikasi sembarangan.

#### Sale FSM

```
DRAFT → CONFIRMED → SETTLED
```

---

## FSM: Sale Lifecycle

### 1. DRAFT

- Status awal
- Sale item boleh diubah
- Tidak ada efek ke stok

---

### 2. CONFIRMED

Dieksekusi oleh `ConfirmSaleService::confirm()`.

Syarat:

- User memiliki **active location**
- Location bertipe `SALE_POINT`
- Sale masih `DRAFT`

Efek:

- Sale item dikunci
- `ProductTransaction::RESERVE` dicatat
- Stok available berkurang, reserved bertambah

---

### 3. SETTLED

Dieksekusi oleh `SettlementService::settle()`.

Syarat:

- Sale sudah `CONFIRMED`

Efek:

- Pembayaran dicatat
- Status final
- Sale menjadi **immutable total**

---

## Ledger / Tabel Transaksi

### StockMovement (Material Ledger)

Digunakan untuk semua pergerakan **bahan baku**.

| Kolom | Keterangan |
|------|-----------|
| material_id | ID material |
| quantity | + masuk, − keluar |
| type | IN / OUT |
| reference_type | INITIAL / PRODUCTION |
| reference_id | ID referensi |

---

### ProductTransaction (Product Ledger)

Digunakan untuk semua pergerakan **produk jadi**.

| Kolom | Keterangan |
|------|-----------|
| product_id | ID produk |
| location_id | Lokasi stok |
| quantity | + masuk, − keluar |
| type | IN / RESERVE / OUT |
| reference_type | PRODUCTION / TRANSFER / SALE |
| reference_id | ID referensi |

---

## Guard & Policy

### LocationGuard

Digunakan di Service Layer untuk memastikan konteks lokasi valid.

```php
LocationGuard::ensureSalePoint($user);
```

Rule:

- User **harus punya active location**
- Location harus bertipe `SALE_POINT`

---

### Policy (Authorization)

Digunakan di Controller / UI layer.

- Mengatur **siapa boleh mengeksekusi**
- Tidak menyentuh domain logic

---

## Aturan Wajib (Kontrak Sistem)

- ❌ Jangan update stok langsung
- ❌ Jangan bypass Service Layer
- ❌ Jangan ubah Sale setelah CONFIRMED
- ✅ Semua transaksi wajib punya reference
- ✅ Ledger adalah single source of truth

---

Dokumentasi ini adalah **kontrak sistem**.  
Jika implementasi melanggar dokumen ini, maka implementasi **salah**, bukan dokumentasinya.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
