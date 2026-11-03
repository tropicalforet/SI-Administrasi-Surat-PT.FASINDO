# E-Office: Sistem Administrasi Surat & Disposisi — PT Fasadetama Indonesia

Sistem informasi berbasis web untuk mengelola surat masuk, surat keluar, dan alur disposisi berjenjang di PT Fasadetama Indonesia. Dibangun sebagai proyek skripsi S1 Sistem Informasi UNIKOM.

## Fitur

- Surat masuk dan surat keluar, dengan penomoran surat otomatis
- Disposisi berjenjang dan monitoring disposisi
- SKPD (surat perjalanan dinas)
- Tanda tangan elektronik dengan verifikasi QR code
- Hak akses per peran dan per menu
- Laporan surat masuk, surat keluar, dan SKPD
- Activity log dan dashboard

Pengujian: Black Box Testing pada 8 modul.

## Teknologi

Laravel 12 · PHP 8.2 · MySQL/MariaDB · Tailwind CSS · Alpine.js · DomPDF · PHPWord

## Cara menjalankan

Kebutuhan: PHP 8.2+, Composer, Node.js 20+, dan MySQL/MariaDB (misalnya lewat Laragon atau XAMPP).

1. Clone repo:
   ```bash
   git clone https://github.com/tropicalforet/SI-Administrasi-Surat-PT.FASINDO.git
   cd SI-Administrasi-Surat-PT.FASINDO
   ```
2. Buat database kosong di MySQL, misalnya `e_office`.
3. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi database:
   ```env
   DB_DATABASE=e_office
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Install dan siapkan aplikasi:
   ```bash
   composer run setup
   php artisan db:seed
   php artisan storage:link
   ```
5. Jalankan:
   ```bash
   composer run dev
   ```
   Buka `http://localhost:8000`.

## Akun awal

Seeder membuat satu akun:

| Email | Password |
|---|---|
| `test@example.com` | `password` |

Akun ini berperan `staff`. Jadikan administrator agar bisa membuat akun lain:

```bash
php artisan tinker --execute="App\Models\User::where('email','test@example.com')->update(['role'=>'superadmin']);"
```

## Peran pengguna

| Peran | Tugas utama |
|---|---|
| `superadmin` / `admin` | Mengelola akun pengguna, hak akses menu, arsip terhapus, activity log |
| `sekretaris` | Mencatat surat masuk, memeriksa surat keluar sebelum ke Direktur Utama |
| `dirut` (Direktur Utama) | Mendisposisikan surat masuk, menyetujui/menolak surat keluar dan SKPD |
| `direktur1` / `direktur2` | Direktur Keuangan & Administrasi / Direktur Teknik: meneruskan disposisi, menyetujui SKPD unitnya |
| `manager` | Meneruskan disposisi ke staf |
| `staff` | Menindaklanjuti disposisi, membuat draft surat keluar dan SKPD |

## Cara menggunakan

### 1. Siapkan akun (admin)

Login sebagai admin, buka menu **Manajemen User**, lalu buat akun untuk setiap peran (sekretaris, dirut, direktur, manager, staf). Peran direktur, manager, dan staf wajib diisi unitnya.

### 2. Surat masuk dan disposisi

1. **Sekretaris** membuka **Surat Masuk → Tambah**, mengisi data surat, mengunggah file, dan memilih penerima (orang atau peran).
2. **Direktur Utama** membuka surat tersebut lalu membuat **disposisi** ke direktur atau sekretaris, beserta instruksinya.
3. Penerima melihat tugasnya di **Disposisi Saya**, lalu memperbarui status atau klik **Teruskan Disposisi** ke bawahannya (direktur → manager → staf).
4. Setelah selesai dikerjakan, pemberi disposisi klik **Verifikasi**, atau **Kembalikan untuk Diperbaiki** jika belum sesuai.
5. Seluruh alur dapat dipantau di **Monitoring Disposisi**. Setiap perubahan memunculkan notifikasi.

### 3. Surat keluar

1. Pembuat surat membuka **Surat Keluar → Tambah**, mengisi isi surat (tersimpan sebagai **draft**), lalu klik **Ajukan**.
2. **Sekretaris** memeriksa dan memproses surat. Nomor surat terbit otomatis, lalu surat diteruskan ke Direktur Utama.
3. **Direktur Utama** menyetujui (surat diberi tanda tangan elektronik dan QR code; status **terkirim**) atau menolak dengan catatan.
4. Surat yang disetujui dapat diunduh sebagai PDF. Scan QR code pada surat untuk membuka halaman verifikasi keaslian.

### 4. SKPD (Surat Perjalanan Dinas)

1. Pegawai membuat SKPD (**draft**), lalu klik **Ajukan** ke direktur unitnya.
2. Direktur menyetujui, lalu SKPD diteruskan ke **Direktur Utama** untuk persetujuan akhir.
3. SKPD yang disetujui dapat dipratinjau dan diunduh sebagai PDF ber-QR code.

### 5. Laporan dan arsip

- **Laporan**: pilih jenis (surat masuk, surat keluar, disposisi, SKPD), atur filter, lalu ekspor ke PDF.
- **Arsip Terhapus** (admin): pulihkan atau hapus permanen data yang terhapus.
- **Activity Log** (admin): riwayat aktivitas seluruh pengguna.
