# Rancangan Lengkap Aplikasi Arsip Berkas

## 1. Ringkasan

Aplikasi ini adalah sistem arsip berkas **multitenant** untuk banyak organisasi, dengan dua jalur akses uploader:
- **upload tanpa login** melalui **link upload khusus** yang dibuat **Admin Tenant**
- **akun pengguna** berbasis **nomor HP** untuk melihat seluruh berkas miliknya dan mengunggah berkas baru di tenant organisasinya

Setiap organisasi diperlakukan sebagai **tenant** yang memiliki data, admin, link upload, kategori, tag, file, leaderboard, dan pengaturan sendiri. Data antar tenant harus terisolasi. Untuk fase awal MVP, rancangan difokuskan pada fondasi inti tanpa audit log dan tanpa integrasi API eksternal apa pun.

Pengguna umum tetap dapat mengunggah berkas tanpa login dengan mengisi **nama**, **nomor HP**, memilih **publik/internal/private**, lalu memilih file dan mengunggahnya pada tenant yang sesuai. Jika nomor HP tersebut sudah memiliki akun pengguna, **Admin Tenant** dapat membuat atau mengaktifkan akun berdasarkan nomor HP yang masuk, lalu menyiapkan password untuk disampaikan **secara manual** melalui WhatsApp.

Akun pengguna menggunakan:
- **nomor HP** sebagai identitas login
- **password** sebagai kredensial rahasia

Hak akses akun pengguna dibatasi untuk:
- login menggunakan nomor HP dan password pada tenant terkait
- `remember me` aktif secara default agar tidak perlu sering login ulang
- melihat seluruh berkas miliknya sendiri berdasarkan nomor HP yang terhubung
- melihat berkas miliknya dengan status/visibilitas **public**, **internal**, maupun **private**
- melihat seluruh daftar berkas yang diunggah di dalam tenant yang sama
- mengunggah berkas baru miliknya sendiri

Seluruh proses pengelolaan berkas tetap menjadi hak penuh **Admin Tenant** dan **Superadmin**, termasuk:
- review file
- isi dan ubah metadata
- ubah status file
- kelola kategori, tag, dan link upload
- kelola skor uploader

Sistem ini dirancang agar:
- uploader tidak wajib memiliki akun
- identitas uploader tetap dibedakan berdasarkan nomor HP
- akun pengguna dapat dibuat dari nomor HP yang sudah pernah masuk ke tenant
- guest upload dapat dikenali ulang secara ringan di browser yang sama melalui `guest_token`
- password akun pengguna dapat disiapkan untuk disampaikan manual lewat WhatsApp
- nomor HP yang sama boleh digunakan di organisasi yang berbeda
- dalam satu organisasi, satu nomor HP mewakili satu uploader
- file dengan visibilitas `public` masuk ke tahap moderasi terlebih dahulu
- file dengan visibilitas `internal` dapat langsung aktif tanpa moderasi
- file dengan visibilitas `private` tidak wajib moderasi admin
- file publik yang sudah valid bisa diunduh oleh pengunjung tenant terkait
- ada leaderboard mingguan dan bulanan per tenant
- ada sistem skor uploader yang dapat disesuaikan manual oleh Admin Tenant
- ada **Superadmin platform** yang khusus mengelola tenant (organisasi) dan akun Admin Tenant
- satu akun **Admin Tenant** hanya boleh terhubung ke **satu tenant**
- satu tenant dapat memiliki **banyak akun Admin Tenant**
- reset password akun **Superadmin** tidak dilakukan dari UI, tetapi hanya melalui **command Artisan**
- Superadmin dapat **login ke tenant** untuk masuk ke konteks organisasi tertentu
- setiap tenant dapat memiliki kuota total storage berkas sendiri
- upload baru harus ditolak jika kuota storage tenant sudah penuh

---

## 2. Tujuan Sistem

Tujuan utama aplikasi:
- menyediakan tempat unggah arsip berkas tanpa login
- menyediakan akun pengguna berbasis nomor HP untuk akses arsip milik sendiri
- memudahkan pengumpulan file dari banyak uploader
- menjaga kualitas arsip melalui review Admin Tenant
- menyediakan katalog file publik yang rapi per tenant
- menampilkan ranking uploader berdasarkan kontribusi dan skor per tenant
- menyediakan pengelolaan banyak organisasi dalam satu platform

---

## 3. Ruang Lingkup Fitur

### 3.1 Fitur untuk Guest Uploader
- akses upload tanpa login melalui link upload aktif
- isi nama
- isi nomor HP
- pilih visibilitas file: publik / internal / private
- pilih file dan unggah
- browser guest dikenali ringan melalui `guest_token`

### 3.2 Fitur untuk Pengguna Terdaftar
- login menggunakan nomor HP dan password pada tenant terkait
- `remember me` aktif default
- melihat dashboard berkas
- melihat seluruh berkas milik sendiri, baik public, internal, maupun private
- melihat seluruh daftar berkas yang diunggah di tenant yang sama
- mencari dan memfilter berkas
- melihat detail berkas
- mengunggah berkas baru milik sendiri
- melihat status berkas miliknya: pending_review / valid / suspended
- menghapus berkas dengan mekanisme soft delete
- logout

### 3.3 Fitur untuk Pengunjung Publik
- melihat daftar file publik yang sudah valid pada tenant terkait
- mencari file publik
- memfilter file berdasarkan kategori, tag, dan tipe file
- mengunduh file publik
- melihat leaderboard mingguan tenant
- melihat leaderboard bulanan tenant

### 3.4 Fitur untuk Admin Tenant
- login ke panel admin tenant
- melihat daftar file masuk
- review file
- mengisi metadata file
- mengubah status file
- mengelola kategori
- mengelola tag
- mengelola link upload
- melihat dan mengubah skor uploader
- melihat last score uploader
- melihat data leaderboard
- membuat akun pengguna berdasarkan nomor HP yang masuk
- reset password akun pengguna
- aktif/nonaktif akun pengguna
- melihat kuota storage tenant dan sisa kapasitas

