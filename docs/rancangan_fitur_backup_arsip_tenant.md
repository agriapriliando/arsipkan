# Rancangan Fitur Backup Arsip Tenant

## Tujuan

Fitur ini ditujukan agar admin tenant dapat membuat cadangan arsip dari antarmuka aplikasi untuk dipindahkan ke media lokal seperti harddisk eksternal, server cadangan, atau perangkat penyimpanan internal instansi.

Fitur backup tidak dimaksudkan sebagai pengganti penyimpanan utama aplikasi. Backup adalah salinan sementara yang dibuat berdasarkan permintaan admin tenant, diunduh, lalu dihapus kembali secara manual atau otomatis.

## Sasaran Utama

- Memudahkan admin tenant mengunduh banyak file sekaligus.
- Menyediakan paket backup yang rapi dan dapat dipindahkan ke media lokal.
- Menjaga keamanan data tenant selama proses pembuatan dan pengunduhan backup.
- Menyediakan jejak audit atas pembuatan, pengunduhan, dan penghapusan backup.
- Menghindari penumpukan file backup sementara di server aplikasi.

## Prinsip Rekomendasi

- Backup dibuat sebagai paket `.zip`, bukan unduh massal sinkron langsung dari browser.
- Backup diproses secara asynchronous melalui queue atau job background.
- File backup disimpan di storage privat.
- Link unduhan backup hanya dapat diakses oleh admin tenant yang berwenang.
- Backup memiliki masa berlaku.
- Backup dapat dihapus manual setelah selesai diunduh.
- Backup yang sudah lewat masa berlaku dibersihkan otomatis oleh scheduler.

## Ruang Lingkup Fitur

Fitur backup minimal mencakup:

- Backup semua file tenant.
- Backup file terpilih.
- Backup berdasarkan filter.
- Unduh file hasil backup.
- Hapus file backup hasil generate.
- Pencatatan audit aktivitas backup.

## Mode Backup yang Direkomendasikan

### 1. Backup Semua File Tenant

Digunakan saat tenant ingin membuat salinan penuh semua arsip yang masih aktif dalam sistem.

Cocok untuk:

- migrasi data
- backup berkala bulanan
- serah terima arsip

### 2. Backup File Terpilih

Admin tenant memilih beberapa file dari daftar arsip, lalu membuat satu paket backup dari file yang dipilih.

Cocok untuk:

- kebutuhan operasional cepat
- pengumpulan dokumen tertentu
- serah terima sebagian arsip

### 3. Backup Berdasarkan Filter

Admin tenant membuat backup berdasarkan parameter tertentu.

Filter yang direkomendasikan:

- rentang tanggal upload
- kategori
- tag
- visibilitas
- status review
- uploader

Mode ini lebih fleksibel dibanding hanya memilih file satu per satu.

## Format Hasil Backup

Hasil backup direkomendasikan berupa:

- satu file `.zip`
- satu file `manifest.json` atau `manifest.csv`

Isi manifest sebaiknya memuat metadata berikut:

- ID file
- judul
- nama file asli
- nama file tersimpan
- kategori
- tag
- uploader
- nomor HP uploader
- tanggal upload
- visibilitas
- status review
- ukuran file
- mime type
- link upload asal

Jika diperlukan, sertakan juga:

- tanggal review
- admin reviewer
- tanggal delete soft delete
- informasi apakah file pernah dipulihkan

## Struktur Isi ZIP yang Direkomendasikan

Contoh struktur:

```text
backup-tenant-demo-dinas-2026-04-30-103000.zip
|-- manifest.json
|-- files/
|   |-- 0001_surat-keputusan.pdf
|   |-- 0002_berita-acara.docx
|   |-- 0003_laporan-kegiatan.xlsx
```

Gunakan penamaan file di dalam ZIP yang stabil dan aman. Jangan hanya memakai nama file asli jika berpotensi bentrok. Tambahkan prefix numerik atau ID file.

## Alur Penggunaan yang Direkomendasikan

### Alur Admin Tenant

1. Admin membuka menu `Backup Arsip`.
2. Admin memilih mode backup:
   - semua file
   - file terpilih
   - berdasarkan filter
3. Admin mengirim permintaan backup.
4. Sistem membuat record backup dengan status `processing`.
5. Job background mengumpulkan file dan metadata.
6. Sistem membentuk file ZIP di storage privat.
7. Status berubah menjadi `ready`.
8. Admin melihat tombol `Unduh`.
9. Setelah diunduh, status dapat berubah menjadi `downloaded`.
10. Admin dapat menekan tombol `Hapus Backup`.
11. Jika tidak dihapus manual, scheduler menghapus backup setelah expired.

### Alur Sistem

1. Validasi hak akses admin tenant.
2. Validasi parameter backup.
3. Simpan record backup.
4. Dispatch job pembentukan paket backup.
5. Bangun manifest.
6. Tambahkan file ke ZIP.
7. Simpan hasil ZIP ke storage privat.
8. Update ukuran file backup.
9. Catat waktu selesai.
10. Siapkan endpoint download aman.
11. Catat histori download.
12. Hapus file fisik saat admin menghapus backup atau saat expired.

## Hak Akses

Rekomendasi hak akses:

- `tenant_admin`: boleh membuat, melihat, mengunduh, dan menghapus backup tenant-nya sendiri
- `superadmin`: boleh melihat dan mengunduh backup tenant jika dibutuhkan untuk dukungan sistem
- `user uploader`: tidak boleh membuat atau mengunduh backup massal tenant

Jika ingin lebih ketat:

- hanya admin pembuat backup yang boleh menghapus backup tersebut
- admin tenant lain dalam tenant yang sama boleh melihat status, tetapi tidak boleh menghapus

