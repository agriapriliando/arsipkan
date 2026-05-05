# Rancangan Fitur Link Unggah Utama

## Tujuan

Fitur ini ditujukan agar tenant dapat memiliki satu `link upload utama` atau `link upload default` yang menjadi jalur unggah paling direkomendasikan bagi user uploader.

Dengan fitur ini, dashboard user uploader dapat menampilkan tombol unggah yang langsung jelas dan tidak memaksa user memilih dari banyak link aktif terlebih dahulu.

## Masalah yang Ingin Diselesaikan

Kondisi saat ini:

- dashboard user menampilkan daftar semua link upload aktif
- user harus memilih sendiri link yang ingin dipakai
- jika tenant memiliki banyak link, user bisa bingung mana yang resmi atau paling utama
- admin belum memiliki cara menandai satu link sebagai jalur unggah utama

Akibatnya:

- pengalaman unggah lebih lambat
- potensi salah memilih link lebih tinggi
- dashboard tidak memiliki CTA unggah yang paling menonjol

## Sasaran Perilaku

Perilaku yang diinginkan:

- admin tenant dapat menetapkan satu link upload sebagai `utama`
- dashboard user uploader menampilkan tombol utama `Upload Berkas Sekarang`
- tombol tersebut mengarah langsung ke link unggah utama tenant
- daftar seluruh link aktif tetap tersedia sebagai opsi tambahan

## Prinsip Rekomendasi

- setiap tenant maksimal memiliki satu link upload utama
- link utama harus tetap memenuhi syarat `usable`
- penetapan link utama adalah keputusan admin, bukan hasil pemilihan otomatis penuh oleh sistem
- dashboard boleh memakai fallback tampilan jika link utama tidak tersedia
- daftar link aktif yang ada sekarang tetap dipertahankan

## Rekomendasi Desain Data

### Opsi Terbaik: Tambah Kolom `is_primary` di Tabel `upload_links`

Tambahkan field boolean:

- `is_primary`

Nilai default:

- `false`

Alasan memilih pendekatan ini:

- status utama adalah atribut milik link
- implementasinya sederhana
- mudah dipakai di halaman admin link upload
- mudah difilter di dashboard user
- tidak perlu sinkronisasi dua arah antara tabel tenant dan upload link

## Aturan Bisnis

Aturan yang direkomendasikan:

- hanya satu `upload_link` per tenant yang boleh memiliki `is_primary = true`
- link yang dijadikan utama sebaiknya masih `is_active = true`
- link yang dijadikan utama sebaiknya belum expired
- link yang dijadikan utama sebaiknya belum mencapai `max_usage`
- link non-usable tidak boleh dipilih sebagai link utama

Jika admin mencoba menjadikan link tidak usable sebagai utama, sistem sebaiknya menolak dengan pesan validasi yang jelas.

## Perilaku Dashboard User

### Jika Link Utama Ada dan Usable

Dashboard menampilkan blok CTA utama di area atas, misalnya:

- judul: `Link Unggah Utama`
- deskripsi singkat: `Gunakan jalur unggah resmi organisasi untuk mengirim berkas`
- tombol utama: `Upload Berkas Sekarang`

Tombol mengarah ke:

- `route('tenant.upload.show', ['tenant_slug' => ..., 'code' => $primaryUploadLink->code])`

### Jika Link Utama Tidak Ada

Dashboard tidak perlu error.

Pilihan terbaik:

- tetap tampilkan daftar link aktif seperti sekarang
- jika ada setidaknya satu link aktif usable, sistem boleh menampilkan CTA memakai link aktif pertama
- label CTA harus netral, misalnya `Buka Link Unggah`

### Jika Link Utama Ada tetapi Tidak Usable

Rekomendasi terbaik:

- status `is_primary` tidak dihapus otomatis
- dashboard tidak memakai link itu sebagai CTA aktif
- dashboard menampilkan fallback ke link aktif usable pertama jika ada
- tampilkan catatan kecil seperti `Link unggah utama sedang tidak tersedia`

Alasan:

- keputusan link utama tetap berada pada admin
- sistem tidak diam-diam memindahkan status utama ke link lain

## Perilaku Halaman Admin Link Upload