### 3.5 Fitur untuk Superadmin
- mengelola tenant (organisasi)
- membuat tenant baru
- mengubah data tenant
- aktif/nonaktif tenant
- mengatur kuota storage per tenant
- melihat daftar seluruh tenant
- membuat akun Admin Tenant
- mengubah akun Admin Tenant
- aktif/nonaktif akun Admin Tenant
- reset password akun Admin Tenant
- login ke tenant tertentu
- mengubah pengaturan global platform
- mengubah bobot skor default platform
- reset password akun Superadmin melalui command Artisan

---

## 4. Peran Pengguna

## 4.1 Guest Uploader
Pengguna tanpa login yang hanya bisa:
- mengisi identitas awal
- memilih visibilitas file
- mengunggah file lewat link aktif

## 4.2 Pengguna Terdaftar
Pengguna dengan akun berbasis nomor HP yang bisa:
- login menggunakan nomor HP dan password
- tetap login dengan `remember me` aktif default
- melihat seluruh berkas miliknya sendiri
- melihat berkas miliknya dengan visibilitas public, internal, maupun private
- melihat seluruh daftar berkas internal dalam tenant yang sama
- mengunggah berkas baru atas nomor HP miliknya
- menghapus berkas miliknya dengan soft delete

Pengguna terdaftar **tidak bisa**:
- mengubah metadata berkas
- mengubah status berkas
- mengelola kategori/tag
- mengakses menu administrasi

## 4.3 Admin Tenant
Pengguna internal yang bisa:
- review file
- isi metadata
- ubah status file
- ubah skor uploader
- buat link upload
- kelola kategori dan tag
- buat akun pengguna dari nomor HP yang masuk
- generate/reset password akun pengguna
- memulihkan berkas yang di-soft delete
- menghapus permanen berkas

Aturan:
- satu akun Admin Tenant hanya boleh terhubung ke satu tenant
- satu tenant dapat memiliki banyak Admin Tenant
- Admin Tenant hanya dapat melihat dan mengelola data tenant miliknya sendiri

## 4.4 Superadmin
Pengguna internal tertinggi yang bisa:
- mengelola tenant (organisasi)
- mengelola akun Admin Tenant
- masuk ke konteks tenant tertentu
- mengelola pengaturan global sistem

Catatan:
- Superadmin bukan milik tenant tertentu
- reset password Superadmin hanya dilakukan melalui command Artisan

---

## 5. Alur Bisnis Utama

## 5.0 Alur Resolusi Tenant
1. Pengunjung atau pengguna membuka URL tenant dengan pola `arsipkan.my.id/{tenant_slug}/...`.
2. Sistem membaca segmen pertama path sebagai `tenant_slug`.
3. Sistem melakukan resolusi tenant berdasarkan `tenant_slug`.
4. Jika `tenant_slug` valid dan tenant aktif, sistem memuat konteks tenant aktif.
5. Semua proses berikutnya berjalan di dalam tenant tersebut, kecuali area Superadmin platform.

Contoh:
- `arsipkan.my.id/pemda-a`
- `arsipkan.my.id/pemda-a/login`
- `arsipkan.my.id/pemda-a/admin`
- `arsipkan.my.id/pemda-a/leaderboard`

Area platform Superadmin tetap berada di luar path tenant, misalnya:
- `arsipkan.my.id/superadmin`

## 5.1 Alur Pembuatan Link Upload
1. Admin Tenant login ke panel tenant.
2. Admin Tenant membuat link upload.
3. Sistem menghasilkan kode unik / token link.
4. Link dapat memiliki pengaturan:
   - aktif / nonaktif
   - tanggal kedaluwarsa
   - batas penggunaan
   - judul / catatan internal
5. Link dibagikan ke uploader tenant tersebut.

## 5.2 Alur Upload Guest
1. Guest membuka link upload tenant.
2. Guest mengisi:
   - nama
   - nomor HP
3. Sistem menormalisasi nomor HP.
4. Sistem mengecek apakah nomor HP sudah terdaftar sebagai uploader di tenant tersebut.
   - jika belum ada, buat profil uploader baru
   - jika sudah ada, gunakan profil uploader yang sama
5. Sistem membuat atau memakai kembali `guest_token` untuk browser guest.
   - `guest_token` hanya berfungsi sebagai identitas ringan browser guest
   - `guest_token` bukan akun login dan bukan pengganti autentikasi
   - `guest_token` dipakai agar sistem dapat mengenali browser guest yang sama untuk pengalaman upload guest yang lebih nyaman
6. Guest memilih:
   - public
   - internal
   - private
7. Guest memilih file.
8. Sistem memeriksa kuota storage tenant.
9. Jika `storage_used_bytes + file_size` melebihi `storage_quota_bytes`, upload ditolak.
10. Guest mengunggah file.
11. Sistem menyimpan file ke tenant terkait.
12. Sistem menentukan status awal:
   - jika `visibility = public`, maka `status = pending_review`
   - jika `visibility = internal`, maka `status = valid`
   - jika `visibility = private`, maka `status = valid`
13. Sistem menambahkan ukuran file ke pemakaian storage tenant.

## 5.3 Alur Pembuatan Akun Pengguna oleh Admin Tenant
1. Admin Tenant membuka daftar uploader berdasarkan nomor HP yang sudah masuk pada tenantnya.
2. Admin Tenant memilih satu nomor HP.
3. Sistem mengecek apakah nomor HP tersebut sudah memiliki akun pengguna di tenant tersebut.
4. Jika belum ada akun, Admin Tenant membuat akun pengguna.
5. Sistem membuat atau menyimpan:
   - relasi akun ke uploader berdasarkan `guest_uploader_id`
   - password awal
   - status akun aktif/nonaktif
   - status wajib ganti password
6. Sistem menghasilkan password awal.
7. Admin Tenant menyampaikan password awal secara manual kepada pengguna.

## 5.4 Alur Login Pengguna
1. Pengguna membuka halaman login tenant.
2. Pengguna mengisi nomor HP dan password.
3. Sistem menormalisasi nomor HP.
4. Sistem memverifikasi tenant aktif.
5. Sistem mencari uploader berdasarkan nomor HP dalam tenant aktif.
6. Sistem mencari akun pengguna melalui `guest_uploader_id`.
7. Sistem memverifikasi akun aktif pada tenant tersebut.
8. Sistem memverifikasi password.
9. Jika akun berstatus wajib ganti password, pengguna diarahkan ke form ubah password terlebih dahulu.
10. Jika valid, pengguna masuk ke dashboard berkas miliknya.
11. Opsi `remember me` aktif secara default.

