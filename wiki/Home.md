# Welcome to the Advanced Log Manager Wiki!

Advanced Log Manager adalah plugin WordPress komprehensif yang dirancang untuk memberikan kemampuan logging, debugging, dan monitoring yang powerful untuk developer dan administrator situs. Wiki ini berfungsi sebagai sumber daya utama Anda untuk dokumentasi detail tentang semua fitur, memastikan Anda dapat memanfaatkan plugin ini secara maksimal.

## Apa itu Advanced Log Manager?

Dalam environment WordPress yang dinamis, memahami apa yang terjadi di balik layar sangat penting untuk menjaga website yang sehat, performant, dan aman. Advanced Log Manager menyederhanakan ini dengan menyentralisasi berbagai tools diagnostic ke dalam satu interface yang intuitif. Dari melacak error PHP hingga memonitor query database dan mengelola konfigurasi server, plugin ini memberdayakan Anda untuk dengan cepat mengidentifikasi dan menyelesaikan masalah, mengoptimalkan performa, dan meningkatkan stabilitas situs secara keseluruhan.

## 🚀 Quick Start untuk Pemula (5 Menit Setup)

Jika Anda baru menggunakan Advanced Log Manager, ikuti langkah-langkah sederhana ini untuk memulai:

### 1. **Aktivasi Plugin**
   - Masuk ke WordPress Admin → **Plugins** → **Installed Plugins**
   - Cari "Advanced Log Manager" dan klik **Activate**

### 2. **Akses Dashboard**
   - Pergi ke **Tools** → **Advance Log Manager**
   - Anda akan melihat dashboard utama dengan status sistem

### 3. **Setup Dasar Debugging**
   - Klik tombol **"Enable Debug Mode"** di dashboard
   - Plugin akan mengaktifkan logging otomatis
   - Debug logs akan tersimpan di `wp-content/debug.log`

### 4. **Test Performance Monitoring**
   - Di tab **Performance Monitor**, aktifkan **"Enable Performance Bar"**
   - Kunjungi halaman frontend situs Anda (sebagai admin)
   - Anda akan melihat bar performa di bagian bawah halaman

### 5. **Cek Logs Pertama**
   - Kembali ke dashboard → Klik card **"Debug Log"**
   - Jika ada error, akan muncul di log viewer
   - Gunakan filter untuk mencari masalah spesifik

**Tips untuk Pemula:**
- ✅ Selalu backup situs sebelum mengubah konfigurasi
- ✅ Mulai dengan debug mode aktif di development environment
- ✅ Gunakan performance bar untuk memonitor loading time
- ❌ Jangan aktifkan debug mode di production tanpa monitoring

## 📋 Fitur Utama Overview

Berikut adalah overview singkat dari fungsionalitas inti yang ditawarkan Advanced Log Manager. Setiap fitur dirancang untuk memberikan kontrol granular dan insights jelas ke berbagai aspek instalasi WordPress Anda.

### 1. 🔍 Log Manager (Debug Logs)

**Fungsi:** Hub sentral untuk melihat, memfilter, dan mengelola semua debug logs WordPress. Mengkonsolidasikan berbagai tipe log (PHP errors, warnings, notices) ke dalam format yang readable.

**Manfaat untuk Pemula:**
*   **Debugging Cepat:** Identifikasi error PHP, warning, dan notice yang mungkin mempengaruhi fungsionalitas situs
*   **View Terpusat:** Akses semua log relevan dari satu interface user-friendly di dashboard WordPress
*   **Filter & Search:** Navigasi melalui file log besar menggunakan filtering dan search capabilities
*   **Pembersihan Log:** Clear log lama untuk menghemat space server

### 2. 🗄️ Query Monitor

**Fungsi:** Memberikan insights mendalam tentang interaksi database WordPress. Melacak setiap query yang dijalankan di situs Anda.

