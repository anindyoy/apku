# Rencana Fitur Pemeriksaan Keseimbangan Saldo

## Tujuan

Menambahkan pemeriksaan otomatis yang memberi tahu pengguna apakah total saldo kas dan total saldo sumber dana miliknya seimbang. Pemeriksaan bersifat informatif dan hanya membaca saldo; fitur ini tidak membuat transaksi atau mengubah saldo secara otomatis.

## Pengaktifan fitur

- Sediakan pengaturan per pengguna pada halaman **Akun Saya**, di bagian **Pengaturan fitur**, dengan pola yang sama seperti pengaturan Tabungan Emas.
- Nama toggle yang disarankan: **Pemeriksaan keseimbangan saldo**. Jelaskan bahwa fitur memeriksa total saldo kas dan sumber dana serta menampilkan indikator di Dashboard.
- Status awal nonaktif. Pengguna dapat mengaktifkan atau menonaktifkannya kapan saja.
- Simpan preferensi pada kolom boolean milik `users` (misalnya `pemeriksaan_saldo_aktif`, default `false`), bukan pengaturan global bersama. Tambahkan migrasi dan perbarui bagian Skema database pada `FITUR_APLIKASI.md` saat implementasi.
- Ketika nonaktif, jangan jalankan pemeriksaan untuk Dashboard dan jangan tampilkan status atau pesan terkait.

## Aturan penghitungan

- Hitung hanya entitas milik pengguna yang sedang login. Kas bersama yang hanya dapat dilihat atau dikelola sebagai kolaborator tidak masuk ke total pribadi pengguna.
- Total kas mengikuti pengaturan **Hitung saldo tabungan ke total saldo kas** yang sudah ada. Jika saldo Kas Tabungan dikecualikan, gunakan cakupan kas biasa yang sama dengan angka total saldo di Dashboard.
- Total sumber dana dihitung satu kali per sumber dana milik pengguna; jangan menjumlahkan saldo sumber dana sekali untuk setiap transaksi atau setiap kas yang terkait. Satu sumber dana dapat digunakan pada transaksi beberapa kas.
- Bandingkan angka saldo tersimpan yang ditampilkan di Dashboard, bukan jumlah nominal transaksi yang mungkin memuat riwayat import tanpa dampak saldo.
- Status **Seimbang** jika selisih total kas dan total sumber dana sama dengan nol; jika tidak, status **Ada selisih** dan tampilkan nilai serta arah selisih secara jelas.
- Penghitungan harus memakai sumber data dan cakupan yang konsisten dengan saldo di Dashboard, termasuk pengaturan tabungan, transaksi transfer, serta data yang telah dikunci atau dihapus sesuai aturan saldo aplikasi.
- Hindari memberi kesan bahwa kecocokan total membuktikan setiap saldo benar. Total yang sama dapat menutupi selisih antar-akun; beri petunjuk bahwa pengguna dapat memeriksa tiap sumber dana melalui **Cocokkan Saldo**.

## Tampilan Dashboard

- Tampilkan indikator ringkas ketika pemeriksaan aktif pada tab **Kas & Sumber Dana**, mencakup total saldo kas, total saldo sumber dana, selisih, dan status.
- Jika total sama, tampilkan status **Seimbang**. Jika berbeda, tampilkan status **Ada selisih** beserta jumlah selisih.
- Pada bagian/tab **Kas** dan **Sumber Dana**, beri penanda yang mudah ditemukan bahwa total belum cocok. Sediakan tautan menuju halaman Cocokkan Saldo untuk investigasi.
- Pastikan tampilan responsif dan terbaca pada tema terang/gelap serta layar sempit. Jangan mengandalkan warna saja untuk menyampaikan status.
- Bila salah satu sisi belum memiliki data, tampilkan totalnya sebagai nol dan tetap jelaskan perbandingannya; jangan menyembunyikan ketidakcocokan.
- Hormati pengaturan personalisasi Dashboard: status pemeriksaan muncul pada area gabungan **Kas & Sumber Dana** jika kedua ringkasan tersedia. Perilaku jika pengguna menyembunyikan salah satu panel perlu ditetapkan konsisten, misalnya tetap menampilkan ringkasan status di area gabungan selama fitur aktif.

## Alur saat terdapat selisih