## 5.5 Alur Login Admin Tenant
1. Admin Tenant membuka halaman login admin tenant.
2. Admin Tenant mengisi email dan password.
3. Sistem memverifikasi akun Admin Tenant aktif.
4. Sistem memastikan akun tersebut terhubung ke tepat satu tenant.
5. Sistem memuat tenant dari akun tersebut.
6. Jika valid, Admin Tenant masuk ke panel tenantnya.

## 5.6 Alur Login Superadmin
1. Superadmin membuka halaman login platform.
2. Superadmin mengisi email dan password.
3. Sistem memverifikasi akun Superadmin aktif.
4. Jika valid, Superadmin masuk ke panel platform.

## 5.7 Alur Superadmin Login ke Tenant
1. Superadmin login ke panel platform.
2. Superadmin membuka daftar tenant.
3. Superadmin memilih satu tenant.
4. Superadmin memilih aksi `login ke tenant`.
5. Sistem membuat sesi tenant-context untuk Superadmin.
6. Superadmin masuk ke panel tenant terpilih dengan penanda bahwa akses berasal dari Superadmin.
7. Semua aksi berjalan dalam konteks tenant yang dipilih.

## 5.8 Alur Akses Berkas Milik Pengguna
1. Pengguna login menggunakan nomor HP dan password.
2. Sistem mengambil relasi akun ke uploader berdasarkan nomor HP dalam tenant aktif.
   - relasi data internal tetap menggunakan `guest_uploader_id`
3. Sistem menampilkan:
   - berkas milik sendiri dalam tenant aktif
   - seluruh daftar berkas internal tenant
4. Pengguna dapat melihat:
   - berkas milik sendiri dengan visibilitas `public`
   - berkas milik sendiri dengan visibilitas `internal`
   - berkas milik sendiri dengan visibilitas `private`
   - berkas milik uploader lain dalam tenant yang sama jika visibilitasnya `internal`

## 5.9 Alur Upload oleh Pengguna Terdaftar
1. Pengguna login.
2. Pengguna membuka menu upload berkas.
3. Sistem otomatis mengikat upload ke nomor HP akun yang sedang login dalam tenant aktif.
   - relasi data internal tetap menggunakan `guest_uploader_id`
4. Pengguna memilih visibilitas:
   - public
   - internal
   - private
5. Pengguna memilih file.
6. Sistem memeriksa kuota storage tenant.
7. Jika `storage_used_bytes + file_size` melebihi `storage_quota_bytes`, upload ditolak.
8. Sistem menyimpan file.
9. Sistem menentukan status awal:
   - jika `visibility = public`, maka `status = pending_review`
   - jika `visibility = internal`, maka `status = valid`
   - jika `visibility = private`, maka `status = valid`
10. Sistem menambahkan ukuran file ke pemakaian storage tenant.
11. Admin Tenant tetap menjadi pihak yang melakukan review untuk file yang memang membutuhkan moderasi.

## 5.10 Alur Soft Delete oleh Pengguna
1. Pengguna login.
2. Pengguna membuka detail atau daftar berkas.
3. Pengguna memilih aksi hapus pada berkas.
4. Sistem melakukan soft delete pada record berkas.
5. Berkas tidak lagi tampil di daftar aktif pengguna dan daftar aktif tenant.
6. Admin Tenant masih dapat melihat berkas yang di-soft delete untuk dipulihkan atau dihapus permanen.

## 5.11 Alur Review oleh Admin Tenant
1. Admin Tenant membuka daftar file pending.
2. Admin Tenant meninjau file.
3. Admin Tenant mengisi metadata:
   - judul berkas
   - kategori
   - tag
   - deskripsi singkat
   - tipe file final
4. Admin Tenant menentukan status akhir sementara:
   - tetap `pending_review`
   - `valid`
   - `suspended`
5. Jika file `valid` dan `visibility = public`, file muncul di halaman publik tenant.
6. Jika file `valid` dan `visibility = internal`, file tidak muncul di publik tetapi dapat dilihat user yang login dalam tenant yang sama.
7. Jika file `valid` dan `visibility = private`, file tidak muncul di publik dan hanya dapat dilihat uploader pemilik, Admin Tenant, dan Superadmin dalam konteks tenant.

## 5.12 Alur Pemulihan atau Hapus Permanen oleh Admin Tenant
1. Admin Tenant membuka daftar berkas yang di-soft delete.
2. Admin Tenant memilih satu berkas.
3. Admin Tenant dapat memilih:
   - pulihkan berkas
   - hapus permanen berkas
4. Jika dipulihkan, berkas kembali tampil di daftar aktif sesuai status sebelumnya.
5. Jika dihapus permanen, record dan file fisik dihapus permanen sesuai kebijakan sistem.
6. Saat hapus permanen berhasil, sistem mengurangi `storage_used_bytes` tenant sesuai ukuran file.

## 5.13 Alur Download File Publik
1. Pengunjung membuka halaman publik tenant.
2. Pengunjung mencari atau memfilter file.
3. Pengunjung memilih file publik yang valid.
4. Sistem mencatat log download.
5. Sistem menghitung download yang sah untuk skor jika memenuhi aturan tenant.

## 5.14 Alur Penyesuaian Skor Manual
1. Admin Tenant membuka profil uploader / halaman skor.
2. Admin Tenant mengubah skor manual.
3. Sistem wajib menyimpan:
   - nilai sebelum
   - nilai sesudah
   - selisih
   - admin yang mengubah
   - tenant terkait
   - waktu perubahan
4. Sistem memperbarui `last score` uploader.

## 5.15 Alur Reset Password Superadmin via Artisan
1. Operator server menjalankan command Artisan khusus reset password Superadmin.
2. Sistem meminta identitas akun Superadmin yang akan direset.
3. Sistem membuat password baru sesuai kebijakan.
4. Sistem menyimpan password hash baru.

