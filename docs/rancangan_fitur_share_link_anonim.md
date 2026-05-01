# Rancangan Fitur Share Link Anonim

## Tujuan

Fitur ini ditujukan agar berkas tertentu dapat dibagikan dan diunduh tanpa login melalui link khusus, tetapi tetap tanpa merusak konsep visibilitas utama aplikasi yaitu `public`, `internal`, dan `private`.

Fitur ini bukan berarti semua file menjadi URL publik permanen. Fitur ini adalah mekanisme distribusi tambahan yang bisa diaktifkan dan dinonaktifkan secara terkontrol.

## Masalah yang Ingin Diselesaikan

Kebutuhan pengguna adalah:

- seseorang tanpa akun dapat mengunduh file langsung
- cukup memiliki link
- link dapat dibuka saat akses dibagikan
- link dapat ditutup kembali kapan saja

Namun jika semua file `public`, `internal`, dan `private` dibuka langsung lewat URL permanen, maka:

- klasifikasi data menjadi rusak
- file sensitif mudah bocor jika link tersebar
- sistem sulit melakukan revoke
- aturan keamanan dokumen tidak lagi konsisten

Karena itu, fitur yang direkomendasikan bukan membuka semua storage secara publik, tetapi menambahkan mode `shared by link`.

## Prinsip Rekomendasi

- `visibility` tetap dipertahankan sebagai klasifikasi data utama.
- akses anonim dipisahkan dari `visibility`.
- file tetap disimpan di storage privat.
- link akses anonim harus memakai token acak, bukan ID file langsung.
- link harus bisa dicabut kapan saja.
- link sebaiknya dapat diberi masa berlaku.
- aktivitas pembukaan dan pengunduhan link harus dapat diaudit.

## Konsep Inti

Pisahkan dua hal berikut:

- `visibility`: siapa yang boleh melihat file dalam aturan normal aplikasi
- `share link`: pengecualian terkontrol agar file tertentu bisa diakses anonim lewat URL khusus

Dengan begitu:

- file `public` tetap tampil di katalog publik jika `valid`
- file `internal` tetap tidak tampil di publik
- file `private` tetap tidak tampil di publik
- tetapi file `internal` atau `private` tetap bisa dibagikan lewat link anonim jika kebijakan mengizinkan

## Rekomendasi Perilaku Fitur

### Saat Share Link Diaktifkan

1. admin atau pemilik file menekan aksi `Bagikan Link`
2. sistem memvalidasi apakah file boleh dibagikan
3. sistem membuat token acak yang panjang dan sulit ditebak
4. sistem menyimpan status share aktif
5. sistem menghasilkan URL seperti `/s/{token}`
6. siapa pun yang memiliki link dapat mengakses file tanpa login selama link masih aktif

### Saat Share Link Dinonaktifkan

Ada dua mode penutupan link yang bisa didukung sistem.

#### Mode 1: Pause/Resume

1. admin atau pemilik file menekan aksi `Nonaktifkan Link`
2. sistem menandai link sebagai nonaktif
3. token lama tetap disimpan
4. URL lama tidak bisa dipakai selama status masih nonaktif
5. jika admin atau pemilik file menekan aksi `Aktifkan Kembali Link`, token lama dihidupkan kembali
6. URL lama kembali bisa digunakan

Mode ini cocok jika pengguna ingin kepraktisan dan ingin memakai URL yang sama di kemudian hari.

#### Mode 2: Revoke Permanen

1. admin atau pemilik file menekan aksi `Cabut Link Permanen`
2. sistem menandai link sebagai dicabut permanen
3. token lama tidak boleh dipakai lagi
4. jika share dibuka lagi di masa depan, sistem harus membuat token baru
5. URL lama tetap mati permanen

Mode ini penting jika tujuan utamanya adalah memutus akses lama secara tegas.

## Kenapa Tidak Cukup Boolean Saja

Field `is_shared_by_link` saja tidak cukup.

Jika hanya ada boolean:

- link lama sulit diputus dengan aman
- tidak ada jejak kapan dibuka atau ditutup
- tidak ada dukungan masa berlaku
- tidak ada kemampuan rotasi token

Karena itu, minimal dibutuhkan metadata tambahan.

## Rekomendasi Struktur Data

### : Tabel Baru `file_share_links`

Cocok jika ingin desain lebih rapi dan scalable.

Field yang direkomendasikan:

- `id`
- `tenant_id`
- `file_id`
- `token`
- `status`
- `allow_anonymous`
- `created_by_user_type`
- `created_by_user_id`
- `created_at`
- `activated_at`
- `deactivated_at`
- `expires_at`
- `revoked_at`
- `last_accessed_at`
- `access_count`
- `notes`
- `updated_at`

Nilai `status` yang direkomendasikan:

- `active`
- `inactive`
- `revoked`
- `expired`