1. Pengguna mengaktifkan pemeriksaan di **Akun Saya**.
2. Dashboard menghitung dan menampilkan hasil saat dibuka atau diperbarui.
3. Jika ada selisih, pengguna dapat membuka daftar kas/sumber dana untuk meninjau rincian.
4. Pengguna dapat membuka **Cocokkan Saldo** untuk membandingkan saldo sumber dana dengan uang riil.
5. Penyesuaian saldo hanya dilakukan melalui alur Cocokkan Saldo yang sudah ada dan atas tindakan pengguna.

## Data, cache, dan kinerja

- Gunakan satu layanan/domain helper untuk menyusun total kas, total sumber dana, selisih, dan status agar perhitungan tidak terduplikasi di Blade.
- Muat angka secara efisien untuk pengguna aktif; hindari query per kas atau per sumber dana.
- Jika memakai cache Dashboard yang sudah ada, masukkan preferensi dan semua tabel sumber saldo ke invalidasi cache. Pastikan perubahan toggle langsung tercermin.
- Status bersifat hasil perhitungan terkini, bukan catatan rekonsiliasi permanen. Tidak perlu tabel riwayat baru kecuali kebutuhan produk berubah.

## Hak akses dan kasus khusus

- Fitur ini untuk dashboard pengguna non-admin dan hanya mencakup kas serta sumber dana milik akun tersebut.
- Jangan memasukkan saldo kas kolaborasi, sumber dana anggota lain, atau data pengguna lain.
- Pengaturan tabungan yang dikecualikan harus memengaruhi total kas yang dibandingkan. Tampilkan cakupan yang digunakan agar pengguna memahami hasilnya.
- Kas dengan saldo negatif tetap ikut dihitung sesuai saldo tersimpan.
- Riwayat transaksi yang diimpor dengan opsi tanpa dampak saldo tidak boleh mengubah hasil.
- Untuk pengguna yang baru selesai onboarding atau belum memiliki salah satu jenis entitas, total kosong dianggap nol.

## Perubahan dokumentasi pengguna

- Perbarui `resources/content/tutorial.json`: jelaskan letak toggle pada Akun Saya, cara membaca status Dashboard, aturan cakupan Kas Tabungan, serta langkah menuju Cocokkan Saldo jika ada selisih.
- Perbarui bagian terkait di `FITUR_APLIKASI.md`, termasuk pengaturan akun, Dashboard, dan skema database.
- Jangan menambahkan petunjuk khusus admin ke tutorial publik.

## Pengujian implementasi

- Pengaturan pengguna: nilai default nonaktif, penyimpanan aktif/nonaktif, dan isolasi preferensi antar pengguna.
- Perhitungan: nilai seimbang, nilai berbeda positif/negatif, kas atau sumber dana kosong, saldo negatif, dan saldo sumber dana yang terhubung ke beberapa kas hanya dihitung sekali.
- Cakupan: Kas Tabungan masuk/keluar sesuai preferensi, kas bersama tidak dihitung, serta transaksi import tanpa dampak saldo tidak mengubah total.
- Dashboard: indikator hanya muncul saat fitur aktif; status dan angka sesuai hasil perhitungan; tampilan tetap benar pada panel Dashboard yang dipersonalisasi.
- Cache: perubahan saldo dan preferensi menginvalidasi hasil yang ditampilkan.
- Jalankan test terfilter yang relevan, termasuk test tutorial sesuai aturan proyek.

## Urutan pengerjaan yang disarankan

1. Tegaskan definisi saldo dan cakupan kas yang dibandingkan dengan saldo sumber dana.
2. Tambahkan preferensi pengguna dan migrasi skema.
3. Implementasikan layanan perhitungan serta strategi cache/invalidation.
4. Tambahkan toggle di Akun Saya.
5. Tampilkan indikator pada Dashboard, khususnya tab Kas dan Sumber Dana.
6. Perbarui tutorial dan `FITUR_APLIKASI.md`.
7. Tambahkan dan jalankan test terfilter untuk pengaturan, perhitungan, dashboard, cache, dan tutorial.

## Keputusan cakupan yang diterapkan

Pemeriksaan membandingkan total sumber dana dengan total kas yang dipakai Dashboard. Jika pengguna memisahkan Kas Tabungan dari total kas, pemeriksaan memakai kas biasa dan menyebutkan cakupan tersebut. Dengan demikian hasil pemeriksaan konsisten dengan angka total kas yang sedang dilihat pengguna.