Contoh command:
```bash
php artisan superadmin:reset-password {email}
```

---

## 6. Aturan Bisnis

## 6.0 Aturan Multitenancy
- setiap organisasi direpresentasikan sebagai satu `tenant`
- seluruh data operasional harus terikat ke satu tenant
- data antar tenant tidak boleh saling terlihat atau saling terubah oleh Admin Tenant
- Superadmin dapat mengakses tenant mana pun melalui context switching / login ke tenant
- satu akun Admin Tenant hanya boleh berada pada satu tenant
- satu tenant dapat memiliki banyak akun Admin Tenant
- nomor HP yang sama boleh terdaftar di tenant yang berbeda
- URL tenant menggunakan pola `arsipkan.my.id/{tenant_slug}/...`
- segmen pertama path setelah domain utama diperlakukan sebagai `tenant_slug`
- `tenant_slug` harus unik
- `tenant_slug` tidak boleh memakai reserved slug sistem
- setiap tenant dapat memiliki batas total storage berkas sendiri
- seluruh perhitungan pemakaian storage harus dilakukan dalam konteks tenant

Contoh reserved slug:
- `superadmin`
- `admin`
- `login`
- `logout`
- `api`
- `assets`
- `storage`
- `docs`
- `public`
- `favicon.ico`

## 6.1 Identitas Uploader
- identitas utama uploader adalah **nomor HP yang sudah dinormalisasi**
- satu nomor HP dianggap satu uploader dalam satu tenant
- nomor HP yang sama boleh muncul kembali di tenant lain
- nama uploader bisa diperbarui pada submit berikutnya, namun nomor HP tetap menjadi atribut identitas utama dalam tenant
- relasi utama antar entitas harus berbasis `id`, bukan nomor HP
- akun pengguna, jika dibuat, harus terhubung ke uploader melalui `guest_uploader_id`
- `guest_token` hanya digunakan untuk mengenali browser guest yang pernah melakukan upload tanpa login pada tenant yang sama
- `guest_token` tidak boleh dipakai sebagai dasar relasi bisnis utama
- `guest_token` tidak boleh diperlakukan sebagai pengganti autentikasi akun pengguna

## 6.2 Aturan Akun Pengguna
- akun pengguna dibuat oleh Admin Tenant dari nomor HP yang sudah masuk ke tenant
- login akun pengguna menggunakan:
  - nomor HP
  - password
- nomor HP login harus menggunakan hasil normalisasi dan tenant yang benar
- satu nomor HP uploader hanya boleh memiliki satu akun pengguna aktif per tenant
- satu uploader hanya boleh memiliki satu akun pengguna
- password wajib disimpan dalam bentuk hash
- untuk MVP, sistem hanya melakukan generate/reset password
- penyampaian password dilakukan manual di luar sistem
- akun yang dibuat atau di-reset oleh Admin Tenant wajib ganti password saat login berikutnya

## 6.3 Aturan Akun Admin Tenant
- akun Admin Tenant dibuat oleh Superadmin
- akun Admin Tenant login menggunakan email dan password
- satu akun Admin Tenant wajib memiliki tepat satu `tenant_id`
- satu email Admin Tenant hanya boleh mewakili satu akun dan satu tenant
- Admin Tenant tidak boleh dipindah otomatis ke tenant lain tanpa proses perubahan data yang tercatat
- reset password akun Admin Tenant dilakukan oleh Superadmin

## 6.4 Aturan Akun Superadmin
- akun Superadmin berada di level platform
- akun Superadmin tidak terikat ke satu tenant tertentu
- reset password akun Superadmin tidak tersedia dari UI
- reset password akun Superadmin hanya boleh dilakukan melalui command Artisan
- saat Superadmin login ke tenant, sistem harus menampilkan konteks tenant yang sedang dibuka

## 6.5 Aturan Upload
- upload guest hanya boleh dilakukan dari link upload yang aktif
- upload oleh pengguna terdaftar hanya boleh dilakukan oleh akun yang aktif
- link upload dapat dibatasi masa berlaku dan jumlah penggunaan
- uploader/pengguna tidak boleh mengisi metadata lanjutan
- sebelum file disimpan, sistem wajib memeriksa kuota storage tenant
- upload baru harus ditolak jika pemakaian storage tenant akan melebihi kuota
- file `public` masuk dengan status awal `pending_review`
- file `internal` masuk dengan status awal `valid`
- file `private` masuk dengan status awal `valid`

## 6.6 Aturan Akses Berkas
- pengunjung publik hanya dapat melihat file dengan:
  - `status = valid`
  - `visibility = public`
- pengguna terdaftar dapat melihat berkas yang terhubung ke nomor HP miliknya dalam tenant aktif
- pengguna terdaftar dapat melihat berkas miliknya sendiri baik `public`, `internal`, maupun `private`
- pengguna terdaftar juga dapat melihat seluruh daftar berkas tenant yang ber-visibility `internal`
- pengguna terdaftar dapat melakukan soft delete pada berkas miliknya
- Admin Tenant hanya dapat melihat seluruh berkas pada tenant miliknya
- Admin Tenant dapat melihat berkas aktif maupun berkas yang sudah di-soft delete
- Admin Tenant dapat memulihkan atau menghapus permanen berkas yang di-soft delete
- Superadmin dapat melihat seluruh berkas lintas tenant atau saat masuk ke tenant tertentu sesuai konteks akses

Aturan visibilitas:
- `public`: dapat dilihat publik jika `status = valid`
- `internal`: dapat dilihat semua user yang login dalam tenant yang sama
- `private`: hanya dapat dilihat uploader pemilik, Admin Tenant, dan Superadmin dalam konteks tenant

## 6.7 Aturan Status File
Status file hanya terdiri dari:
- `pending_review`
- `valid`
- `suspended`

Definisi:
- `pending_review`: file sudah diunggah dan menunggu pemeriksaan Admin Tenant
- `valid`: file telah diperiksa dan dinyatakan layak
- `suspended`: file ditangguhkan sementara dan tidak ditampilkan di publik

