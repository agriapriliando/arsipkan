# Langkah Sistematis Pembuatan Aplikasi Arsipkan

## 1. Tujuan Dokumen

Dokumen ini merangkum langkah-langkah sistematis dan berurutan untuk membangun aplikasi **Arsipkan** berdasarkan:

- [rancangan_aplikasi_arsipkan.md](C:/laragon/www/arsipkandocs/docs/rancangan_aplikasi_arsipkan.md:1)
- [erd_dan_flow_menu_aplikasi_arsipkan.md](C:/laragon/www/arsipkandocs/docs/erd_dan_flow_menu_aplikasi_arsipkan.md:1)

Dokumen ini ditujukan sebagai urutan implementasi praktis untuk stack:

- Laravel 13
- Livewire 4
- Alpine JS
- Bootstrap 5
- MySQL

---

## 2. Prinsip Implementasi

Sebelum mulai coding, tetapkan prinsip berikut sebagai fondasi:

- aplikasi memakai arsitektur **multitenant shared database, shared schema**
- tenant di-resolve dari path: `arsipkan.my.id/{tenant_slug}/...`
- semua data operasional tenant-bound wajib memiliki `tenant_id`
- relasi utama antar data memakai `id`, bukan nomor HP
- nomor HP dipakai sebagai identitas login user uploader
- nomor HP boleh sama di tenant berbeda, tetapi unik di tenant yang sama
- `guest_token` hanya untuk mengenali browser guest uploader secara ringan
- tidak ada integrasi API eksternal pada MVP
- tidak ada audit log pada MVP
- file mendukung `public`, `internal`, dan `private`
- soft delete dilakukan user uploader, restore dan permanent delete dilakukan admin tenant
- kuota storage berlaku per tenant

---

## 3. Urutan Pembangunan

## 3.1 Siapkan Proyek Dasar

1. Buat project Laravel 13 baru.
2. Konfigurasi koneksi database MySQL.
3. Pasang Livewire 4.
4. Pasang Bootstrap 5.
5. Siapkan layout dasar Blade untuk:
   - publik tenant
   - user uploader
   - admin tenant
   - superadmin
6. Siapkan struktur direktori untuk:
   - `app/Models`
   - `app/Livewire`
   - `app/Http/Middleware`
   - `app/Services`
   - `app/Policies`
   - `app/Console/Commands`

Hasil tahap ini:

- aplikasi Laravel berjalan
- Livewire dan Bootstrap siap dipakai
- struktur kode siap menerima fitur multitenant

---

## 3.2 Bangun Fondasi Multitenancy

1. Buat tabel `tenants`.
2. Isi field utama:
   - `code`
   - `name`
   - `slug`
   - `path_prefix`
   - `storage_quota_bytes`
   - `storage_used_bytes`
   - `storage_warning_threshold_percent`
   - `is_active`
3. Buat daftar reserved slug sistem.
4. Buat middleware atau service tenant resolver.
5. Tenant resolver harus:
   - membaca segmen pertama URL
   - mencari tenant berdasarkan `slug`
   - memastikan tenant aktif
   - menyimpan tenant aktif ke request/container
6. Pisahkan area route:
   - route tenant: `/{tenant_slug}/...`
   - route superadmin: `/superadmin/...`

Hasil tahap ini:

- sistem bisa mengetahui tenant aktif dari URL
- seluruh request tenant memiliki konteks tenant yang jelas

---

## 3.3 Buat Migration Seluruh Tabel Inti

Bangun migration sesuai urutan relasi berikut:

1. `tenants`
2. `guest_uploaders`
3. `admin_users`
4. `user_accounts`
5. `upload_links`
6. `categories`
7. `tags`
8. `files`
9. `file_tag_map`
10. `file_downloads`
11. `score_rules`
12. `score_adjustments`

Pastikan constraint penting dibuat sejak awal:

- `tenants.slug` unik
- `guest_uploaders`: unik `tenant_id + phone_number_normalized`
- `user_accounts`: unik `tenant_id + guest_uploader_id`
- `upload_links`: unik `tenant_id + code`
- `categories`: unik `tenant_id + slug`
- `tags`: unik `tenant_id + name`
- `admin_users`:
  - `superadmin.email` unik global
  - `tenant_admin.email` minimal unik dalam tenant

Tambahkan `softDeletes()` pada `files`.

Hasil tahap ini:

- skema database final MVP tersedia
- constraint data utama sudah aman sejak awal

---

## 3.4 Buat Model dan Relasi Eloquent

