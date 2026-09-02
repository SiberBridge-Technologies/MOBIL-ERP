# ERP-APP — Çalışan Mobil Uygulaması (React Native + Expo)

Figma tasarımındaki ekranlarla birebir eşleşen ekranlar:

| Figma frame | Ekran dosyası |
|---|---|
| login 1 | app/LoginScreen.tsx |
| cari-arama-bos 2 / cari-arama-sonuc 3 | app/CariAramaScreen.tsx |
| cari-sayfasi 4 | app/CariSayfasiScreen.tsx |
| Urun-Detay-Screen 6 | app/UrunDetayScreen.tsx |
| sepet-bos 5 / Sepet-Sayfasi-Screen 7 | app/SepetScreen.tsx |
| Siparis-Formu-Screen 8 | app/SiparisFormuScreen.tsx |

## Kurulum

```bash
npm install
```

`app.json` içindeki `extra.apiBaseUrl` değerini backend'inizin gerçek adresiyle güncelleyin:

```json
"extra": { "apiBaseUrl": "https://sizin-domaininiz.com/api" }
```

## Geliştirme

```bash
npx expo start
```

## Android APK üretimi (Production)

```bash
npm install -g eas-cli
eas login
eas build --platform android
```

## Mimari notu

Uygulama MySQL'e **asla** doğrudan bağlanmaz. `services/api.ts` tüm istekleri
`Authorization: Bearer <token>` header'ıyla PHP REST API'ye gönderir. Sepetteki
fiyat/iskonto hesapları yalnızca önizleme amaçlıdır — kesin tutar, sipariş
oluşturulurken sunucu tarafında (orders/create.php) yeniden hesaplanır.