Catatan tambahan:
- soft delete bukan bagian dari enum `status`
- soft delete direkomendasikan memakai kolom terpisah seperti `deleted_at`
- berkas yang di-soft delete tidak tampil pada daftar aktif
- file `private` tidak wajib menunggu review admin
- file yang di-soft delete tetap dihitung ke kuota storage tenant sampai dipulihkan atau dihapus permanen

## 6.8 Aturan Metadata
Field metadata berikut hanya boleh diisi / diubah oleh Admin Tenant atau Superadmin dalam konteks tenant:
- judul berkas
- kategori
- tag
- deskripsi singkat
- tipe file final

Catatan:
- file `private` tidak wajib menunggu metadata admin agar bisa dipakai pemiliknya

## 6.9 Aturan Skor
Skor uploader dapat berasal dari:
- jumlah file valid
- jumlah download file publik yang sah
- penyesuaian manual oleh Admin Tenant

Catatan:
- file yang sedang di-soft delete tidak dihitung dalam daftar aktif, leaderboard, maupun skor sampai dipulihkan

Pada profil uploader / halaman skor, sistem menampilkan:
- `last score`

## 6.10 Aturan Leaderboard
### Leaderboard jumlah berkas
- dihitung dari jumlah file `valid`
- tersedia versi mingguan dan bulanan per tenant
- file yang di-soft delete tidak ikut dihitung
- file `private` tidak ikut dihitung

### Leaderboard nilai tertinggi
- dihitung dari skor total uploader
- tersedia minimal untuk bulanan per tenant
- skor total = skor otomatis + penyesuaian manual
- bobot skor hanya mengikuti pengaturan default platform dari Superadmin
- file yang di-soft delete tidak ikut dihitung
- file `private` tidak ikut dihitung

---

## 7. Struktur Modul Aplikasi

## 7.0 Modul Platform Superadmin
Fitur:
- login Superadmin
- dashboard platform
- daftar tenant
- tambah tenant
- edit tenant
- aktif/nonaktif tenant
- login ke tenant
- manajemen akun Admin Tenant
- pengaturan global platform
- utilitas reset password Superadmin berbasis command

## 7.1 Modul Halaman Publik
Fitur:
- beranda file publik per tenant
- pencarian file
- filter kategori
- filter tag
- filter tipe file
- daftar file terbaru
- daftar file populer
- leaderboard mingguan tenant
- leaderboard bulanan tenant

## 7.2 Modul Upload Guest
Fitur:
- validasi tenant aktif
- validasi link upload
- form nama
- form nomor HP
- normalisasi nomor HP
- pilihan public / internal / private
- upload file
- pesan berhasil upload di dalam aplikasi

## 7.3 Modul Portal Pengguna
Fitur:
- login nomor HP + password per tenant
- `remember me` aktif default
- dashboard berkas tenant
- daftar berkas milik sendiri
- daftar berkas internal tenant
- filter status / visibilitas
- detail berkas
- upload berkas baru
- soft delete berkas milik sendiri
- ubah password sendiri

## 7.4 Modul Panel Admin Tenant
Fitur:
- dashboard
- daftar file pending
- daftar semua file
- detail file
- isi metadata
- ubah status file
- pulihkan berkas yang di-soft delete
- hapus permanen berkas
- kategori
- tag
- leaderboard
- manajemen skor
- manajemen link upload
- manajemen akun pengguna berdasarkan nomor HP
- generate/reset password akun pengguna

## 7.5 Modul Panel Superadmin
Fitur:
- manajemen tenant
- manajemen akun Admin Tenant
- login ke tenant
- pengaturan global sistem

---

## 8. Struktur Halaman

## 8.1 Halaman Publik
### Beranda
Menampilkan:
- file publik terbaru tenant
- file paling sering diunduh
- leaderboard mingguan tenant
- leaderboard bulanan tenant
- pencarian cepat

### Daftar File Publik
Menampilkan:
- judul berkas
- kategori
- tag
- tipe file
- uploader
- jumlah download
- tombol unduh

### Detail File Publik
Menampilkan:
- judul berkas
- deskripsi
- kategori
- tag
- tipe file
- uploader
- tanggal upload / validasi
- jumlah download
- tombol unduh

### Halaman Leaderboard
Menampilkan:
- ranking jumlah berkas mingguan
- ranking jumlah berkas bulanan
- ranking nilai tertinggi bulanan

## 8.2 Halaman Upload Guest
### Form Identitas
Field:
- nama
- nomor HP

### Form Upload
Field:
- visibilitas: public / internal / private
- file

## 8.3 Portal Pengguna
### Login Pengguna
Field:
- nomor HP
- password
- remember me: `true` default

Catatan:
- tenant ditentukan dari URL, misalnya `arsipkan.my.id/pemda-a/login`

### Dashboard Pengguna
Menampilkan:
- jumlah total berkas milik sendiri
- jumlah total berkas tenant
- jumlah berkas pending_review
- jumlah berkas valid
- jumlah berkas suspended
- jumlah berkas public
- jumlah berkas internal
- jumlah berkas private

### Daftar Berkas Saya
Menampilkan:
- judul/original name
- visibilitas
- status
- tanggal upload
- kategori jika sudah diisi Admin Tenant
- tombol lihat detail
- tombol hapus soft delete

### Daftar Berkas Tenant
Menampilkan:
- judul/original name
- uploader
- visibilitas
- status
- tanggal upload
- kategori jika sudah diisi Admin Tenant
- tombol lihat detail

Catatan:
- daftar ini hanya menampilkan file tenant dengan visibilitas `internal`

### Upload Berkas Saya
Field:
- visibilitas: public / internal / private
- file

### Profil / Keamanan
Field:
- nomor HP
- nama
- ubah password

## 8.4 Panel Admin Tenant
### Dashboard
Menampilkan:
- total file
- total pending review
- total valid
- total suspended
- total soft deleted
- kuota storage tenant
- storage terpakai
- sisa storage
- top uploader bulan ini
- total download publik
- last uploads

### Manajemen File
Menu:
- file pending review
- semua file
- file soft deleted
- detail file
- edit metadata
- ubah status
- pulihkan file
- hapus permanen

### Manajemen Kategori
Menu:
- daftar kategori
- tambah kategori
- edit kategori
- aktif/nonaktif kategori