1. Buat model:
   - `Tenant`
   - `GuestUploader`
   - `AdminUser`
   - `UserAccount`
   - `UploadLink`
   - `File`
   - `Category`
   - `Tag`
   - `FileDownload`
   - `ScoreRule`
   - `ScoreAdjustment`
2. Definisikan relasi utama:
   - `Tenant` memiliki banyak data operasional
   - `GuestUploader` memiliki banyak `files`
   - `GuestUploader` memiliki satu `user_account`
   - `UserAccount` milik satu `guest_uploader`
   - `UploadLink` memiliki banyak `files`
   - `File` milik satu `tenant`
   - `File` milik satu `guest_uploader`
   - `File` milik satu `category`
   - `File` memiliki banyak `tags`
3. Tambahkan scope tenant jika dibutuhkan.
4. Pastikan setiap query data tenant-bound mudah dibatasi berdasarkan `tenant_id`.

Hasil tahap ini:

- struktur data aplikasi siap dipakai oleh service dan UI

---

## 3.5 Bangun Seeder Awal

1. Buat seed `score_rules` default platform.
2. Buat akun `superadmin` awal.
3. Buat satu contoh `tenant`.
4. Buat satu contoh `tenant_admin`.
5. Siapkan data contoh kategori dan tag tenant.

Hasil tahap ini:

- sistem bisa langsung diuji tanpa input manual dari nol

---

## 3.6 Bangun Sistem Autentikasi Internal

Autentikasi dibangun per area.

### Superadmin

1. Buat login superadmin dengan `email + password`.
2. Pastikan hanya `role = superadmin` yang bisa masuk area `/superadmin`.
3. Buat command Artisan reset password superadmin.

### Admin Tenant

1. Gunakan tabel `admin_users`.
2. Buat login admin tenant dengan `email + password`.
3. Pastikan hanya `role = tenant_admin` yang bisa masuk area admin tenant.
4. Pastikan `tenant_id` admin sesuai dengan tenant aktif.

### User Uploader

1. Login memakai:
   - nomor HP
   - password
2. Alur login:
   - normalisasi nomor HP
   - cari `guest_uploader` berdasarkan tenant aktif
   - cari `user_account` dari `guest_uploader_id`
   - cek `is_active`
   - cek password
3. Aktifkan `remember me` default.
4. Jika `must_change_password = true`, paksa user ubah password dulu.

Hasil tahap ini:

- seluruh aktor internal bisa login sesuai area masing-masing

---

## 3.7 Bangun Otorisasi dan Policy

1. Buat policy atau gate untuk `File`.
2. Buat policy untuk `Category`, `Tag`, `UploadLink`, dan `UserAccount`.
3. Atur hak akses:
   - `guest uploader` hanya upload via link aktif
   - `user uploader` hanya soft delete file miliknya
   - `user uploader` bisa lihat:
     - file miliknya sendiri
     - seluruh file `internal` tenant
   - `admin tenant` bisa review, edit metadata, restore, dan permanent delete file tenant
   - `superadmin` bisa mengelola platform dan masuk ke konteks tenant
4. Pastikan file `private` hanya bisa diakses oleh:
   - uploader pemilik
   - admin tenant
   - superadmin dalam konteks tenant

Hasil tahap ini:

- batas akses antar role menjadi konsisten dan aman

---

## 3.8 Bangun Manajemen Tenant oleh Superadmin

1. Buat halaman daftar tenant.
2. Buat form tambah tenant.
3. Buat form edit tenant.
4. Tambahkan aktif/nonaktif tenant.
5. Tambahkan pengaturan kuota storage:
   - `storage_quota_bytes`
   - `storage_warning_threshold_percent`
6. Tambahkan aksi `login ke tenant`.

Hasil tahap ini:

- superadmin dapat mengelola organisasi dan kuotanya

---

## 3.9 Bangun Manajemen Admin Tenant

1. Buat CRUD `admin_users` untuk role `tenant_admin`.
2. Validasi bahwa satu admin tenant hanya punya satu `tenant_id`.
3. Tambahkan aktif/nonaktif akun.
4. Tambahkan reset password admin tenant oleh superadmin.

Hasil tahap ini:

- tenant bisa memiliki admin operasional yang dikelola pusat

---

## 3.10 Bangun Master Data Tenant

1. Buat CRUD kategori.
2. Buat CRUD tag.
3. Batasi semua data berdasarkan `tenant_id`.
4. Pastikan kategori dan tag hanya dapat dikelola oleh admin tenant dan superadmin dalam konteks tenant.