Di halaman kelola link upload tenant, tambahkan elemen berikut:

- badge `Utama` pada link yang ditetapkan sebagai utama
- aksi `Jadikan Utama` pada link lain
- jika link sudah utama, aksi bisa berubah menjadi label pasif atau tombol nonaktif

Saat admin menekan `Jadikan Utama`:

1. sistem validasi bahwa link masih usable
2. sistem set semua link tenant lain menjadi `is_primary = false`
3. sistem set link terpilih menjadi `is_primary = true`
4. sistem tampilkan notifikasi sukses

## Rekomendasi Query dan Logika Backend

### Di Dashboard User

Controller dashboard sebaiknya mengambil:

- semua link aktif usable seperti saat ini
- satu `primaryUploadLink` terpisah
- satu `preferredUploadLink`

Definisi yang direkomendasikan:

- `primaryUploadLink`: link tenant dengan `is_primary = true`
- `preferredUploadLink`: link yang dipakai CTA dashboard

Logika `preferredUploadLink`:

1. jika `primaryUploadLink` ada dan usable, pakai itu
2. jika tidak, pakai link aktif usable pertama
3. jika tidak ada sama sekali, bernilai `null`

Dengan pola ini:

- admin tetap punya konsep resmi `utama`
- UI tetap tangguh saat link utama bermasalah

## Rekomendasi Validasi

Validasi saat set link utama:

- link harus milik tenant aktif
- link harus `is_active = true`
- `expires_at` belum lewat
- `usage_count` belum mencapai `max_usage`

Validasi saat render tombol dashboard:

- jangan tampilkan tombol aktif jika `preferredUploadLink` bernilai `null`

## Rekomendasi UX Dashboard

Urutan yang disarankan:

1. hero dashboard
2. CTA `Upload Berkas Sekarang`
3. kartu statistik
4. tabel `Daftar Link Upload Aktif`

Alasan:

- user uploader biasanya datang untuk aksi unggah
- CTA utama seharusnya terlihat sebelum tabel dan statistik

Jika ingin lebih jelas, tampilkan informasi kecil di bawah tombol:

- judul link
- kode link
- status seperti `Tanpa batas waktu` atau `Sisa kuota tersedia`

## Rekomendasi Naming

Nama yang paling mudah dipahami pengguna:

- `Link Unggah Utama`

Alternatif:

- `Link Unggah Default`
- `Link Unggah Resmi`

Rekomendasi terbaik tetap `Link Unggah Utama` karena lebih natural di UI admin maupun dashboard user.

## Hal yang Sebaiknya Dihindari

- jangan langsung redirect otomatis user ke halaman upload saat membuka dashboard
- jangan otomatis memindahkan status utama ke link lain tanpa aksi admin
- jangan menyembunyikan daftar link lain sepenuhnya
- jangan mengizinkan lebih dari satu link utama per tenant

## Dampak ke Struktur Saat Ini

Fitur ini cocok dengan struktur aplikasi sekarang karena:

- model `UploadLink` sudah memiliki konsep `usable`
- dashboard user sudah memiliki daftar link aktif
- route upload guest per kode sudah tersedia
- halaman admin link upload sudah menjadi tempat logis untuk mengelola status utama

## Tahap Implementasi yang Direkomendasikan

### Tahap 1

- tambah migration `is_primary` pada `upload_links`
- update model `UploadLink`
- tambah aksi admin `Jadikan Utama`

### Tahap 2

- update dashboard user agar memiliki `primaryUploadLink`
- tampilkan CTA utama unggah
- tetap pertahankan tabel semua link aktif

### Tahap 3

- tambahkan validasi dan pesan error yang jelas
- tambahkan badge `Utama` di halaman admin

## Kesimpulan

Pendekatan terbaik adalah menambahkan satu status `is_primary` pada `upload_links`, lalu memakai status tersebut untuk menampilkan tombol unggah utama di dashboard user uploader.

Dengan desain ini:

- admin tetap mengontrol link resmi
- user uploader mendapat jalur unggah tercepat
- sistem tetap aman jika link utama sedang tidak usable
- implementasi tetap sederhana dan sesuai arsitektur aplikasi saat ini
