# Rancangan Skor Unduhan Berkas Internal

## Tujuan

Rancangan ini ditujukan untuk menambahkan perhitungan skor dari unduhan berkas `internal` oleh akun login tenant, sehingga aktivitas pemanfaatan arsip internal juga dapat berkontribusi ke leaderboard.

Rancangan ini tidak mengubah prinsip bahwa skor tetap diberikan kepada uploader file, bukan kepada pengunduh file.

## Kondisi Implementasi Saat Ini

Saat ini logika scoring hanya menghitung:

- poin dari upload file yang `valid`
- poin dari unduhan file `public`

Unduhan file `internal` saat ini belum dihitung untuk skor maupun leaderboard.

Secara implementasi saat ini:

- pencatatan skor download hanya dipanggil untuk file `public`
- perhitungan skor uploader hanya menghitung `file_downloads` yang terkait file `public`
- leaderboard juga hanya menghitung unduhan file `public`

## Kebutuhan Baru

Kebutuhan yang diinginkan adalah:

- berkas `internal` yang diakses dan diunduh oleh akun login dapat menambah skor uploader
- unduhan tersebut juga ikut memengaruhi leaderboard tenant

Namun kebutuhan ini harus diberi batasan agar:

- tidak mudah disalahgunakan
- tidak menghitung self-download
- tidak menghitung akses anonim
- tetap konsisten dengan konteks tenant

## Prinsip Rekomendasi

- hanya unduhan file `internal` yang dilakukan oleh akun login tenant yang sama yang boleh dihitung
- self-download tidak dihitung
- unduhan file `private` tetap tidak dihitung pada tahap awal
- file harus berstatus `valid`
- scoring file `internal` boleh dibedakan dari `public`

## Definisi Aturan Baru

### Unduhan Public

Tetap seperti saat ini:

- file `visibility = public`
- file `status = valid`
- dapat diunduh dari katalog publik atau portal user
- jika pengunduh adalah pemilik file sendiri, unduhan tidak dihitung untuk skor

### Unduhan Internal

Aturan yang direkomendasikan:

- file `visibility = internal`
- file `status = valid`
- unduhan dilakukan oleh `user_account` yang login
- akun tersebut berada dalam tenant yang sama
- pengunduh bukan uploader pemilik file
- unduhan dicatat dan dihitung untuk skor uploader

### Unduhan Private

Rekomendasi tahap:

- tetap tidak dihitung untuk skor

Alasannya:

- file `private` lebih dekat ke dokumen personal atau terbatas
- risikonya lebih tinggi untuk manipulasi aktivitas
- nilai kompetitif leaderboard lebih masuk akal bila difokuskan pada dampak file `public` dan `internal`

## Siapa yang Mendapat Poin

Poin tetap diberikan kepada:

- uploader pemilik file

Bukan kepada:

- user yang mengunduh

Jadi jika user A mengunduh file internal milik user B, maka:

- skor bertambah untuk user B sebagai uploader
- leaderboard uploader user B ikut naik

## Aturan Anti Abuse

Minimal aturan berikut direkomendasikan:

- self-download tidak dihitung
- unduhan `internal` hanya dihitung jika pelaku adalah akun login yang aktif
- unduhan dari tenant lain tidak mungkin dihitung
- file soft deleted tidak dihitung
- file `pending_review` atau `suspended` tidak dihitung

Tahap awal lebih sederhana:

- hitung semua unduhan valid non-self
- pantau dulu apakah muncul abuse

## Rekomendasi Bobot Skor

### Bobot Sama

Gunakan 1 poin yang sama untuk:

- `public download`
- `internal download`

Kelebihan:

- implementasi mudah
- aturan sederhana


## Rekomendasi Perubahan Service Scoring

Service scoring saat ini masih berorientasi pada `recordPublicDownload`.

Rekomendasi:

- ubah menjadi metode yang lebih umum, misalnya `recordDownload`
- metode menerima konteks download dan actor yang mengunduh
- scoring membaca file download berdasarkan visibilitas file dan konteks unduhan

### Perhitungan `last_score`

Disarankan rumus menjadi:

- poin upload valid
- ditambah jumlah unduhan public atau pun internal yang dihitung x `download_point`
- ditambah adjustment manual

### Perhitungan Leaderboard Periodik

Disarankan leaderboard menghitung:

- `valid_upload_count`
- `counted_download_count`
- `adjustment_total`

Lalu skor periode:

- `valid_upload_count * upload_valid_point`
- `+ counted_download_count * download_point`
- `+ adjustment_total`

## Rekomendasi Tampilan Leaderboard

Tambahkan informasi seperti:

- jumlah upload valid, termasuk berkas publik dan internal
- jumlah unduhan berkas publik dan internal
- total skor periode

Dengan begitu pengguna memahami sumber skor, bukan hanya melihat angka total.

## Rekomendasi Tampilan Dashboard

Pada dashboard user dan admin, pertimbangkan menambah ringkasan:

- skor dari upload
- skor dari unduhan

Ini membantu menjelaskan kenapa skor bisa naik walaupun file tidak tampil di katalog publik.

## Pertimbangan Kebijakan Bisnis

Sebelum implementasi, perlu diputuskan arah leaderboard:

### Leaderboard dimaksudkan sebagai kompetisi keterbukaan publik

- file `internal` dan `publik` ikut terhitung

## Rekomendasi Tahap Implementasi

### Tahap 1

- tambahkan dukungan scoring untuk unduhan `internal`
- simpan `download_context`
- simpan `downloaded_by_user_account_id`
- tetap kecualikan self-download
- tampilkan unduhan internal di leaderboard

### Tahap 2

- gabung bobot publik dan internal ke `download_point`

### Tahap 3

- tambahkan anti abuse yang lebih ketat

## Rekomendasi Akhir

Ya, unduhan berkas `internal` oleh akun login bisa dibuat ikut menambah skor dan leaderboard.

Pendekatan terbaik adalah:

- hanya hitung unduhan file `internal` yang `valid`
- hanya hitung jika pengunduh adalah akun login tenant yang sama
- jangan hitung self-download
- simpan actor pengunduh dan konteks unduhan
- pisahkan bobot skor `public` dan `internal`

Dengan desain ini, leaderboard tidak hanya mencerminkan seberapa sering file dibuka publik, tetapi juga seberapa bermanfaat arsip internal bagi tenant.
