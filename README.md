# Dokumentasi Proyek UTS: Pengembangan Microservice Order & Integration Services

dokumentasi ini disusun secara mandiri untuk menjelaskan alur kerja, fungsi, serta cara pengujian dari sistem *order-service* yang telah dibangun pada tugas Ujian Tengah Semester (UTS) mata kuliah Microservices.

Sistem ini dirancang untuk menyelesaikan permasalahan pembelian pada sebuah toko online berbasis *e-commerce*, di mana sebelumnya pengguna hanya bisa memilih produk dan memasukkannya ke keranjang belanja, namun belum bisa menyelesaikan proses transaksi pembayaran (*checkout*).

---

##  1. Penjelasan Sederhana & Arsitektur Sistem

### Apa Fungsi Utama Sistem Ini?
Secara sederhana, **`order-service`** ini berfungsi layaknya mesin kasir digital. Ketika pembeli menekan tombol *checkout* di aplikasi, layanan ini bertugas untuk:
1. Memeriksa apa saja barang yang sedang ada di dalam keranjang belanja pembeli tersebut melalui *cart-service*[cite: 2].
2. Memastikan ke stok barang di gudang melalui *product-service* apakah barangnya masih tersedia dan mencukupi[cite: 2].
3. Mengunci harga dan nama barang pada saat itu juga (**Snapshot Price**) agar jika di kemudian hari harga barang di toko berubah, harga pada nota pesanan pembeli yang sudah terlanjur dibuat tidak akan ikut berubah[cite: 2].
4. Menyimpan data transaksi pesanan ke database lokal milik *order-service* secara aman, lalu secara otomatis mengosongkan keranjang belanja pembeli[cite: 2].

### Bagaimana Alur Komunikasinya?
Sistem ini dibangun menggunakan konsep **Arsitektur Berlapis (*Layered Architecture*)** agar fungsi dan tanggung jawab setiap bagian kodenya terpisah dengan jelas dan rapi:
- **Routes & Controller**: Menerima permintaan (*request*) dari luar dan mengatur jalur respons keluar[cite: 2].
- **Form Request**: Berfungsi sebagai pos pemeriksaan awal untuk menyaring data yang masuk agar sesuai dengan format yang benar (validasi)[cite: 2].
- **Service Layer**: Tempat di mana seluruh logika bisnis utama diproses (seperti aturan cek stok dan perhitungan total harga)[cite: 2].
- **Client Layer**: Perantara khusus yang bertugas melakukan panggilan jaringan internet secara internal (*HTTP Request*) antar-mikroservis (`cart-service` dan `product-service`) dengan batasan waktu maksimal 5 detik (*timeout*) demi mencegah aplikasi mengalami *hang* jika layanan lain sedang bermasalah[cite: 2].
- **Database & Transactions (`DB::transaction`)**: Memastikan bahwa proses penyimpanan data pesanan bersifat mutlak (*atomic*)—artinya jika di tengah jalan ada satu proses yang gagal, data tidak akan setengah-setengah tersimpan, melainkan dibatalkan seluruhnya agar tidak terjadi kerusakan data[cite: 2].

---

##  2. Panduan Menjalankan Sistem Dari Awal 

Agar dapat menguji sistem ini dengan mudah tanpa konfigurasi yang rumit, seluruh layanan sudah dikemas di dalam wadah kontainer Docker

### Langkah-Langkah Eksekusi:
1. Pastikan aplikasi **Docker Desktop** di komputer Anda sudah dalam keadaan aktif.
2. Buka terminal atau *command prompt*, lalu lakukan *clone* repositori ini dan masuk ke dalam folder proyek[cite: 2]:
   git clone https://github.com/Ciciindriani-dot/mi-pdsku-uts.git
   cd mi-pdsku-uts

3. Jalankan perintah Docker Compose untuk membangun dan menghidupkan seluruh layanan secara bersamaan (termasuk *product-service*, *cart-service*, *order-service*, beserta database MySQL masing-masing)
   docker-compose up -d --build

4. Setelah semua kontainer menyala dengan sempurna, jalankan perintah migrasi database berikut di terminal agar tabel pesanan (`orders` dan `order_items`) terbentuk secara otomatis di dalam database *order-service*
   docker-compose exec order-service php artisan migrate

---

##  3. Katalog Endpoint & Simulasi Pengujian API

