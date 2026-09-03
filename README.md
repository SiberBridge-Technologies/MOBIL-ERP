# ERP-APP

Şirket içi ERP / sipariş / stok / cari yönetim sistemi.

> **Yeni başlıyorsanız buradan başlayın:** [`KULLANIM_KILAVUZU.md`](./KULLANIM_KILAVUZU.md)
> — kurulumdan panel/mobil kullanımına kadar her adımı anlatan detaylı Türkçe kılavuz.

- `backend/` — PHP + MySQL yönetim paneli ve REST API (cPanel'e yüklenecek)
- `mobile/` — React Native + Expo çalışan uygulaması (Android APK)

Detaylar için her klasördeki README.md dosyasına bakın.

## Figma tasarımından çıkarılan ekran akışı

```
Login
  └── Cari Arama (boş / sonuç listesi)
        └── Cari Sayfası (son 15 sipariş, Yeni Sipariş Başlat)
              └── Sepet (ürün arama + sepet)
                    └── Ürün Detay (koli, iskonto 1/2/3, vade, sepete ekle)
                    └── Sipariş Formu (evrak açıklaması, teslim tarihi, ambar, ödeme tipi, kaydet)
```

## Hızlı özet

1. `backend/database/schema.sql` dosyasını MySQL'e yükleyin ve `config/database.php`'yi güncelleyin.
2. `https://siteniz.com/erp/setup.php` ile ilk admin kullanıcıyı oluşturun, sonra bu dosyayı silin.
3. `mobile/app.json` içindeki `apiBaseUrl`'i backend adresinizle güncelleyin.
4. Yönetim paneli (`backend/admin/`) tam işlevsel: ürün, kategori, cari, sipariş, çalışan yönetimi.
5. Çalışan-cari atamalarını admin panelinden (cari detay veya çalışan detay sayfası) yapın.

Detaylı adım adım anlatım için **[KULLANIM_KILAVUZU.md](./KULLANIM_KILAVUZU.md)** dosyasına bakın.
