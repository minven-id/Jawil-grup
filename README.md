# Jawil Grup - Sistem Monitoring Barang

Aplikasi berbasis web untuk monitoring dan manajemen inventaris barang/aset dengan sistem login roles dan pengaturan akses yang lengkap.

## Fitur Utama

- **Sistem Login & Autentikasi**
  - Login aman dengan password hashing
  - Session management dengan timeout
  - Proteksi halaman berdasarkan login

- **Role-Based Access Control (RBAC)**
  - 4 level role: Super Admin, Admin, Manager, Staff
  - Permission-based access control
  - Pengaturan hak akses yang fleksibel

- **Manajemen Barang/Inventaris**
  - CRUD (Create, Read, Update, Delete) barang
  - Upload gambar barang
  - Kategorisasi barang
  - Tracking status barang (Tersedia, Digunakan, Maintenance, Rusak, Hilang)
  - Pencarian dan filter barang
  - Pagination untuk data yang banyak

- **Manajemen Kategori**
  - CRUD kategori barang
  - Melihat jumlah barang per kategori

- **Manajemen Pengguna**
  - CRUD pengguna
  - Assignment role pengguna
  - Aktivasi/deaktivasi pengguna

- **Log Aktivitas**
  - Audit trail lengkap
  - Tracking semua aktivitas pengguna
  - Filter berdasarkan aksi, tabel, tanggal
  - Melihat detail perubahan data

- **Dashboard**
  - Statistik overview
  - Barang dengan stok rendah
  - Aktivitas terbaru
  - Status barang overview

## Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: TailwindCSS (via CDN)
- **Icons**: Font Awesome 6

## Persyaratan Sistem

- XAMPP/WAMP atau server PHP lainnya
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.2+
- Web browser modern (Chrome, Firefox, Edge, Safari)

## Instalasi

### 1. Setup Database

Buka phpMyAdmin (http://localhost/phpmyadmin) dan:

1. Buat database baru bernama `jawil_grup`
2. Import file `database.sql` ke database tersebut
   - Buka database `jawil_grup`
   - Klik tab "Import"
   - Pilih file `database.sql`
   - Klik "Go"

### 2. Konfigurasi

File konfigurasi sudah diset dengan default:
- Database Host: localhost
- Database User: root
- Database Password: (kosong)
- Database Name: jawil_grup

Jika perlu mengubah konfigurasi, edit file `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jawil_grup');
```

### 3. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/jawil_grup
```

## Login Default

Setelah instalasi, gunakan akun default untuk login:

- **Kode Pegawai**: ADM-001
- **Password**: admin123

⚠️ **PENTING**: Ganti password admin default setelah login pertama untuk keamanan.

## Role & Permissions

### Super Admin
- `users.manage` - Kelola pengguna
- `items.manage` - Kelola barang (CRUD)
- `categories.manage` - Kelola kategori
- `logs.view` - Lihat log aktivitas
- `settings.manage` - Pengaturan sistem

### Admin
- `items.manage` - Kelola barang (CRUD)
- `categories.manage` - Kelola kategori
- `logs.view` - Lihat log aktivitas

### Manager
- `items.view` - Lihat barang
- `items.edit` - Edit barang
- `categories.view` - Lihat kategori

### Staff
- `items.view` - Lihat barang saja

## Struktur Direktori

```
jawil_grup/
├── config.php              # Konfigurasi aplikasi
├── database.sql            # SQL schema database
├── login.php               # Halaman login
├── logout.php              # Logout handler
├── dashboard.php           # Dashboard utama
├── items.php               # Manajemen barang
├── categories.php          # Manajemen kategori
├── users.php               # Manajemen pengguna
├── logs.php                # Log aktivitas
├── includes/
│   ├── db.php             # Database connection class
│   ├── auth.php           # Authentication & authorization
│   ├── header.php         # Header & sidebar
│   └── footer.php         # Footer & scripts
├── uploads/                # Directory upload gambar
└── README.md              # Dokumentasi ini
```

## Penggunaan

### Menambah Barang Baru

1. Login sebagai Admin atau Super Admin
2. Buka menu "Barang"
3. Klik tombol "Tambah Barang"
4. Isi form:
   - Kode Barang (unik)
   - Nama Barang
   - Deskripsi
   - Kategori
   - Jumlah stok
   - Satuan
   - Lokasi penyimpanan
   - Status
   - Tanggal pembelian (opsional)
   - Harga pembelian (opsional)
   - Supplier (opsional)
   - Upload gambar (opsional)
5. Klik "Simpan"

### Mengedit Barang

1. Buka menu "Barang"
2. Klik icon edit (pencil) pada barang yang ingin diubah
3. Edit data yang diperlukan
4. Klik "Update"

### Menghapus Barang

1. Buka menu "Barang"
2. Klik icon hapus (trash) pada barang yang ingin dihapus
3. Konfirmasi penghapusan

### Menambah Pengguna Baru

1. Login sebagai Super Admin
2. Buka menu "Pengguna"
3. Klik "Tambah Pengguna"
4. Isi data pengguna dan pilih role
5. Klik "Simpan"

### Melihat Log Aktivitas

1. Login sebagai user dengan permission `logs.view`
2. Buka menu "Aktivitas"
3. Gunakan filter untuk mencari aktivitas spesifik
4. Klik icon mata untuk melihat detail

## Keamanan

- Password di-hash menggunakan bcrypt (PASSWORD_DEFAULT)
- SQL injection prevention menggunakan PDO prepared statements
- XSS prevention dengan htmlspecialchars
- Session timeout (default 1 jam)
- CSRF protection (perlu ditambahkan untuk production)
- File upload validation

## Troubleshooting

### Error: Database connection failed
- Pastikan MySQL/MariaDB service berjalan di XAMPP
- Cek konfigurasi database di `config.php`
- Pastikan database `jawil_grup` sudah dibuat

### Error: Session not working
- Pastikan session.save_path di php.ini valid
- Cek permission directory session

### Gambar tidak bisa diupload
- Pastikan directory `uploads/` memiliki permission write
- Cek MAX_FILE_SIZE di config.php
- Pastikan ekstensi file sesuai (jpg, jpeg, png, gif)

### Login gagal
- Pastikan username dan password benar
- Cek apakah user statusnya aktif di database
- Reset password admin jika lupa:
  ```sql
  UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
  ```

## Customization

### Mengubah Password Admin

Login sebagai admin, lalu:
1. Buka menu "Pengguna"
2. Edit user admin
3. Masukkan password baru
4. Simpan

### Menambah Role Baru

Tambahkan role di database:
```sql
INSERT INTO roles (role_name, description, permissions) VALUES
('Role Baru', 'Deskripsi role', '["permission1","permission2"]');
```

### Mengubah Warna Tema

Edit file CSS di header.php atau gunakan custom CSS.

## Backup & Restore

### Backup Database
```bash
mysqldump -u root -p jawil_grup > backup.sql
```

### Restore Database
```bash
mysql -u root -p jawil_grup < backup.sql
```

## Support & Contribution

Untuk issue atau pertanyaan, silakan hubungi tim development.

## License

Aplikasi ini dibuat untuk internal use Jawil Grup.

## Changelog

### Version 1.0.0 (2026)
- Initial release
- Core features: auth, RBAC, item management, categories, users, logs
- Dashboard with statistics
- Responsive design with TailwindCSS