Hasil tahap ini:

- data klasifikasi file siap digunakan saat review

---

## 3.11 Bangun Manajemen Link Upload

1. Buat CRUD `upload_links`.
2. Setiap link memiliki:
   - `code`
   - `title`
   - `is_active`
   - `expires_at`
   - `max_usage`
   - `usage_count`
3. Tambahkan validasi:
   - tenant aktif
   - link aktif
   - belum expired
   - `usage_count` belum melewati `max_usage`

Hasil tahap ini:

- tenant bisa membuka jalur upload guest yang terkontrol

---

## 3.12 Bangun Alur Guest Upload

1. Buat halaman upload berdasarkan `tenant_slug` dan `upload_link code`.
2. Guest mengisi:
   - nama
   - nomor HP
   - visibilitas
   - file
3. Normalisasi nomor HP.
4. Cari atau buat `guest_uploader`.
5. Buat atau pakai kembali `guest_token` di browser.
6. Cek kuota tenant:
   - ambil `storage_used_bytes`
   - bandingkan dengan `storage_quota_bytes`
7. Jika kuota penuh, tolak upload.
8. Simpan file ke storage nonpublik.
9. Buat record `files`.
10. Tentukan status awal:
   - `public` -> `pending_review`
   - `internal` -> `valid`
   - `private` -> `valid`
11. Tambahkan `storage_used_bytes`.

Hasil tahap ini:

- guest uploader sudah bisa mengunggah file tanpa login

---

## 3.13 Bangun Manajemen Akun User Uploader

1. Admin tenant melihat daftar nomor HP uploader yang sudah masuk.
2. Admin tenant membuat akun dari `guest_uploader` yang ada.
3. Simpan:
   - `tenant_id`
   - `guest_uploader_id`
   - password hash
   - `is_active`
   - `must_change_password`
4. Sediakan generate/reset password.
5. Password disampaikan manual oleh admin tenant.

Hasil tahap ini:

- uploader yang sudah pernah upload dapat diberi akun login

---

## 3.14 Bangun Portal User Uploader

1. Buat dashboard user uploader.
2. Buat halaman:
   - daftar berkas saya
   - daftar berkas tenant
   - detail berkas
   - upload berkas
   - profil / ubah password
3. Aturan tampilan:
   - daftar berkas saya menampilkan file miliknya sendiri
   - daftar berkas tenant menampilkan file `internal` tenant
4. Tambahkan upload file dari user login.
5. Berlakukan cek kuota yang sama seperti guest upload.
6. Tambahkan soft delete file milik sendiri.

Hasil tahap ini:

- user uploader mendapatkan portal dasar yang lengkap untuk MVP

---

## 3.15 Bangun Review dan Manajemen File oleh Admin Tenant

1. Buat daftar file pending review.
2. Buat halaman detail file.
3. Admin tenant dapat mengisi:
   - judul
   - deskripsi
   - kategori
   - tag
   - tipe file final
4. Admin tenant dapat mengubah status:
   - `pending_review`
   - `valid`
   - `suspended`
5. Pastikan hanya file `public` yang wajib melewati antrian review.
6. File `internal` dan `private` boleh langsung valid sesuai aturan awal.

Hasil tahap ini:

- kualitas arsip tenant dapat dikendalikan

---

## 3.16 Bangun Soft Delete, Restore, dan Permanent Delete

1. User uploader hanya boleh soft delete file miliknya.
2. Soft delete mengisi:
   - `deleted_at`
   - `deleted_by_user_account_id`
3. File soft deleted:
   - tidak muncul di daftar aktif
   - tidak dihitung di daftar aktif, leaderboard, dan skor
   - tetap memakai kuota storage
4. Admin tenant dapat:
   - memulihkan file
   - menghapus permanen file
5. Permanent delete harus:
   - menghapus record
   - menghapus file fisik sesuai kebijakan
   - mengurangi `storage_used_bytes`

Hasil tahap ini:

- siklus hidup file sesuai rancangan MVP

---

## 3.17 Bangun Katalog Publik Tenant

1. Buat beranda publik tenant.
2. Tampilkan hanya file dengan:
   - `visibility = public`
   - `status = valid`
   - bukan soft deleted
3. Tambahkan pencarian.
4. Tambahkan filter kategori, tag, dan tipe file.
5. Buat halaman detail file publik.
6. Buat proses download file publik.

Hasil tahap ini:

- tenant memiliki katalog file publik yang bisa diakses umum