## Status Backup yang Direkomendasikan

Gunakan state yang jelas pada record backup:

- `processing`
- `ready`
- `failed`
- `downloaded`
- `expired`
- `deleted`

Penjelasan:

- `processing`: backup sedang dibuat
- `ready`: backup siap diunduh
- `failed`: proses gagal
- `downloaded`: sudah pernah diunduh minimal satu kali
- `expired`: masa berlaku habis
- `deleted`: file backup sudah dihapus dari storage

## Data yang Perlu Disimpan

Disarankan membuat tabel seperti `backup_exports`.

Field utama yang direkomendasikan:

- `id`
- `tenant_id`
- `created_by_admin_id`
- `mode`
- `filters_json`
- `selected_file_ids_json`
- `status`
- `storage_disk`
- `storage_path`
- `file_name`
- `file_size`
- `file_count`
- `download_count`
- `last_downloaded_at`
- `expires_at`
- `completed_at`
- `failed_at`
- `deleted_at`
- `failure_reason`
- `created_at`
- `updated_at`

Jika ingin audit lebih detail, tambahkan tabel log seperti `backup_export_logs` dengan event:

- `created`
- `processing_started`
- `processing_finished`
- `downloaded`
- `deleted`
- `expired`
- `failed`

## Aturan Masa Berlaku

Rekomendasi default:

- backup siap unduh berlaku `24 jam`

Alternatif:

- `3 hari` untuk tenant yang jarang login

Jangan membiarkan backup sementara tersimpan permanen di server.

## Aturan Penghapusan

### Hapus Manual

Setelah admin selesai mengunduh:

- admin menekan tombol `Hapus Backup`
- sistem menghapus file ZIP dari storage privat
- record tidak harus dihapus total dari database
- record dapat dipertahankan untuk audit dengan status `deleted`

### Hapus Otomatis

Scheduler harian atau per jam memeriksa backup yang:

- status `ready` atau `downloaded`
- `expires_at` sudah lewat
- file fisik masih ada

Lalu sistem:

- menghapus file fisik
- mengubah status menjadi `expired` atau `deleted`
- mencatat log penghapusan otomatis

## Keamanan yang Direkomendasikan

- file backup tidak boleh disimpan di direktori publik
- endpoint download harus memeriksa otentikasi dan kepemilikan tenant
- jangan gunakan URL publik permanen untuk file backup
- token unduhan, jika dipakai, harus singkat masa berlakunya
- semua aktivitas backup dicatat di audit log
- batasi jumlah backup aktif per tenant untuk mencegah abuse
- batasi ukuran maksimum per paket backup
- pastikan tenant A tidak bisa mengakses backup tenant B

## Pertimbangan Performa

Jangan membuat ZIP besar langsung di satu request web biasa karena berisiko:

- timeout
- memory tinggi
- proses gagal di tengah jalan
- pengalaman admin buruk

Rekomendasi:

- gunakan queue job
- tampilkan progress sederhana atau status polling
- batasi jumlah file per paket jika perlu
- jika paket sangat besar, pertimbangkan pemecahan per batch

## Dampak terhadap Storage

File backup adalah duplikasi dari arsip utama. Karena itu:

- backup sementara sebaiknya tidak dihitung sebagai arsip utama tenant
- tetapi bisa dicatat sebagai `temporary backup usage`

Pilihan kebijakan:

### Opsi A

Backup tidak mengurangi kuota storage tenant utama, tetapi dibatasi jumlah dan masa berlakunya.

### Opsi B

Backup ikut dihitung ke kuota sementara tenant selama file backup masih tersimpan.

Rekomendasi terbaik untuk tahap awal adalah Opsi A, dengan pembatasan:

- maksimal jumlah backup aktif
- maksimal ukuran paket backup
- auto cleanup ketat

## Rekomendasi UI

Tambahkan menu baru:

- `Backup Arsip`

Halaman utama menampilkan:

- tombol `Buat Backup`
- tabel daftar backup
- status
- jumlah file
- ukuran
- dibuat oleh
- dibuat pada
- kadaluarsa pada
- jumlah unduhan
- aksi `Unduh`
- aksi `Hapus`

Form pembuatan backup:

- pilih mode backup
- pilih filter
- pilih file jika mode manual
- tampilkan estimasi jumlah file
- tampilkan peringatan jika ukuran besar

## Rekomendasi Tahap Implementasi

### Tahap 1

- backup file terpilih
- generate ZIP
- download oleh admin tenant
- hapus manual
- auto-expire sederhana

### Tahap 2

- backup berdasarkan filter
- manifest metadata
- audit log lebih lengkap

### Tahap 3

- backup penuh tenant
- optimasi untuk dataset besar
- progress processing yang lebih baik

## Risiko Jika Tidak Dirancang dengan Benar

- backup menumpuk dan memenuhi storage server
- file sensitif bocor jika link unduhan tidak dilindungi
- proses ZIP timeout dan gagal
- tenant bisa mengakses backup tenant lain
- admin mengunduh backup tanpa jejak audit

## Kesimpulan Rekomendasi

Pendekatan terbaik adalah:

- backup dibuat sebagai paket ZIP
- diproses asynchronous
- disimpan di storage privat
- hanya admin tenant berwenang yang dapat mengakses
- memiliki masa berlaku
- dapat dihapus manual setelah diunduh
- dibersihkan otomatis oleh scheduler
- seluruh aktivitasnya dicatat untuk audit

Dengan pendekatan ini, fitur backup tetap praktis bagi admin tenant, tetapi tidak mengorbankan keamanan, performa, dan tata kelola arsip.
