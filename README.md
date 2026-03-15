<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

## The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Galeri Digital Batik FE

## Penjelasan Sistem

Galeri Digital Batik FE adalah aplikasi web berbasis Laravel yang digunakan untuk mengelola galeri digital batik. Sistem ini menyediakan fitur untuk menampilkan, menambah, mengedit, dan menghapus koleksi batik secara online. Pengguna dapat mengakses galeri, melihat detail batik, serta melakukan pencarian berdasarkan kategori atau motif.

### Fitur Utama

- Manajemen koleksi batik (CRUD)
- Pencarian dan filter batik
- Tampilan galeri yang responsif
- Autentikasi pengguna
- Dashboard admin

## Instalasi

Ikuti langkah-langkah berikut untuk menginstal dan menjalankan aplikasi ini:

### 1. Clone Repository

```bash
git clone https://github.com/username/galeri-digital-batik-fe.git
cd galeri-digital-batik-fe
```

### 2. Install Dependency

- **Composer** (PHP):
    ```bash
    composer install
    ```
- **NPM** (Frontend):
    ```bash
    npm install
    ```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Lalu sesuaikan konfigurasi database dan lainnya di file `.env`.

### 4. Generate Key

```bash
php artisan key:generate
```

### 5. Migrasi dan Seeder Database

```bash
php artisan migrate --seed
```

### 6. Build Asset Frontend

```bash
npm run build
```

### 7. Menjalankan Server

```bash
php artisan serve
```

Server akan berjalan di `http://localhost:8000`.

## Penggunaan

1. Buka browser dan akses `http://localhost:8000`.
2. Login sebagai admin untuk mengelola galeri batik.
3. Pengguna dapat melihat koleksi batik, melakukan pencarian, dan melihat detail batik.

## Kontribusi

Silakan buat pull request atau issue jika ingin berkontribusi atau menemukan bug.

---
