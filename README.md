# ERP Management System

Modern, ölçeklenebilir ve merkezi bir işletme yönetim sistemi. ERP platformu; yöneticilerin şirket operasyonlarını merkezi bir yönetim panelinden kontrol etmesini, çalışanların ise React Native tabanlı mobil uygulama üzerinden görev, izin, duyuru ve çalışma süreçlerini yönetmesini sağlar.

## 🚀 Proje

Sistem iki ana uygulamadan oluşmaktadır:

* **Yönetim Paneli:** Yöneticilerin çalışanları, görevleri, izinleri, duyuruları, stokları ve sistem operasyonlarını yönetebildiği web tabanlı panel.
* **Çalışan Uygulaması:** Çalışanların görevlerini, izinlerini, duyuruları ve kişisel çalışma bilgilerini mobil cihaz üzerinden takip edebildiği React Native uygulaması.

Her iki uygulama merkezi bir backend/API ve SQL veritabanı üzerinden haberleşir.

## ✨ Temel Özellikler

* 🔐 Güvenli kullanıcı girişi ve kimlik doğrulama
* 👥 Çalışan ve kullanıcı yönetimi
* 🏢 Departman ve pozisyon yönetimi
* 📋 Görev oluşturma ve görev atama
* 📊 Görev ve operasyon takibi
* 🏖️ İzin talebi ve onay sistemi
* 📢 Şirket duyuruları
* 🔔 Bildirim sistemi
* 📦 Stok ve ürün yönetimi
* 📈 Yönetim dashboard'u ve istatistikler
* 📝 İşlem kayıtları ve audit log
* 👤 Rol tabanlı yetkilendirme
* 📱 React Native mobil çalışan uygulaması
* 🌐 Web tabanlı yönetim paneli
* 🗄️ Merkezi SQL veritabanı
* 🔌 REST API tabanlı iletişim
* 📱 Responsive ve modern kullanıcı arayüzü

## 🏗️ Mimari

```text
                 ┌─────────────────────┐
                 │   Yönetim Paneli    │
                 │        Web          │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │      REST API       │
                 │      Backend        │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │    SQL Database     │
                 └──────────┬──────────┘
                            ▲
                            │
                 ┌──────────┴──────────┐
                 │   React Native App  │
                 │      Çalışan        │
                 └─────────────────────┘
```

Yönetim paneli ve mobil uygulama doğrudan veritabanına bağlanmaz. Tüm veri işlemleri merkezi API üzerinden gerçekleştirilir.

Bu yapı güvenlik, ölçeklenebilirlik ve gelecekte yeni istemcilerin sisteme eklenebilmesi açısından temel mimariyi oluşturur.

## 🛠️ Teknolojiler

**Mobile**

* React Native
* JavaScript / TypeScript

**Management Panel**

* Web teknolojileri
* Modern responsive UI

**Backend**

* REST API
* Authentication & Authorization
* Server-side business logic

**Database**

* SQL
* İlişkisel veri modeli

## 🔐 Güvenlik

Sistem güvenlik odaklı bir mimariyle geliştirilmektedir.

* Token tabanlı authentication
* Rol ve yetki kontrolü
* Şifre hashleme
* API endpoint koruması
* Input validation
* SQL injection koruması
* Audit logging
* Güvenli hata yönetimi

## 📂 Proje Yapısı

```text
ERP-APP/
│
├── YONETIM-PANELI/
│   ├── frontend/
│   ├── backend/
│   ├── api/
│   └── database/
│
└── CALISAN-UYGULAMASI/
    ├── src/
    ├── android/
    ├── ios/
    └── ...
```

## 🎯 Hedef

Projenin amacı, işletmelerin günlük operasyonlarını farklı sistemler arasında bölünmeden tek bir merkezi ERP platformu üzerinden yönetebilmesini sağlamaktır.

Mimari; ileride vardiya yönetimi, puantaj, QR/barkod sistemi, dosya yönetimi, gelişmiş raporlama, push notifications ve AI destekli ERP özellikleri gibi yeni modüllerin eklenmesine uygun şekilde tasarlanmıştır.

## 📌 Proje Durumu

🚧 **Aktif geliştirme aşamasındadır.**

Yeni modüller, güvenlik geliştirmeleri ve kullanıcı deneyimi iyileştirmeleri düzenli olarak projeye eklenecektir.
