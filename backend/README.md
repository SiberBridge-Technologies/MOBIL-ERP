# ERP-APP — Backend (Yönetim Paneli + REST API)

## Kurulum (cPanel)

1. `database/schema.sql` dosyasını phpMyAdmin üzerinden içe aktarın.
2. `config/database.php` içindeki `DB_USER` / `DB_PASS` değerlerini kendi hosting bilgilerinizle değiştirin.
3. İlk admin kullanıcıyı oluşturmak için PHP ile bir şifre hash üretin:
   ```php
   <?php echo password_hash('admin123', PASSWORD_DEFAULT);
   ```
   Ardından `employees` tablosuna elle ekleyin (rol: `ADMIN`).
4. Tüm `backend/` klasörünü hosting'e (örn. `public_html/erp`) yükleyin.
5. HTTPS zorunlu olmalı — cPanel üzerinden ücretsiz Let's Encrypt sertifikası aktif edin.

## Yönetim Paneli (admin/)

Tarayıcı üzerinden `admin/login.php` adresine gidilerek giriş yapılır (session tabanlı,
API'nin token sisteminden ayrıdır). Sayfalar:

| Sayfa | Açıklama | Yetki |
|---|---|---|
| login.php / logout.php | Giriş / çıkış | Açık |
| index.php | Genel Bakış (istatistikler, son siparişler, düşük stok) | ADMIN/YONETICI |
| products.php, product-add.php, product-edit.php | Ürün yönetimi | ADMIN/YONETICI |
| customers.php, customer-add.php, customer-detail.php | Cari yönetimi + çalışan atama | ADMIN/YONETICI |
| orders.php, order-detail.php | Sipariş listesi + durum güncelleme (ONAYLANDI'da stok düşer) | ADMIN/YONETICI |
| employees.php, employee-add.php, employee-detail.php | Çalışan yönetimi, şifre resetleme, cari atama | Sadece ADMIN |
| settings.php | Kendi şifresini değiştirme | Herkes |

`config/.htaccess` ve `includes/.htaccess` bu klasörlere doğrudan tarayıcı erişimini engeller.

## API Uç Noktaları

| Metod | Endpoint | Açıklama | Yetki |
|---|---|---|---|
| POST | /api/auth/login.php | Giriş, token döner | Açık |
| POST | /api/auth/logout.php | Token'ı geçersiz kılar | Token |
| GET | /api/products/list.php?q= | Ürün arama/listeleme | Token |
| GET | /api/products/get.php?id= | Ürün detay | Token |
| POST | /api/products/create.php | Ürün ekleme | Admin/Yönetici |
| POST | /api/products/update.php | Ürün güncelleme | Admin/Yönetici |
| POST | /api/products/delete.php | Ürün pasifleştirme | Admin/Yönetici |
| GET | /api/customers/list.php?q= | Cari arama (yetkiye göre filtrelenir) | Token |
| GET | /api/customers/get.php?id= | Cari detay + son 15 sipariş | Token (yetki kontrollü) |
| POST | /api/customers/create.php | Cari ekleme | Admin/Yönetici |
| POST | /api/orders/create.php | Sipariş oluşturma (fiyat/stok sunucuda doğrulanır) | Token |
| POST | /api/orders/update.php | Durum güncelleme (ONAYLANDI → stok düşer) | Admin/Yönetici |
| GET | /api/employees/list.php | Çalışan listesi | Admin/Yönetici |

## Güvenlik notları

- Tüm sorgular PDO prepared statement kullanır.
- Şifreler `password_hash()` ile saklanır.
- Sipariş oluştururken fiyat/stok **her zaman** MySQL'den tekrar okunur; istemciden gelen fiyat asla güvenilmez.
- Token'lar `auth_tokens` tablosunda tutulur, süresi dolanlar otomatik reddedilir.
