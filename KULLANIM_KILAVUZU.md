# ERP-APP — Detaylı Kullanım Kılavuzu

Bu kılavuz, projeyi hiç görmemiş biri bile takip edip sistemi sıfırdan çalışır hale
getirebilsin diye adım adım yazılmıştır. Sırasıyla okuyup uygulayın.

---

## İÇİNDEKİLER

1. [Sistemin Genel Mantığı](#1-sistemin-genel-mantığı)
2. [Gerekli Araçlar](#2-gerekli-araçlar)
3. [Bölüm A — Backend (Yönetim Paneli + API) Kurulumu](#bölüm-a--backend-kurulumu)
4. [Bölüm B — Mobil Uygulama Kurulumu](#bölüm-b--mobil-uygulama-kurulumu)
5. [Yönetim Panelini Kullanma](#5-yönetim-panelini-kullanma)
6. [Mobil Uygulamayı Kullanma](#6-mobil-uygulamayı-kullanma)
7. [Sipariş Akışı ve Stok Mantığı](#7-sipariş-akışı-ve-stok-mantığı)
8. [Android APK Üretimi (Yayına Alma)](#8-android-apk-üretimi-yayına-alma)
9. [Sık Karşılaşılan Sorunlar](#9-sık-karşılaşılan-sorunlar)
10. [Güvenlik Kontrol Listesi](#10-güvenlik-kontrol-listesi)
11. [Klasör / Dosya Haritası](#11-klasör--dosya-haritası)

---

## 1. Sistemin Genel Mantığı

Sistem **iki bağımsız parçadan** oluşur ve bunlar birbirine **sadece internet
üzerinden (HTTPS + REST API)** bağlanır:

```
┌─────────────────────┐        ┌──────────────┐        ┌─────────────────────┐
│  YÖNETİM PANELİ      │        │              │        │   ÇALIŞAN MOBİL      │
│  (tarayıcıdan        │◄──────►│    MySQL     │◄──────►│   UYGULAMASI          │
│  admin/login.php)    │        │  Veritabanı  │        │  (React Native/Expo) │
│  PHP                 │        │              │        │  Android APK         │
└─────────────────────┘        └──────────────┘        └─────────────────────┘
                                       ▲
                                       │
                              PHP REST API (backend/api/)
                              Mobil uygulama SADECE bu
                              API'ler üzerinden veri okur/yazar
```

**Neden önemli:** Mobil uygulama veritabanına asla doğrudan bağlanmaz. Bu sayede
veritabanı şifresi APK'nın içine hiç gömülmez ve biri APK'yı tersine mühendislikle
açsa bile veritabanına ulaşamaz.

İki parça da **aynı MySQL veritabanını** kullanır — yani yönetim panelinden girilen
bir ürün, saniyeler içinde mobil uygulamada da görünür.

---

## 2. Gerekli Araçlar

Kuruluma başlamadan önce bilgisayarınızda/hesabınızda şunlar olmalı:

| Araç | Ne için | Nereden |
|---|---|---|
| **cPanel'li bir hosting hesabı** (veya yerel XAMPP/MAMP) | Backend'i (PHP+MySQL) çalıştırmak için | Hosting firmanız |
| **phpMyAdmin** (cPanel içinde hazır gelir) | Veritabanı şemasını yüklemek için | cPanel panelinizde |
| **Node.js** (18 veya üzeri) | Mobil uygulamayı geliştirmek için | https://nodejs.org |
| **Expo Go** uygulaması (opsiyonel, test için) | Telefonda anlık test etmek için | Play Store |
| **Bir kod editörü** (VS Code önerilir) | Dosyaları düzenlemek için | https://code.visualstudio.com |

---

## BÖLÜM A — Backend Kurulumu

### A.1 — Dosyaları hostinge yükleme

1. `backend/` klasörünün **tüm içeriğini** cPanel'deki dosya yöneticisi (File Manager)
   veya FTP ile hosting hesabınıza yükleyin.
   - Örnek hedef: `public_html/erp/` (yani siteniz `https://siteniz.com/erp/` olarak açılacak)
   - İsterseniz doğrudan `public_html/` köküne de yükleyebilirsiniz.

2. Yükledikten sonra klasör yapısı hosting'de şöyle görünmeli:
   ```
   public_html/erp/
   ├── admin/
   ├── api/
   ├── assets/
   ├── config/
   ├── database/
   ├── includes/
   ├── setup.php
   ├── index.php
   └── README.md
   ```

### A.2 — Veritabanını oluşturma

1. cPanel'de **"MySQL Veritabanları"** (MySQL Databases) bölümüne girin.
2. Yeni bir veritabanı oluşturun (örn. `kullaniciadi_erp`).
3. Yeni bir MySQL kullanıcısı oluşturun ve bu veritabanına **TÜM YETKİLERLE**
   (All Privileges) bağlayın.
4. Kullanıcı adını, şifreyi ve veritabanı adını bir yere not edin — birazdan
   lazım olacak.

### A.3 — Tabloları içe aktarma

1. cPanel'den **phpMyAdmin**'i açın.
2. Sol menüden az önce oluşturduğunuz veritabanını seçin.
3. Üstteki **"İçe Aktar" (Import)** sekmesine tıklayın.
4. `backend/database/schema.sql` dosyasını seçip **"Git" (Go)** butonuna basın.
5. İşlem bittiğinde sol tarafta şu tabloları görmelisiniz:
   `employees, customers, employee_customers, categories, products, orders,
   order_items, stock_movements, auth_tokens`

> **Not:** `schema.sql` dosyasının en altındaki örnek `INSERT` satırı yorum
> satırı olarak bırakılmıştır — ilk admin kullanıcıyı SQL yazmadan, aşağıdaki
> A.5 adımındaki hazır sihirbazla oluşturacaksınız.

### A.4 — Veritabanı bağlantı bilgilerini girme

1. Dosya yöneticisinden `config/database.php` dosyasını açın (düzenle).
2. Şu satırları kendi bilgilerinizle değiştirin:

   ```php
   define('DB_HOST', 'localhost');              // genelde değişmez
   define('DB_NAME', 'kullaniciadi_erp');        // A.2'de oluşturduğunuz isim
   define('DB_USER', 'kullaniciadi_erpuser');    // A.2'de oluşturduğunuz kullanıcı
   define('DB_PASS', 'BURAYA_GERCEK_SIFRE');     // A.2'de belirlediğiniz şifre
   ```
3. Kaydedin.

### A.5 — İlk admin kullanıcıyı oluşturma (SQL bilmenize gerek yok)

1. Tarayıcıdan şu adrese gidin: `https://siteniz.com/erp/setup.php`
2. Açılan formu doldurun: Ad, Soyad, Kullanıcı Adı, Şifre.
3. **"Admin Hesabını Oluştur"** butonuna basın.
4. Başarılı mesajını gördükten sonra **`setup.php` dosyasını hosting'den silin**
   (dosya yöneticisinden "Sil" deyin). Bu dosya güvenlik amacıyla sadece
   sistemde hiç kullanıcı yokken çalışır, ama yine de silmeniz önerilir.

> Eğer bu sayfayı unutup tekrar açarsanız, sistem zaten bir admin olduğunu
> görüp size otomatik olarak "Kurulum zaten tamamlanmış" mesajı gösterir —
> yani ikinci kez admin oluşturamazsınız, bu normaldir.

### A.6 — HTTPS'i aktif etme

1. cPanel'de **"SSL/TLS Status"** veya **"Let's Encrypt"** bölümüne girin.
2. Domaininiz için ücretsiz SSL sertifikasını aktif edin.
3. Sitenizin `https://` ile açıldığından emin olun (`http://` değil).
   Mobil uygulama sadece HTTPS ile çalışacak şekilde tasarlanmıştır.

### A.6.5 — `.env` dosyasını oluşturma (veritabanı bilgileri artık burada)

Backend, veritabanı şifresi gibi hassas bilgileri artık `config/database.php`
dosyasının İÇİNDE değil, ayrı bir `.env` dosyasında tutar. Bu sayede farklı
ortamlar (yerel bilgisayar, hosting) arasında geçerken kod dosyalarına
dokunmanıza gerek kalmaz, sadece `.env`'i güncellersiniz.

1. `config/.env.example` dosyasını `config/.env` olarak kopyalayın (paket
   içinde zaten hazır bir `.env` dosyası da gelir, XAMPP için önceden
   doldurulmuştur — gerçek hosting'e taşırken değerleri güncelleyin).
2. `config/.env` dosyasını açıp kendi bilgilerinizi girin:

   ```
   DB_HOST=localhost
   DB_NAME=erp_app
   DB_USER=root
   DB_PASS=
   APP_DEBUG=false
   ```

3. `APP_DEBUG=true` yaparsanız PHP hataları ekranda görünür (sadece yerel
   test için kullanın); `APP_DEBUG=false` canlı ortamda olması gereken
   güvenli ayardır.
4. `.env` dosyası zaten `config/` klasöründe olduğu için `.htaccess`
   tarafından otomatik korunur — tarayıcıdan doğrudan açılamaz.

> **Önemli:** `.env` dosyasını asla başkasıyla paylaşmayın veya herkese açık
> bir yere (GitHub gibi) yüklemeyin — içinde veritabanı şifreniz var.

### A.7 — Test etme

Tarayıcıdan `https://siteniz.com/erp/admin/login.php` adresine gidin. Az önce
oluşturduğunuz kullanıcı adı/şifre ile giriş yapabiliyorsanız backend hazırdır. 🎉

---

## BÖLÜM B — Mobil Uygulama Kurulumu

### B.1 — Proje bağımlılıklarını yükleme

1. Bilgisayarınızda bir terminal (Komut İstemi / PowerShell / Terminal) açın.
2. `mobile/` klasörüne girin:
   ```bash
   cd mobile
   ```
3. Bağımlılıkları yükleyin:
   ```bash
   npm install
   ```

### B.2 — API adresini ayarlama (artık `.env` ile)

API adresi artık `app.json` içinde DEĞİL, `mobile/.env` dosyasında tutulur
(backend'deki mantığın aynısı — Expo SDK 49+ `.env` dosyalarını otomatik
olarak destekler, ekstra paket kurmaya gerek yoktur).

1. `mobile/.env.example` dosyasını `mobile/.env` olarak kopyalayın (paket
   içinde zaten hazır bir `.env` de gelir).
2. İçindeki adresi kendi backend'inizle değiştirin:

   ```
   EXPO_PUBLIC_API_BASE_URL=http://192.168.1.34/erp/api
   ```

   > Dikkat: Sonunda `/api` olmalı, `/api/` değil (son slash olmadan).
   > Değişken adı mutlaka `EXPO_PUBLIC_` ile başlamalı — Expo yalnızca bu
   > önekle başlayan değişkenleri uygulamaya dahil eder.

3. **Her `.env` değişikliğinden sonra** Expo sunucusunu mutlaka önbellek
   temizleyerek yeniden başlatın, yoksa eski adres kullanılmaya devam eder:

   ```bash
   npx expo start -c
   ```

### B.3 — Geliştirme modunda çalıştırma (test)

```bash
npx expo start
```

Terminalde bir QR kod çıkar:
- **Telefonunuzdan** Expo Go uygulamasını açıp QR kodu okutarak anlık test
  edebilirsiniz (telefon ve bilgisayar aynı Wi-Fi'da olmalı).
- **Android emülatöründe** test etmek isterseniz terminalde `a` tuşuna basın.

### B.4 — İlk giriş denemesi

Uygulama açıldığında Login ekranı gelir. A.5'te oluşturduğunuz kullanıcı adı/şifre
ile (veya yönetim panelinden oluşturacağınız bir ÇALIŞAN hesabıyla) giriş
yapabiliyorsanız mobil-backend bağlantısı çalışıyor demektir.

> **Not:** Admin/Yönetici hesapları da mobil uygulamaya giriş yapabilir, ancak
> mobil uygulama esas olarak sahadaki ÇALIŞAN rolündeki kullanıcılar için
> tasarlanmıştır.

---

## 5. Yönetim Panelini Kullanma

Giriş adresi: `https://siteniz.com/erp/admin/login.php`

### 5.1 Genel Bakış (Dashboard)
Girişten sonra karşınıza çıkan ilk sayfa. Şunları gösterir:
- Toplam aktif ürün / cari sayısı
- Bekleyen sipariş sayısı
- Bu ayki onaylanmış ciro
- Son 10 sipariş (tıklayarak detayına gidebilirsiniz)
- Stoğu 10 adedin altına düşmüş ürünler (kırmızı uyarı)

### 5.2 Ürünler
- **Ürünler** menüsünden tüm ürün listesine ulaşırsınız, arama kutusuyla
  kod veya isme göre filtreleyebilirsiniz.
- **"+ Yeni Ürün"** ile ürün ekleyin: ürün kodu, adı, kategori, liste fiyatı,
  koli fiyatı, koli içi adet, KDV oranı, hacim, stok, birim.
- **"Düzenle"** ile mevcut ürünü güncelleyin veya **"Ürünü Pasifleştir"**
  ile listeden (ve mobil uygulamadan) kaldırın — veri kaybolmaz, sadece
  gizlenir (soft delete).
- **↳ Kategoriler** alt menüsünden ürün kategorilerini yönetebilirsiniz
  (üst/alt kategori ilişkisi kurulabilir).

> **Önemli:** Buradan girdiğiniz fiyat/stok değişikliği, mobil uygulamadaki
> çalışanlar bir sonraki API isteğinde (örneğin sayfayı yenilediklerinde)
> otomatik olarak görür. Ayrı bir "senkronizasyon" işlemi yapmanıza gerek yoktur.

### 5.3 Cariler
- **Cariler** menüsünden firma/müşteri listesine ulaşırsınız.
- **"+ Yeni Cari"** ile cari ekleyin: cari kodu, firma adı, yetkili kişi,
  telefon, adres, vergi bilgileri vb.
- Bir cariye tıklayıp **"Detay"**a girdiğinizde:
  - Sol tarafta bilgileri düzenleyebilirsiniz.
  - Sağ üstte **"Yetkili Çalışanlar"** bölümünden, bu cariye hangi
    çalışanların sipariş girebileceğini atayabilir/kaldırabilirsiniz.
    **Bu adım kritik:** bir çalışana atanmayan cariler, o çalışanın mobil
    uygulamasında hiç görünmez.
  - Sağ altta o cariye ait tüm siparişleri görürsünüz.

### 5.4 Siparişler
- **Siparişler** menüsünden tüm siparişleri, durumlarına göre filtreleyerek
  görebilirsiniz (Taslak / Beklemede / Onaylandı / Tamamlandı / İptal).
- Bir siparişin **"Detay"**ına girdiğinizde ürün kalemlerini, tutarları ve
  sipariş bilgilerini (evrak açıklaması, teslim tarihi, ambar, ödeme tipi)
  görürsünüz.
- Sağ tarafta **"Durum Güncelle"** ile siparişin durumunu değiştirebilirsiniz.
  **Stok, sipariş "ONAYLANDI" durumuna geçtiğinde otomatik olarak düşer**
  (detay için bkz. Bölüm 7).

### 5.5 Çalışanlar (sadece ADMIN rolü görebilir)
- **"+ Yeni Çalışan"** ile mobil uygulamayı kullanacak personeli ekleyin.
  Rol olarak `CALISAN`, `YONETICI` veya `ADMIN` seçebilirsiniz.
- Bir çalışanın **"Detay"**ına girdiğinizde:
  - Bilgilerini güncelleyebilir, rolünü/durumunu değiştirebilirsiniz.
  - **Şifresini resetleyebilirsiniz** ("Yeni Şifre" alanını doldurup kaydedin).
  - **Hangi carilerle çalışabileceğini** atayabilirsiniz (bu, cari detay
    sayfasındaki atama ile aynı ilişkiyi günceller, iki yerden de yapılabilir).

### 5.6 Ayarlar
Giriş yapmış kullanıcının kendi şifresini değiştirebileceği sayfa.

---

## 6. Mobil Uygulamayı Kullanma (Çalışan Gözünden)

1. **Giriş Yap** — kullanıcı adı ve şifre ile giriş yapılır.
2. **Cariler sekmesi** — arama kutusuna firma adı veya cari kodu yazılır.
   Sadece o çalışana **atanmış** cariler listelenir.
3. Bir cariye tıklanır → **Cari Sayfası** açılır: o cariye ait son 15 sipariş
   görülür. **"Yeni Sipariş Başlat"** butonuna basılır.
4. **Sepet ekranı** açılır. Sol taraftaki arama kutusundan ürün aranır.
5. Bir ürüne tıklanınca **Ürün Detay** ekranı açılır:
   - Kaç koli sipariş edileceği girilir.
   - İsteğe bağlı olarak 3 kademeli iskonto (%) girilebilir.
   - Sistem anlık olarak net tutarı hesaplar ve **stok yeterliyse**
     "Girilebilir", yetersizse "Girilemez" uyarısı gösterir.
   - **"Sepete Ekle"** ile ürün sepete eklenir.
6. Sepete istenildiği kadar ürün eklendikten sonra **"Devam Et"** butonuna
   basılır → **Sipariş Formu** açılır.
7. Sipariş formunda evrak açıklaması, teslim tarihi, ambar bilgisi ve ödeme
   tipi (Nakit/Vadeli) girilir, **"Siparişi Kaydet"** ile sipariş sisteme
   gönderilir. Sipariş numarası ekrana gösterilir.
8. **Siparişlerim sekmesi** — çalışanın kendi oluşturduğu tüm siparişleri ve
   durumlarını (Beklemede/Onaylandı/vb.) gösterir, aşağı çekerek
   yenilenebilir.
9. **Profil sekmesi** — kullanıcı bilgileri ve **Çıkış Yap** butonu.

---

## 7. Sipariş Akışı ve Stok Mantığı

Bu, projenin en kritik iş kuralıdır, iyi anlaşılmalı:

```
Çalışan sipariş oluşturur
        │
        ▼
   Sipariş durumu: BEKLEMEDE   ← Bu aşamada STOK HENÜZ DÜŞMEZ
        │
        │  (Yönetici/Admin panelden inceler)
        ▼
   Durum "ONAYLANDI" yapılır   ← STOK BURADA DÜŞER (otomatik, tek seferlik)
        │
        ▼
   Durum "TAMAMLANDI" yapılır  ← Sevkiyat/fatura tamamlandı anlamına gelir
```

**Neden böyle tasarlandı?** Eğer stok, sipariş oluşturulur oluşturulmaz
düşseydi, bir çalışanın yanlışlıkla girdiği (veya iptal edeceği) bir sipariş
anında gerçek stoğu bloke ederdi. "BEKLEMEDE → ONAYLANDI" ara adımı, yöneticiye
kontrol imkânı tanır.

**Fiyat güvenliği:** Mobil uygulama sepette gösterdiği fiyat ve tutarları
*sadece önizleme* olarak kullanır. Sipariş gerçekten kaydedilirken
(`orders/create.php`), sunucu ürünün güncel fiyatını ve stoğunu **MySQL'den
yeniden okur**. Yani biri APK'yı manipüle edip "100 TL" yerine "1 TL" göndermeye
çalışsa bile, sunucu bu veriye güvenmez ve gerçek fiyatı kullanır.

**Stok hareketleri:** Her stok düşümü, `stock_movements` tablosuna da
kaydedilir (hangi sipariş yüzünden, ne zaman, ne kadar düştüğü). İleride bu
tablo üzerinden bir "Stok Hareketleri Raporu" sayfası kolayca eklenebilir.

---

## 8. Android APK Üretimi (Yayına Alma)

Geliştirme/test bittiğinde, çalışanların telefonlarına kuracağı gerçek bir
APK dosyası üretmek için:

1. Expo hesabı oluşturun (ücretsiz): https://expo.dev
2. Terminalde EAS CLI'ı kurun:
   ```bash
   npm install -g eas-cli
   eas login
   ```
3. `mobile/` klasöründeyken:
   ```bash
   eas build --platform android
   ```
4. İlk seferinde birkaç soru sorulur (proje adı, imzalama anahtarı vb.) —
   varsayılan seçenekleri onaylamanız yeterlidir.
5. Build işlemi Expo'nun sunucularında birkaç dakika sürer. Bitince size bir
   **indirme linki** verilir — bu link üzerinden `.apk` dosyasını indirip
   çalışanların telefonlarına kurabilir, ya da doğrudan Google Play Console'a
   yükleyebilirsiniz.

> Şirket içi kullanım için Play Store'a yüklemeden, APK dosyasını doğrudan
> WhatsApp/e-posta/ortak sürücü ile dağıtmanız da mümkündür (Android
> ayarlarından "Bilinmeyen kaynaklardan yükleme" izni gerekir).

---

## 9. Sık Karşılaşılan Sorunlar

| Sorun | Olası Sebep / Çözüm |
|---|---|
| Mobil uygulama "Veritabanı bağlantı hatası" veriyor | `config/database.php` içindeki bilgiler yanlış. cPanel'deki MySQL kullanıcı/şifre/veritabanı adını tekrar kontrol edin. |
| Mobil uygulamada giriş yapılamıyor ama admin panelinde yapılabiliyor | `app.json`'daki `apiBaseUrl` yanlış veya sonunda `/` fazlalığı var. `https://site.com/erp/api` şeklinde, sonunda slash olmadan olmalı. |
| "CORS" veya ağ hatası alınıyor | Backend'de HTTPS aktif değil olabilir; mobil uygulama `http://` adreslere bazı Android sürümlerinde bağlanamaz. SSL'i aktif edin. |
| Çalışan mobil uygulamada hiç cari göremiyor | O çalışana henüz cari atanmamış. Yönetim panelinden ilgili carinin detayına girip "Yetkili Çalışanlar" bölümünden çalışanı ekleyin. |
| Sipariş oluşturulurken "Yetersiz stok" hatası | Ürünün gerçek stoğu, girilen koli × koli içi adetten az. Ürün detayında stoğu kontrol edin/güncelleyin. |
| setup.php sayfası "Kurulum zaten tamamlanmış" diyor | Sistemde zaten bir çalışan kaydı var. Yönetim panelinden normal giriş yapın veya phpMyAdmin'den `employees` tablosunu kontrol edin. |
| Ürün/cari kodu eklerken "zaten kayıtlı" hatası | O kod/ad başka bir kayıtta zaten kullanılıyor (`urun_kodu` ve `cari_kodu` alanları benzersiz olmalı). Farklı bir kod deneyin. |

---

## 10. Güvenlik Kontrol Listesi

Canlıya almadan önce şunları mutlaka yapın:

- [ ] `setup.php` dosyasını kullandıktan sonra sunucudan sildiniz mi?
- [ ] Site `https://` ile açılıyor mu (SSL aktif mi)?
- [ ] `config/database.php` içindeki şifre güçlü mü (rastgele, en az 12 karakter)?
- [ ] Admin panel şifreleri varsayılan/basit değil mi (örn. "admin123" kalıcı
      olarak kullanılmamalı)?
- [ ] `config/` ve `includes/` klasörlerindeki `.htaccess` dosyaları hosting'e
      yüklendi mi (bu klasörlere doğrudan tarayıcıdan erişimi engeller)?
- [ ] Gereksiz çalışan/admin hesapları pasifleştirildi mi?
- [ ] Her çalışana yalnızca gerçekten çalıştığı cariler atandı mı (aşırı
      geniş yetki vermekten kaçının)?

---

## 11. Klasör / Dosya Haritası

```
ERP-APP/
│
├── backend/                        ← cPanel'e yüklenecek kısım
│   ├── setup.php                   ← İlk kurulumda 1 kez çalıştırılır, sonra silinir
│   ├── index.php                   ← Kök adres, login sayfasına yönlendirir
│   ├── admin/                      ← Tarayıcıdan kullanılan yönetim paneli
│   │   ├── login.php / logout.php
│   │   ├── index.php               ← Genel Bakış (dashboard)
│   │   ├── products.php / product-add.php / product-edit.php
│   │   ├── categories.php
│   │   ├── customers.php / customer-add.php / customer-detail.php
│   │   ├── orders.php / order-detail.php
│   │   ├── employees.php / employee-add.php / employee-detail.php
│   │   └── settings.php
│   ├── api/                        ← Mobil uygulamanın konuştuğu REST API
│   │   ├── auth/ (login, logout)
│   │   ├── products/ (list, get, create, update, delete)
│   │   ├── categories/ (list, create, delete)
│   │   ├── customers/ (list, get, create, update, delete)
│   │   ├── orders/ (list, get, create, update)
│   │   └── employees/ (list, get, create, update, delete)
│   ├── config/
│   │   ├── database.php            ← BURAYI DOLDURMANIZ GEREKİYOR (A.4)
│   │   └── config.php
│   ├── includes/                   ← Ortak PHP fonksiyonları (auth, header, vb.)
│   ├── assets/style.css            ← Yönetim panelinin görsel stili
│   ├── database/schema.sql         ← phpMyAdmin'e yüklenecek veritabanı şeması
│   └── README.md
│
└── mobile/                         ← Bilgisayarınızda geliştirilecek kısım
    ├── App.tsx                     ← Navigasyon giriş noktası
    ├── app.json                    ← BURAYI DOLDURMANIZ GEREKİYOR (B.2)
    ├── package.json
    ├── app/                        ← Ekranlar (Figma ile birebir eşleşir)
    │   ├── LoginScreen.tsx
    │   ├── MainTabs.tsx            ← Alt sekme menüsü (Cariler/Siparişlerim/Profil)
    │   ├── CariAramaScreen.tsx
    │   ├── CariSayfasiScreen.tsx
    │   ├── UrunDetayScreen.tsx
    │   ├── SepetScreen.tsx
    │   ├── SiparisFormuScreen.tsx
    │   ├── SiparislerimScreen.tsx
    │   └── ProfilScreen.tsx
    ├── components/HeaderBar.tsx
    ├── services/                   ← API çağrıları burada toplanır
    │   ├── api.ts                  ← Token yönetimi + fetch wrapper
    │   ├── authService.ts
    │   ├── productService.ts
    │   ├── customerService.ts
    │   ├── orderService.ts
    │   └── CartContext.tsx         ← Sepet state yönetimi
    ├── constants/theme.ts          ← Renkler, boşluklar
    └── README.md
```

---

### Bir sonraki geliştirme fikirleri (opsiyonel)

- Stok hareketleri raporu (admin panelde `stock_movements` tablosunu listeleyen bir sayfa)
- Mobilde offline ürün/cari önbelleği (dokümanın 14. bölümünde bahsedilen fikir)
- Sipariş için PDF/yazdırılabilir özet çıktısı ("Özet Çıkar" butonu şu an görsel yer tutucu)
- Push bildirim (sipariş onaylandığında çalışana bildirim gönderme)

Bu kılavuzdaki adımların tamamını uyguladığınızda sistem uçtan uca çalışır
durumda olacaktır. Herhangi bir adımda takılırsanız, ilgili bölümün başlığını
belirterek tekrar sorabilirsiniz.