### Manajemen Tag
Menu:
- daftar tag
- tambah tag
- edit tag
- hapus tag

### Manajemen Skor
Menu:
- daftar uploader
- detail skor uploader
- ubah skor manual
- riwayat perubahan skor
- tampilkan last score

### Manajemen Link Upload
Menu:
- daftar link upload
- buat link
- aktif/nonaktif link
- atur expired
- atur batas penggunaan

### Manajemen Akun Pengguna
Menu:
- daftar nomor HP uploader
- daftar akun pengguna
- buat akun dari nomor HP
- reset password
- aktif/nonaktif akun

## 8.5 Panel Superadmin
### Manajemen Tenant
Menu:
- daftar tenant
- tambah tenant
- edit tenant
- aktif/nonaktif tenant
- atur kuota storage tenant
- lihat detail tenant
- login ke tenant

### Manajemen Admin Tenant
Menu:
- daftar Admin Tenant
- tambah Admin Tenant
- edit Admin Tenant
- reset password
- aktif/nonaktif akun

### Manajemen Superadmin
Menu:
- daftar akun Superadmin
- tambah akun Superadmin
- aktif/nonaktif akun Superadmin
- reset password via command Artisan

### Pengaturan Sistem
Menu:
- bobot skor upload
- bobot skor download
- kebijakan leaderboard
- batasan sistem lainnya

---

## 9. Struktur Data / ERD Konseptual

## 9.0 Tabel `tenants`
Menyimpan organisasi yang menjadi tenant platform.

Field utama:
- `id`
- `code`
- `name`
- `slug`
- `path_prefix`
- `storage_quota_bytes`
- `storage_used_bytes`
- `storage_warning_threshold_percent`
- `is_active`
- `created_at`
- `updated_at`

Catatan:
- `slug` dipakai sebagai `tenant_slug` pada URL
- `path_prefix` dapat dibentuk dari `/{tenant_slug}`
- `slug` harus unik
- `slug` tidak boleh bentrok dengan reserved slug sistem
- `storage_quota_bytes` menyimpan batas total storage tenant dalam satuan byte
- `storage_used_bytes` menyimpan total storage tenant yang sedang terpakai
- `storage_warning_threshold_percent` dipakai untuk peringatan kapasitas, misalnya `80`

## 9.1 Tabel `guest_uploaders`
Menyimpan profil uploader berdasarkan nomor HP di dalam tenant.

Field utama:
- `id`
- `tenant_id`
- `name`
- `phone_number`
- `phone_number_normalized`
- `guest_token`
- `last_score`
- `first_ip`
- `last_ip`
- `created_at`
- `updated_at`

Catatan:
- `phone_number_normalized` harus unik per tenant
- nomor HP yang sama boleh dipakai di tenant lain
- kombinasi `tenant_id` + `phone_number_normalized` harus unik
- `guest_token` dipakai untuk mengenali browser guest yang pernah upload tanpa login
- `guest_token` tidak menggantikan akun pengguna dan tidak menjadi relasi utama lintas tabel

## 9.2 Tabel `user_accounts`
Menyimpan akun pengguna berbasis nomor HP untuk login portal pengguna.

Field utama:
- `id`
- `tenant_id`
- `guest_uploader_id`
- `password`
- `is_active`
- `must_change_password`
- `password_changed_at`
- `last_login_at`
- `created_by_admin_id`
- `created_at`
- `updated_at`

Catatan:
- `guest_uploader_id` terhubung ke uploader pemilik nomor HP
- nomor HP untuk login diambil dari relasi ke `guest_uploaders`
- kombinasi `tenant_id` + `guest_uploader_id` harus unik
- `password` wajib disimpan hash
- `must_change_password = true` saat akun dibuat atau password di-reset admin
- `tenant_id` pada `user_accounts` harus sama dengan `tenant_id` pada `guest_uploaders` yang dirujuk

## 9.4 Tabel `upload_links`
Menyimpan link upload yang dibuat Admin Tenant.

Field utama:
- `id`
- `tenant_id`
- `code`
- `title`
- `is_active`
- `expires_at`
- `max_usage`
- `usage_count`
- `created_by_admin_id`
- `created_at`
- `updated_at`

Catatan:
- kombinasi `tenant_id` + `code` harus unik

## 9.5 Tabel `files`
Menyimpan seluruh berkas yang diunggah.

Field utama:
- `id`
- `tenant_id`
- `guest_uploader_id`
- `upload_link_id`
- `uploaded_via`
- `original_name`
- `stored_name`
- `extension`
- `mime_type`
- `file_size`
- `visibility`
- `status`
- `title`
- `description`
- `category_id`
- `detected_file_type`
- `final_file_type`
- `uploaded_at`
- `reviewed_at`
- `reviewed_by_admin_id`
- `deleted_at`
- `deleted_by_user_account_id`
- `permanently_deleted_by_admin_id`
- `created_at`
- `updated_at`

Keterangan:
- `uploaded_via`: `guest_link` / `user_portal`
- `visibility`: `public` / `internal` / `private`
- `status`: `pending_review` / `valid` / `suspended`
- soft delete direpresentasikan oleh `deleted_at`
- seluruh foreign key tenant-bound pada `files` harus merujuk entitas dengan `tenant_id` yang sama
- `file_size` menjadi dasar penambahan dan pengurangan pemakaian storage tenant

## 9.6 Tabel `categories`
Menyimpan kategori file.

Field utama:
- `id`
- `tenant_id`
- `name`
- `slug`
- `description`
- `is_active`
- `created_at`
- `updated_at`

Catatan:
- kombinasi `tenant_id` + `slug` harus unik
- kombinasi `tenant_id` + `name` sebaiknya unik

## 9.7 Tabel `tags`
Menyimpan master tag.

Field utama:
- `id`
- `tenant_id`
- `name`
- `created_at`
- `updated_at`

Catatan:
- kombinasi `tenant_id` + `name` harus unik

## 9.8 Tabel `file_tag_map`
Relasi many-to-many antara file dan tag.

Field utama:
- `id`
- `file_id`
- `tag_id`