---

## 3.18 Bangun Pencatatan Download dan Skor

1. Saat file publik diunduh, buat record `file_downloads`.
2. Tentukan apakah download dihitung untuk skor.
3. Siapkan `score_rules` default platform:
   - poin upload valid
   - poin download sah
4. Hitung `last_score` uploader.
5. Buat `score_adjustments` untuk penyesuaian manual admin tenant.
6. Tampilkan leaderboard mingguan dan bulanan per tenant.

Hasil tahap ini:

- kontribusi uploader dapat diukur dan ditampilkan

---

## 3.19 Bangun Pengelolaan Kuota Storage

1. Tampilkan kuota tenant pada dashboard admin tenant.
2. Tampilkan:
   - total kuota
   - storage terpakai
   - sisa storage
   - indikator mendekati batas
3. Superadmin dapat mengubah kuota tenant.
4. Tambahkan validasi upload:
   - `storage_used_bytes + file_size <= storage_quota_bytes`
5. Soft delete tidak mengurangi kuota.
6. Permanent delete mengurangi kuota.
7. Buat command rekalkulasi kuota tenant sebagai pengaman data.

Hasil tahap ini:

- pemakaian storage tenant tetap terkendali dan terukur

---

## 3.20 Bangun Command dan Utilitas Sistem

1. Command reset password superadmin.
2. Command rekalkulasi storage tenant.
3. Jika perlu, command normalisasi data nomor HP lama.
4. Jika perlu, command rebuild leaderboard.

Hasil tahap ini:

- operasi administratif penting bisa dilakukan dengan aman

---

## 3.21 Bangun Validasi dan Pengamanan

1. Validasi nomor HP.
2. Validasi file upload:
   - ukuran maksimum
   - MIME type
   - ekstensi
3. Gunakan nama file fisik acak.
4. Simpan file di storage nonpublik.
5. Gunakan rate limiting untuk:
   - login
   - upload
6. Pastikan seluruh query tenant-bound selalu membawa konteks tenant.

Hasil tahap ini:

- risiko dasar keamanan MVP sudah ditangani

---

## 3.22 Bangun UI Bertahap

Urutan UI yang disarankan:

1. login superadmin
2. dashboard superadmin
3. CRUD tenant
4. CRUD admin tenant
5. login admin tenant
6. dashboard admin tenant
7. kategori, tag, dan link upload
8. guest upload
9. manajemen akun user uploader
10. portal user uploader
11. review file
12. katalog publik
13. leaderboard

Tujuannya:

- fondasi administratif selesai dulu
- jalur upload dibuka setelah kontrol tenant siap
- fitur publik dibangun setelah file lifecycle stabil

---

## 3.23 Lakukan Pengujian Sistematis

Lakukan pengujian minimal berikut:

1. tenant A tidak bisa melihat data tenant B
2. nomor HP yang sama bisa didaftarkan di tenant berbeda
3. upload guest berhasil pada tenant aktif
4. upload ditolak jika link nonaktif atau expired
5. upload ditolak jika kuota penuh
6. file `public` masuk `pending_review`
7. file `internal` langsung `valid`
8. file `private` langsung `valid`
9. user uploader hanya bisa soft delete file miliknya
10. admin tenant bisa restore dan permanent delete
11. permanent delete mengurangi kuota tenant
12. superadmin bisa login ke tenant
13. reset password superadmin hanya bisa lewat Artisan

---

## 4. Urutan Prioritas MVP

Jika ingin implementasi cepat, gunakan prioritas ini:

1. multitenancy dan tenant resolver
2. migration dan model inti
3. autentikasi superadmin dan admin tenant
4. CRUD tenant dan admin tenant
5. link upload
6. guest upload
7. akun user uploader
8. portal user uploader
9. review file
10. soft delete, restore, permanent delete
11. kuota storage
12. katalog publik
13. skor dan leaderboard

---

## 5. Hasil Akhir yang Diharapkan

Jika seluruh langkah di atas selesai, maka aplikasi MVP yang dihasilkan sudah memiliki:

- multitenancy berbasis path
- superadmin platform
- admin tenant per organisasi
- guest upload tanpa login
- user uploader berbasis nomor HP
- visibilitas file `public`, `internal`, `private`
- moderasi file publik
- soft delete dan restore
- kuota storage per tenant
- katalog publik tenant
- skor dan leaderboard dasar

Dokumen ini sebaiknya dipakai sebagai urutan kerja implementasi, bukan sekadar referensi konsep.
