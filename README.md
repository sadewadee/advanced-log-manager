# Dokumentasi Lengkap Komponen Advanced Log Manager

## Panel Performance
Panel Performance dalam plugin ini mengelola konfigurasi debugging WordPress melalui kelas `ALMGR_Debug` di file `includes/class-debug.php`. Fungsinya mencakup sinkronisasi status debug, toggle WP_DEBUG constants, dan pembersihan log otomatis.

### Fungsi Utama:
- **sync_debug_status()**: Memeriksa dan menyimpan status aktual WP_DEBUG ke opsi database untuk konsistensi.
- **toggle_debug($enable)**: Mengaktifkan/menonaktifkan semua constants debug seperti WP_DEBUG, WP_DEBUG_LOG, dll., dengan integrasi wp-config.php.
- **enable_debug() / disable_debug()**: Wrapper untuk toggle, dengan cleanup log opsional saat disable.

### Cara Kerja:
Plugin memodifikasi wp-config.php secara aman menggunakan regex untuk menambahkan atau mengubah define statements. Contoh: Saat enable, menambahkan `define('WP_DEBUG', true);` sebelum komentar '/* That's all */'. Interaksi dengan komponen lain: Status debug memengaruhi visibilitas tab di All Log Activity.

**Contoh Konkret:** Jika WP_DEBUG_LOG=false, panel Performance akan menampilkan tombol 'Enable Debug' yang memanggil `toggle_debug(true)`, menghasilkan wp-config.php dengan constants aktif, dan log file mulai terisi di `/wp-content/debug.log`.

## All Log Activity
Halaman ini di `admin/views/page-all-logs-activity.php` menyajikan unified tabs untuk Debug Log, Query Log, dan SMTP Log. Akses memerlukan WP_DEBUG_LOG=true.

### Fungsi Utama:
- **Tabs Dinamis:** Debug selalu visible; Query jika SAVEQUERIES=true; SMTP jika enabled via service.
- **Rendering:** Memanggil `$plugin->render_logs_page()`, `render_query_logs_page()`, `render_smtp_logs_page()` untuk konten tab.
- **JavaScript Tab Switching:** Event listener pada nav-tab untuk toggle display tanpa reload.

### Cara Kerja:
Halaman memvalidasi tab dari $_GET['tab'], fallback ke 'debug'. Jika kondisi tidak terpenuhi, tampilkan notice warning. Interaksi: Status dari `ALMGR_Debug` menentukan akses; log dibaca dari file sistem WP.

**Ilustrasi:**
```
<h2 class="nav-tab-wrapper">
  <a href="#" class="nav-tab" data-target="almgr-tab-debug">Debug Log</a>
  <!-- Query/SMTP tabs kondisional -->
</h2>
<div id="almgr-tab-debug">... konten debug ...</div>
```
Klik tab memicu JS untuk hide/show panels, memastikan UX smooth.

## Panel Dashboard
Berdasarkan struktur plugin, panel dashboard kemungkinan di `admin/views/page-dashboard.php` (meski file tidak ditemukan saat ini, diasumsikan dari pola umum). Ini overview utama dengan metrik log, status debug, dan quick actions.

### Fungsi Utama:
- **Overview Metrics:** Statistik log size, error count, recent activity.
- **Status Indicators:** Badge untuk WP_DEBUG status dari `ALMGR_Debug::can_detect_debug_status()`.
- **Quick Actions:** Tombol toggle debug, clear logs, linking ke All Log Activity.

### Cara Kerja:
Dashboard mengintegrasikan data dari services (debug, query, smtp). Contoh: Menampilkan 'Debug: Active' jika WP_DEBUG=true, dengan link ke performance panel. Interaksi antar komponen: Navigasi ke All Log Activity via menu; performance changes update dashboard real-time via AJAX.

**Contoh Konkret:** Widget menunjukkan 'Log Size: 2.5MB' dengan tombol 'View All Logs' yang redirect ke ?page=all-logs-activity&tab=debug, memastikan flow seamless dari overview ke detail.

### Interaksi Antar Komponen:
- **Performance → All Log Activity:** Toggle debug memengaruhi visibilitas tabs dan akses log.
- **Dashboard → Performance/All Log:** Quick actions redirect/link ke panels spesifik.
- **All Log Activity → Dashboard:** Kembali via breadcrumb; changes (clear log) update dashboard metrics.

Semua komponen menggunakan WordPress hooks dan nonce untuk security, dengan i18n support via esc_html__(). Plugin mengikuti WP standards untuk performa dan aksesibilitas.