## 9.9 Tabel `file_downloads`
Menyimpan log unduhan file publik.

Field utama:
- `id`
- `tenant_id`
- `file_id`
- `ip_address`
- `user_agent`
- `downloaded_at`
- `is_counted_for_score`

## 9.10 Tabel `admin_users`
Menyimpan user internal.

Field utama:
- `id`
- `tenant_id`
- `name`
- `email`
- `password`
- `role`
- `is_active`
- `last_login_at`
- `created_at`
- `updated_at`

Nilai `role`:
- `tenant_admin`
- `superadmin`

Catatan:
- jika `role = tenant_admin`, maka `tenant_id` wajib terisi
- jika `role = superadmin`, maka `tenant_id` boleh `NULL`
- satu akun `tenant_admin` hanya boleh memiliki satu `tenant_id`
- email `superadmin` harus unik global
- email `tenant_admin` minimal unik dalam tenant yang sama

## 9.11 Tabel `score_rules`
Menyimpan pengaturan bobot skor.

Field utama:
- `id`
- `upload_valid_point`
- `download_point`
- `is_active`
- `created_by_superadmin_id`
- `created_at`

Catatan:
- tabel ini menyimpan bobot skor default platform
- tidak ada override bobot skor per tenant pada MVP

## 9.12 Tabel `score_adjustments`
Menyimpan histori perubahan skor manual.

Field utama:
- `id`
- `tenant_id`
- `guest_uploader_id`
- `nilai_sebelum`
- `nilai_sesudah`
- `selisih`
- `updated_by_admin_id`
- `updated_at`
- `created_at`

Catatan:
- `selisih` bisa bernilai positif atau negatif
- `last score` uploader bisa dihitung dari nilai terbaru atau disimpan di profil agregat uploader

## 10. Relasi Antar Tabel

- satu `tenant` dapat memiliki banyak `guest_uploaders`
- satu `tenant` dapat memiliki banyak `user_accounts`
- satu `tenant` dapat memiliki banyak `upload_links`
- satu `tenant` dapat memiliki banyak `files`
- satu `tenant` dapat memiliki banyak `categories`
- satu `tenant` dapat memiliki banyak `tags`
- satu `tenant` dapat memiliki banyak `tenant_admin`
- satu `tenant` dapat memakai bobot skor default platform
- satu `tenant` dapat memiliki banyak `score_adjustments`
- satu `guest_uploader` dapat memiliki banyak `files`
- satu `guest_uploader` dapat memiliki satu `user_account`
- satu `upload_link` dapat digunakan oleh banyak `files`
- satu `category` dapat dipakai banyak `files`
- satu `file` dapat memiliki banyak `tags` melalui `file_tag_map`
- satu `file` dapat memiliki banyak `file_downloads`
- satu `guest_uploader` dapat memiliki banyak `score_adjustments`
- satu `admin_user` bertipe `tenant_admin` dapat membuat banyak `upload_links` pada tenantnya
- satu `admin_user` bertipe `tenant_admin` dapat mereview banyak `files` pada tenantnya
- satu `admin_user` bertipe `tenant_admin` dapat membuat banyak `score_adjustments` pada tenantnya
- satu `admin_user` bertipe `tenant_admin` dapat membuat/reset banyak `user_accounts` pada tenantnya
- satu `admin_user` bertipe `superadmin` dapat login ke banyak tenant melalui context switching

---

## 11. Skema Perhitungan Skor

## 11.1 Skor Otomatis
Contoh rumus default:

```text
skor_otomatis = (jumlah_file_valid x bobot_upload_valid) + (jumlah_download_sah x bobot_download)
```

Contoh bobot awal:
- file valid = 10 poin
- download sah = 1 poin

## 11.2 Skor Akhir
```text
skor_akhir = skor_otomatis + total_penyesuaian_manual
```

## 11.3 Last Score
`last score` adalah skor terakhir uploader setelah perubahan manual terbaru diterapkan.

Bisa diambil dengan dua pendekatan:
1. dihitung dinamis dari histori
2. disimpan sebagai nilai agregat terakhir di profil uploader

Untuk performa, lebih baik ada kolom agregat di profil uploader, misalnya:
- `last_score`

---

## 12. Validasi Sistem

## 12.1 Validasi Nomor HP
- wajib diisi
- hanya angka setelah dinormalisasi
- panjang sesuai format yang diterima
- format diseragamkan, misalnya ke awalan `62`
- harus unik dalam tenant yang sama

## 12.2 Validasi Akun Pengguna
- akun hanya bisa dibuat dari nomor HP uploader yang sudah ada
- satu nomor HP hanya memiliki satu akun pengguna aktif
- satu uploader hanya memiliki satu akun pengguna
- password wajib memenuhi kebijakan minimal keamanan
- akun hasil create/reset admin harus menyalakan `must_change_password`

## 12.3 Validasi Akun Admin Tenant
- akun Admin Tenant harus memiliki tepat satu `tenant_id`
- satu akun Admin Tenant tidak boleh terhubung ke lebih dari satu tenant
- tenant yang nonaktif tidak boleh menerima login Admin Tenant baru

## 12.4 Validasi Akun Superadmin
- akun Superadmin tidak boleh memiliki `tenant_id` operasional tetap
- reset password Superadmin hanya boleh melalui command Artisan

## 12.5 Validasi File Upload
- ukuran maksimal file
- whitelist ekstensi yang diizinkan
- cek MIME type
- rename file fisik secara otomatis
- nama asli file tetap disimpan sebagai metadata
- `storage_used_bytes + file_size` tidak boleh melebihi `storage_quota_bytes` tenant
- jika kuota penuh, upload baru harus ditolak dengan pesan yang jelas

## 12.7 Validasi Penghapusan Berkas
- pengguna hanya boleh melakukan soft delete pada berkas miliknya sendiri
- berkas yang sudah di-soft delete tidak boleh tampil di daftar aktif
- hanya Admin Tenant yang boleh memulihkan berkas
- hanya Admin Tenant yang boleh menghapus permanen berkas
- soft delete tidak mengurangi `storage_used_bytes`
- hapus permanen wajib mengurangi `storage_used_bytes` tenant sesuai ukuran file