Kelebihan:

- audit lebih baik
- bisa simpan histori
- bisa mendukung rotasi atau multi-link
- lebih fleksibel untuk masa depan

Kekurangan:

- implementasi lebih banyak

Rekomendasi terbaik untuk aplikasi ini: **tabel `file_share_links`**.

## Rekomendasi Aturan Bisnis

### Aturan Dasar

- hanya file yang masih ada secara fisik yang boleh dibagikan
- file yang sudah dihapus soft delete tidak boleh dibagikan
- link anonim hanya aktif jika status share masih `active`
- token harus unik
- token tidak boleh memakai ID file, slug file, atau pola yang mudah ditebak
- link dengan status `inactive` tidak boleh bisa diakses sampai diaktifkan kembali
- link dengan status `revoked` tidak boleh bisa diaktifkan kembali

### Aturan Terkait Visibility

Rekomendasi paling aman:

- file `public`: boleh dibagikan lewat share link
- file `internal`: boleh dibagikan lewat share link oleh admin tenant
- file `private`: boleh dibagikan lewat share link oleh oleh admin tenant dan pemilik file

File `private` diizinkan untuk share link anonim, harus dipahami bahwa:

- file itu tetap berlabel `private` dalam sistem
- tetapi secara distribusi, file tersebut sedang dibuka untuk siapa pun yang memegang link

Karena itu UI harus memberi peringatan yang jelas.

### Aturan Terkait Status File

Rekomendasi:

- `public` hanya boleh dibagikan jika `status = valid`
- `internal` hanya boleh dibagikan jika `status = valid`
- `private` minimal harus tidak soft deleted, dan idealnya juga `valid`
- file `pending_review` tidak boleh dibagikan anonim
- file `suspended` tidak boleh dibagikan anonim

## Rekomendasi Route

Gunakan route khusus anonim, misalnya:

- `GET /s/{token}` untuk melihat informasi file
- `GET /s/{token}/download` untuk mengunduh file

## Rekomendasi Controller Flow

### Endpoint Aktifkan Link Baru

1. validasi pengguna berhak membuka share link
2. validasi file masih eligible
3. revoke atau nonaktifkan link aktif lama jika desain hanya mengizinkan satu link aktif
4. generate token acak
5. simpan record link aktif
6. kembalikan URL share

### Endpoint Aktifkan Kembali Link Lama

1. validasi pengguna berhak mengelola share link
2. cari link dengan status `inactive`
3. pastikan link belum expired dan belum `revoked`
4. ubah status menjadi `active`
5. isi `activated_at`
6. URL lama kembali valid

Endpoint ini hanya berlaku untuk mode `pause/resume`.

### Endpoint Nonaktifkan Link

1. validasi pengguna berhak menutup link
2. cari link aktif milik file
3. ubah status menjadi `inactive`
4. isi `deactivated_at`
5. token tetap disimpan

Endpoint ini dipakai untuk mode `pause/resume`.

### Endpoint Cabut Link Permanen

1. validasi pengguna berhak mencabut link
2. cari link aktif atau nonaktif milik file
3. ubah status menjadi `revoked`
4. isi `revoked_at`
5. jika model memakai satu token di tabel file, token harus dihapus atau di-rotate

Endpoint ini dipakai untuk mode `revoke permanen`.

### Endpoint Download Anonim

1. cari link berdasarkan token
2. pastikan status `active`
3. pastikan belum expired
4. pastikan file masih ada dan masih eligible
5. catat akses
6. stream file dari storage privat

## Rekomendasi Hak Akses

- `superadmin`: boleh membuka dan menutup link dalam konteks tenant
- `tenant_admin`: boleh membuka dan menutup link untuk file tenant, termasuk internal dan private
- `user uploader`: hanya untuk file miliknya sendiri

Rekomendasi awal:

- tahap 1: hanya `tenant_admin` dan `superadmin`
- tahap 2: `user uploader` bisa untuk file miliknya sendiri

## Rekomendasi Audit Log

Ini adalah rancangan untuk masa akan datang, untuk tahap sekarang tidak perlu audit log

- link dibuat
- link diaktifkan
- link diaktifkan kembali
- link dinonaktifkan
- link dicabut
- link expired
- file diunduh lewat link
- percobaan akses ke link yang sudah tidak aktif

Field audit yang berguna:

- `file_id`
- `share_link_id`
- `tenant_id`
- `event`
- `ip_address`
- `user_agent`
- `performed_by_user_type`
- `performed_by_user_id`
- `created_at`

## Rekomendasi UI

Tambahkan aksi pada halaman detail file atau daftar file:

- `Bagikan Link`
- `Salin Link`
- `Nonaktifkan Link`
- `Aktifkan Kembali Link`
- `Cabut Link Permanen`

Informasi yang sebaiknya ditampilkan:

- status link: aktif / nonaktif / expired
- tanggal dibuat
- jumlah unduhan
- terakhir diakses

Untuk file sensitif, tampilkan peringatan seperti:

`Siapa pun yang memiliki link ini dapat mengunduh file tanpa login selama link masih aktif.`

## Rekomendasi Masa Berlaku

- link akan selalu aktif sampai di nonaktifkan

## Dua Mode Penutupan Link

### Mode Pause/Resume

Karakteristik:

- token lama tetap disimpan
- URL lama mati sementara saat status `inactive`
- URL lama hidup lagi saat link diaktifkan kembali
- cocok untuk kebutuhan operasional yang ingin mempertahankan URL yang sama

Rekomendasi penggunaan:

- gunakan hanya jika perilaku ini memang disengaja
- beri label UI yang jelas seperti `Nonaktifkan Link` dan `Aktifkan Kembali Link`
- jangan pakai istilah `Cabut` untuk mode ini

### Mode Revoke Permanen

Karakteristik:

- token lama tidak dipakai lagi
- URL lama mati permanen
- jika link dibuka lagi, sistem membuat token baru
- lebih aman untuk file sensitif

Rekomendasi penggunaan:

- jadikan ini perilaku default untuk file sensitif
- pakai aksi UI terpisah seperti `Cabut Link Permanen`

## Revoke dan Rotasi Token

Rekomendasi penting:

- pada mode `pause/resume`, token lama boleh tetap disimpan tetapi tidak boleh valid saat status `inactive`
- pada mode `revoke permanen`, token lama tidak boleh tetap valid
- lakukan revoke permanen dengan mengubah status menjadi `revoked`
- jika model memakai satu token di tabel file, token harus dihapus atau di-rotate saat revoke permanen

Dengan begitu:

- pada mode `pause/resume`, URL lama bisa hidup lagi jika memang diaktifkan kembali
- pada mode `revoke permanen`, URL lama langsung mati permanen
- jika share dibuka lagi setelah revoke permanen, sistem membuat token baru
- pengguna yang menyimpan link lama tidak bisa memakai URL sebelumnya setelah revoke permanen

## Rekomendasi Penyimpanan File

File tetap sebaiknya berada di disk `local` private seperti arsitektur aplikasi sekarang.

Jangan:

- memindahkan file `internal` atau `private` ke `public/storage`
- membuat URL fisik storage menjadi publik permanen
- mengandalkan nama file asli sebagai URL

Pendekatan yang benar adalah:

- file tetap private
- controller anonim membaca token
- controller men-stream file dari storage private

## Pertimbangan Keamanan

Risiko utama fitur ini adalah kebocoran link.

Karena itu:

- token harus acak dan panjang
- link harus dapat dicabut
- perbedaan antara `inactive` dan `revoked` harus jelas di sistem dan UI
- pertimbangkan rate limit

Yang perlu dipahami:

- jika seseorang memiliki link valid, sistem memang menganggap orang itu berhak mengunduh
- jadi kontrol utamanya ada pada kerahasiaan token dan kemampuan revoke

## Dampak terhadap Desain Saat Ini

Fitur ini cocok dengan arsitektur aplikasi sekarang karena:

- file memang sudah disimpan di storage private
- download memang sudah melalui controller
- `FilePolicy` bisa tetap dipakai untuk akses normal
- akses anonim cukup ditambahkan sebagai jalur terpisah

Yang perlu dijaga:

- jangan mencampur route anonim dengan route login biasa
- jangan mengubah arti `visibility`
- jangan menghapus aturan bahwa `private` tidak boleh punya URL publik permanen

Catatan penting:

share link anonim bukan URL publik permanen. Ini adalah akses sementara atau akses terkontrol berbasis token.

## Tahapan Implementasi yang Direkomendasikan

### Tahap 1

- tambah tabel `file_share_links`
- buat generate link anonim
- buat nonaktifkan link
- buat cabut link permanen
- buat endpoint download anonim
- catat access count

### Tahap 2

- tambah halaman manajemen link di admin tenant

### Tahap 3

- dukung beberapa link per file

## Rekomendasi Akhir

Pendekatan terbaik untuk kebutuhan ini adalah:

- tetap pertahankan `public`, `internal`, `private` sebagai klasifikasi utama
- tambahkan fitur terpisah bernama `share link anonim`
- file tetap di storage private
- akses anonim hanya melalui token acak
- link bisa diaktifkan
- link bisa dinonaktifkan sementara dengan mode `pause/resume`
- link bisa dicabut total dengan mode `revoke permanen`
- link lama mati setelah revoke permanen
- aktivitas penting dicatat

Dengan desain ini, kebutuhan "siapa pun yang punya link bisa langsung unduh tanpa login" dapat terpenuhi, tetapi kontrol keamanan dan tata kelola file tetap terjaga.