**Manfaat untuk Pemula:**
*   **Optimasi Performa:** Identifikasi dan optimalkan slow database queries
*   **Analisis Resource:** Pahami plugin/theme mana yang generate database calls berlebihan
*   **Debug Database Issues:** Pinpoint error atau behavior unexpected terkait database
*   **Detailed Analysis:** View execution time, caller functions, dan affected rows

### 3. 📧 SMTP Logs

**Fungsi:** Mencatat semua email keluar dari situs WordPress Anda, memberikan history komprehensif tentang delivery attempts, status, dan errors.

**Manfaat untuk Pemula:**
*   **Email Deliverability:** Verifikasi apakah email terkirim dengan sukses
*   **Troubleshooting:** Diagnose masalah email seperti masuk spam atau gagal kirim
*   **Audit Trail:** Maintain record semua email keluar untuk compliance
*   **Info Detail:** View sender, recipient, subject, dan status setiap email

### 4. ⚙️ .htaccess Editor

**Fungsi:** Cara aman dan convenient untuk memodifikasi file `.htaccess` situs langsung dari dashboard WordPress.

**Manfaat untuk Pemula:**
*   **Safe Editing:** Edit file `.htaccess` tanpa perlu FTP access
*   **Auto Backup:** Backup otomatis sebelum perubahan
*   **Security & Performance:** Implement rules keamanan dan optimasi performa
*   **Error Prevention:** Built-in safeguards mencegah common errors

### 5. 🔧 PHP Config Presets

**Fungsi:** Terapkan preset konfigurasi PHP yang umum untuk environment berbeda (debugging, development, production).

**Manfaat untuk Pemula:**
*   **Switching Mudah:** Ganti konfigurasi PHP dengan beberapa klik
*   **Debugging Streamlined:** Enable error reporting verbose untuk debugging
*   **Performance Optimized:** Apply settings untuk production sites
*   **Environment Management:** Switch antara development dan production

### 6. 📊 Performance Monitoring

**Fungsi:** Berikan real-time insights tentang performance metrics situs, sering ditampilkan di admin bar WordPress.

**Manfaat untuk Pemula:**
*   **Real-time Insights:** Monitor metrics kritis langsung dari admin bar
*   **Issue Detection:** Spot performance degradation atau resource usage spikes
*   **Bottleneck Identification:** Pahami area mana yang mempengaruhi site speed
*   **UX Improvement:** Ensure website yang fast dan responsive

## 🆚 Perbandingan Fitur

| Fitur | Free Version | Pro Version |
|-------|-------------|-------------|
| Debug Log Viewer | ✅ | ✅ |
| Query Monitor | ✅ | ✅ |
| SMTP Logs | ✅ | ✅ |
| .htaccess Editor | ✅ | ✅ |
| PHP Config Presets | ✅ | ✅ |
| Performance Bar | ✅ | ✅ |
| Advanced Filtering | ✅ | ✅ |
| Export Logs | ✅ | ✅ |
| Priority Support | ❌ | ✅ |
| Custom Presets | ❌ | ✅ |

## ❓ FAQ untuk Pemula

**Q: Apakah plugin ini aman untuk production site?**
A: Ya, plugin ini memiliki multiple safety features seperti auto-backup dan validation. Namun, selalu test di staging environment dulu.

**Q: Bagaimana cara membaca debug logs?**
A: Logs berisi timestamp, error level, dan pesan error. Gunakan filter untuk fokus pada error tertentu.

**Q: Performance bar memperlambat situs saya?**
A: Hanya terlihat untuk admin users dan impact minimal. Disable jika diperlukan.

**Q: Bagaimana jika saya bingung dengan konfigurasi?**
A: Mulai dengan preset "Basic" dan aktifkan debug mode. Baca troubleshooting guide untuk masalah umum.

Advanced Log Manager adalah solusi all-in-one untuk WordPress site yang robust dan well-maintained. Explore bagian wiki lainnya untuk guides lebih mendalam dan tutorials tentang setiap fitur.