Pengujian ini dapat dilakukan menggunakan aplikasi penguji API seperti **Insomnia** atau **Postman**. Seluruh rute pada `order-service` aktif pada port **`3003`**

### A. Cek Status Kesehatan Layanan (`GET /health`)
- **Fungsi**: Memastikan bahwa *order-service* menyala dan siap menerima perintah
- **Cara Akses**: Kirim metode `GET` ke `http://localhost:3003/api/health`
- **Contoh Respons Sukses (`200 OK`)**
{
  "status": "ok",
  "service": "order-service"
}

### B. Proses Checkout Pesanan (`POST /orders`)
- **Fungsi**: Mengubah isi keranjang belanja *user* menjadi sebuah transaksi pesanan resmi
- **Cara Akses**: Kirim metode `POST` ke `http://localhost:3003/api/orders` dengan *Header* `Content-Type: application/json`
- **Contoh Request Body**
{
  "user_id": 1
}
- **Contoh Respons Sukses (`201 Created`)**
{
  "message": "Pesanan berhasil dibuat",
  "data": {
    "id": 1,
    "user_id": 1,
    "status": "PENDING",
    "total_amount": 1050000,
    "items": [
      {
        "product_id": 1,
        "product_name": "Keyboard Mekanikal",
        "unit_price": 450000,
        "quantity": 2,
        "subtotal": 900000
      }
    ],
    "created_at": "2026-10-20T03:15:00+00:00",
    "updated_at": "2026-10-20T03:15:00+00:00"
  }
}
- **Skenario Gagal - Data Tidak Lengkap (`422 Unprocessable Entity`)**: Jika *field* `user_id` dikosongkan.
{
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "user_id": [
      "Field user_id wajib diisi"
    ]
  }
}
- **Skenario Gagal - Stok Barang Kurang / Keranjang Kosong (`409 Conflict`)**: Muncul apabila pembeli meminta jumlah barang melebihi stok gudang yang tersedia
{
  "message": "Stok Monitor 24 Inch tidak mencukupi: diminta 11, tersedia 10"
}
- **Skenario Gagal - Layanan Lain Mati (`503 Service Unavailable`)**: Muncul apabila *product-service* atau *cart-service* sedang tidak aktif di jaringan Docker
{
  "message": "Product service tidak dapat dihubungi, coba lagi nanti"
}

### C. Melihat Detail Pesanan (`GET /orders/{id}`)
- **Fungsi**: Menampilkan rincian lengkap suatu transaksi berdasarkan nomor ID pesanan.
- **Cara Akses**: Kirim metode `GET` ke `http://localhost:3003/api/orders/1`
- **Contoh Respons Sukses (`200 OK`)**
{
  "message": "Berhasil mengambil pesanan",
  "data": {
    "id": 1,
    "user_id": 7,
    "status": "PAID",
    "total_amount": 1050000,
    "items": [
      {
        "product_id": 1,
        "product_name": "Keyboard Mekanikal",
        "unit_price": 450000,
        "quantity": 2,
        "subtotal": 900000
      }
    ],
    "created_at": "2026-10-20T03:15:00+00:00",
    "updated_at": "2026-10-20T04:02:00+00:00"
  }
}
- **Skenario Gagal - Tidak Ditemukan (`404 Not Found`)**: Jika ID pesanan tidak terdaftar di database.
{
  "message": "Pesanan dengan id 99 tidak ditemukan"
}

### D. Riwayat Pesanan Berdasarkan User (`GET /orders/user/{userId}`)
- **Fungsi**: Menampilkan daftar seluruh riwayat belanja seorang *user* tertentu dengan tambahan filter status opsional (`?status=PENDING` atau `?status=PAID`)[cite: 2].
- **Cara Akses**: Kirim metode `GET` ke `http://localhost:3003/api/orders/user/7?status=PENDING`
- **Contoh Respons Sukses (`200 OK`)**
{
  "message": "Berhasil mengambil daftar pesanan",
  "data": [
    {
      "id": 2,
      "user_id": 7,
      "status": "PENDING",
      "total_amount": 150000,
      "items": [
        {
          "product_id": 2,
          "product_name": "Mouse Wireless",
          "unit_price": 150000,
          "quantity": 1,
          "subtotal": 150000
        }
      ],
      "created_at": "2026-10-20T05:10:00+00:00",
      "updated_at": "2026-10-20T05:10:00+00:00"
    }
  ]
}

