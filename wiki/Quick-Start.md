# 🚀 Quick Start Guide - Advanced Log Manager

Panduan 5 menit untuk memulai menggunakan Advanced Log Manager. Cocok untuk pemula yang baru pertama kali menggunakan plugin debugging WordPress.

## 📋 Checklist Setup Awal

- [ ] Plugin sudah terinstall dan aktif
- [ ] Dashboard plugin dapat diakses
- [ ] Debug mode berhasil diaktifkan
- [ ] Performance bar terlihat di frontend
- [ ] Logs berhasil direkam

## ⚡ Langkah demi Langkah Setup

### Langkah 1: Aktivasi Plugin
1. Login ke WordPress Admin Dashboard
2. Pergi ke **Plugins** → **Installed Plugins**
3. Cari "Advanced Log Manager"
4. Klik **Activate** jika belum aktif

### Langkah 2: Akses Dashboard Utama
1. Di menu sebelah kiri, klik **Tools** → **Advance Log Manager**
2. Anda akan melihat dashboard dengan status sistem:
   - Debug Mode status
   - Performance Monitor status
   - Log file sizes
   - System overview

### Langkah 3: Aktifkan Debug Mode
1. Di dashboard utama, klik tombol besar **"Enable Debug Mode"**
2. Plugin akan otomatis:
   - Mengaktifkan `WP_DEBUG` di `wp-config.php`
   - Mengaktifkan `WP_DEBUG_LOG`
   - Memulai pencatatan error ke `debug.log`
3. Status akan berubah menjadi "Active"

### Langkah 4: Test Performance Monitoring
1. Klik tab **"Performance Monitor"**
2. Aktifkan toggle **"Enable Performance Bar"**
3. Buka tab baru browser dan kunjungi halaman frontend situs Anda
4. Login sebagai administrator
5. Anda akan melihat bar hitam di bagian bawah halaman dengan metrics:
   - Load time
   - Number of queries
   - Memory usage

### Langkah 5: Generate dan Lihat Logs Pertama
1. Lakukan beberapa aksi di situs untuk generate logs:
   - Kunjungi halaman yang tidak ada (404 error)
   - Coba login dengan password salah
   - Akses halaman admin
2. Kembali ke dashboard Advanced Log Manager
3. Klik card **"Debug Log"** atau **"Query Log"**
4. Anda akan melihat entries log terbaru

## 🔍 Memahami Interface Dashboard

### System Overview Cards
- **Debug Mode**: Status aktif/tidak debug logging
- **Performance Monitor**: Status monitoring performa
- **Debug Log**: Ukuran file log dan status
- **Query Log**: Status query logging
- **SMTP Logs**: Status email logging

### Feature Cards
- **Debug Management**: Kontrol settings debug
- **Performance Monitor**: Konfigurasi monitoring
- **.htaccess Editor**: Edit file server config
- **PHP Config**: Preset konfigurasi PHP

## 🛠️ Troubleshooting Setup

### Jika Debug Mode Tidak Aktif
- Pastikan `wp-config.php` writable
- Cek permission file system
- Coba manual edit `wp-config.php`

### Jika Performance Bar Tidak Muncul
- Pastikan login sebagai administrator
- Clear browser cache
- Cek console browser untuk error JavaScript

### Jika Logs Tidak Terekam
- Verifikasi debug mode aktif
- Cek permission folder `wp-content`
- Pastikan ada aktivitas yang generate logs

## 📖 Tips untuk Pemula

### Mode Development vs Production
- **Development**: Aktifkan semua debug features
- **Staging**: Test dengan debug mode aktif
- **Production**: Minimal logging, monitor performa

### Backup Sebelum Perubahan
- Selalu backup `wp-config.php` sebelum edit
- Backup `.htaccess` sebelum menggunakan editor
- Test perubahan di staging environment dulu

### Monitoring Berkala
- Cek logs setiap hari di development
- Monitor performance metrics weekly
- Review error logs sebelum deploy

## 🎯 Next Steps

Setelah setup dasar selesai:

1. **Pelajari Filtering Logs**: Gunakan filter untuk menemukan error spesifik
2. **Konfigurasi .htaccess**: Tambahkan security headers dan caching rules
3. **Setup PHP Presets**: Pilih preset sesuai environment
4. **Monitor Performance**: Gunakan insights untuk optimasi
5. **Baca Dokumentasi Lengkap**: Explore wiki untuk fitur advanced

## 🆘 Butuh Bantuan?

Jika mengalami masalah:
1. Cek **Troubleshooting Guide** di wiki
2. Pastikan versi plugin terbaru
3. Cek forum support atau documentation
4. Contact developer jika issue persist

---

**Selamat!** Anda sudah berhasil setup Advanced Log Manager. Plugin ini akan membantu Anda maintain WordPress site yang lebih sehat dan performant.