## 12.6 Validasi Link Upload
- link harus aktif
- link belum expired
- `usage_count` belum melebihi `max_usage` jika dibatasi

---

## 13. Keamanan yang Direkomendasikan

- file baru disimpan dulu di storage nonpublik
- file publik hanya diexpose setelah status `valid`
- file private tidak boleh diakses langsung lewat URL publik permanen
- gunakan nama file fisik acak
- lakukan validasi MIME type dan ekstensi
- batasi ukuran file upload
- rate limiting untuk upload dan login
- simpan password akun pengguna dalam bentuk hash
- simpan password akun Admin Tenant dan Superadmin dalam bentuk hash
- catat IP dan user agent untuk log penting
- kredensial yang dikirim manual sebaiknya hanya berisi password sementara, lalu user dipaksa ganti password saat login pertama
- storage file sebaiknya dipisah per tenant
- cache dan leaderboard harus membawa konteks tenant
- file yang di-soft delete sebaiknya tidak langsung dihapus dari storage sampai ada aksi hapus permanen
- sediakan mekanisme rekalkulasi `storage_used_bytes` tenant jika suatu saat terjadi selisih data

---

## 14. Rekomendasi Teknologi

Stack yang direkomendasikan:
- **Backend:** Laravel 13, Livewire 4, Alpine JS
- **Admin Panel:** Laravel 13, Livewire 4, Alpine JS
- **Portal Pengguna:** Blade / Livewire 4, Alpine JS
- **Frontend publik:** Blade / Livewire 4, Alpine JS
- **Framework CSS:** Bootstrap
- **Database:** MySQL
- **Storage:** local private/public atau object storage
- **Pengiriman password:** manual oleh admin, tanpa integrasi sistem eksternal

Alasan:
- Laravel cocok untuk workflow CRUD, autentikasi, dan upload file
- Laravel 13, Livewire 4, Alpine JS mempercepat pembuatan panel Admin Tenant, Superadmin, dan portal pengguna
- struktur role, tenant scope, dan approval workflow mudah dibangun
- scheduler Laravel cocok untuk leaderboard mingguan dan bulanan per tenant
- karena tidak memakai integrasi sistem eksternal, sistem cukup melakukan generate/reset password

---

## 15. Rekomendasi Tahapan Pengembangan

## 15.1 MVP
Bangun terlebih dahulu:
- login Admin dan Superadmin
- manajemen tenant
- manajemen user Admin Tenant
- manajemen link upload
- upload guest tanpa login
- identifikasi uploader berbasis nomor HP
- pembuatan akun pengguna dari nomor HP yang masuk
- login pengguna dengan nomor HP + password
- portal pengguna untuk melihat berkas milik sendiri
- upload pengguna terdaftar
- status file: pending_review, valid, suspended
- visibilitas file: public, internal, private
- soft delete oleh pengguna
- pemulihan / hapus permanen oleh Admin Tenant
- metadata oleh Admin
- katalog file publik
- download log
- leaderboard dasar per tenant
- perubahan skor manual
- tampilan `last score`
- login Superadmin ke tenant

## 15.2 Tahap Lanjutan
Dapat ditambahkan kemudian:
- scan antivirus
- duplicate file detection berbasis hash
- OTP verifikasi nomor HP
- preview dokumen tertentu
- export laporan
- pengumuman atau tindak lanjut operasional dilakukan manual bila dibutuhkan di tahap lanjut
- statistik dashboard lebih mendalam

---

## 16. Rekomendasi UX/UI

### Untuk Guest Uploader
Buat alur sesingkat mungkin:
1. isi nama
2. isi nomor HP
3. pilih public/internal/private
4. pilih file
5. upload

### Untuk Pengguna Terdaftar
Fokuskan portal pada:
- daftar berkas milik sendiri
- daftar berkas internal tenant yang mudah dicari
- status berkas yang jelas
- pemisahan public/internal/private
- aksi hapus yang jelas dan aman
- upload berkas baru
- ubah password

### Untuk Admin
Fokuskan panel pada:
- antrean file pending
- form metadata yang cepat
- filter uploader / status / kategori
- manajemen skor yang transparan
- pembuatan akun pengguna dari nomor HP yang masuk
- tombol generate/reset password yang jelas
- daftar file soft deleted yang mudah ditinjau

### Untuk Superadmin
Fokuskan panel pada:
- daftar tenant yang mudah dipahami
- pembuatan Admin Tenant yang cepat
- tombol `login ke tenant` yang jelas
- pengaturan global platform yang ringkas dan jelas

### Untuk Halaman Publik
Fokuskan pada:
- pencarian cepat
- filter yang jelas
- file populer
- leaderboard yang mudah dibaca

---

## 17. Kesimpulan

Rancangan akhir aplikasi ini memiliki prinsip:
- upload tetap mudah untuk publik tanpa login
- pengguna dapat memiliki akun berbasis nomor HP jika perlu mengakses arsip miliknya dalam tenant terkait
- akun pengguna dapat melihat berkas miliknya sendiri, baik public, internal, maupun private
- akun pengguna juga dapat melihat seluruh daftar berkas internal dalam tenant yang sama
- akun pengguna dapat menghapus berkas miliknya dengan soft delete
- kontrol penuh pengelolaan berkas tetap berada di Admin Tenant dan Superadmin
- Admin Tenant dapat memulihkan atau menghapus permanen berkas yang di-soft delete
- identitas uploader dibedakan dengan nomor HP, sementara relasi utama antar data memakai `id`
- metadata dijaga konsistensinya oleh Admin Tenant
- publik hanya melihat file yang valid dan publik dalam tenant terkait
- leaderboard dan skor memberi motivasi kontribusi per tenant
- MVP difokuskan pada proses inti tanpa audit log
- tenant dapat dikelola terpusat oleh Superadmin
- satu akun Admin Tenant hanya berada pada satu organisasi
- nomor HP yang sama dapat digunakan di organisasi berbeda, tetapi tetap dipisahkan per tenant

Struktur ini cocok untuk dikembangkan sebagai MVP yang stabil dan dapat diperluas ke tahap berikutnya tanpa perlu mengubah fondasi utama sistem.