### E. Simulasi Pembayaran (`PATCH /orders/{id}/pay`)
- **Fungsi**: Mengubah status pesanan dari yang awalnya `PENDING` (tertunda) menjadi `PAID` (sudah dibayar).
- **Cara Akses**: Kirim metode `PATCH` ke `http://localhost:3003/api/orders/2/pay`[cite: 2]
- **Contoh Respons Sukses (`200 OK`)**:
{
  "message": "Pesanan berhasil dibayar",
  "data": {
    "id": 2,
    "user_id": 7,
    "status": "PAID",
    "total_amount": 150000,
    "items": [
      {
        "product_id": 2,
        "product_name": "Mouse Wireless",
        "unit_price": 150000,
        "quantity": 1,
        "subtotal": 150000
      }
    ],
    "created_at": "2026-10-20T05:10:00+00:00",
    "updated_at": "2026-10-20T05:12:00+00:00"
  }
}
- **Skenario Gagal (`409 Conflict`)**: Jika pesanan tersebut sudah pernah dibayar sebelumnya (status sudah `PAID`), sistem akan menolak pembayaran ulang.
{
  "message": "Pesanan berstatus PAID, hanya pesanan PENDING yang dapat dibayar"
}

---

##  4. Daftar Environment Variables

Seluruh konfigurasi utama yang dibutuhkan oleh aplikasi diatur secara fleksibel melalui file `.env`:

| Nama Variabel | Keterangan / Fungsi | Contoh Nilai |
| :--- | :--- | :--- |
| `APP_NAME` | Nama identitas aplikasi | `OrderService` |
| `APP_ENV` | Mode lingkungan sistem | `local` |
| `APP_KEY` | Kunci enkripsi pengaman Laravel | `base64:...` |
| `APP_DEBUG` | Pengatur mode debug (*true* untuk menampilkan detail error teknis)[cite: 2] | `false` |
| `APP_URL` | Alamat URL dasar server | `http://localhost:3003` |
| `DB_CONNECTION` | Jenis penggerak database | `mysql` |
| `DB_HOST` | Alamat host server database MySQL lokal | `order-mysql` |
| `DB_PORT` | Nomor port database MySQL | `3306` |
| `DB_DATABASE` | Nama database untuk order | `order_db` |
| `DB_USERNAME` | Nama pengguna akses database | `root` |
| `DB_PASSWORD` | Kata sandi akses database | `secret` |
| `CART_SERVICE_URL` | Alamat jalur komunikasi internal menuju *cart-service* | `http://cart-service:3002` |
| `PRODUCT_SERVICE_URL`| Alamat jalur komunikasi internal menuju *product-service* | `http://product-service:3001` |

---

##  5. Keputusan Desain & Keterbatasan Sistem

### Keputusan Desain yang Diambil:
1. **Pemisahan Lapisan Ketat**: Memastikan kode dari *Controller*, logika *Service*, hingga komunikasi luar (*Client*) tidak saling tumpang tindih agar mudah dirawat dan diuji.
2. **Penerapan Keamanan Transaksi Database (`DB::transaction`)**: Dipilih untuk menjamin bahwa pencatatan data utama pesanan beserta rincian item barangnya berjalan sinkron secara bersamaan tanpa risiko data terpotong sebelah.
3. **Penanganan Kesalahan Berkelanjutan (*Graceful Degradation*)**: Jika pesanan berhasil disimpan ke database lokal namun ternyata terjadi kendala saat meminta *cart-service* mengosongkan keranjang belanja, sistem tetap mengutamakan keamanan data pesanan utama dengan mengembalikan respons sukses `201 Created` dan mencatat masalah keranjang tersebut ke dalam file *log* sistem secara aman.

### Keterbatasan Sistem Saat Ini:
1. **Tidak Melakukan Pengurangan Stok Otomatis**: Sesuai dengan batasan modul tugas UTS yang diberikan, *order-service* hanya bertugas memvalidasi ketersediaan dan jumlah stok barang melalui *product-service* tanpa memotong atau mengurangi sisa stok asli yang ada di gudang *product-service*.
2. **Simulasi Pembayaran Sederhana**: Fitur pembayaran pada rute `PATCH /orders/{id}/pay` bersifat simulasi internal untuk mengubah status transaksi saja (`PENDING` menjadi `PAID`) tanpa melibatkan integrasi sistem pembayaran eksternal pihak ketiga (*payment gateway*) yang sebenarnya.