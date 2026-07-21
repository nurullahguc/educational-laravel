# Educational Laravel

Tek bir Laravel backend'i içinde toplanmış, **frontend pratiği için** hazırlanmış
küçük ama gerçekçi REST API projeleri koleksiyonu. Amaç: React / Vue / vb. öğrenirken
bağlanacak, mantıklı kurgulanmış backend API'lerine sahip olmak.

Her proje kendi modülü içinde yaşar; kimlik doğrulama (auth) tüm projelerin paylaştığı
ortak çekirdektir. Backend yalnızca API üretir — frontend ayrı geliştirilir.

## İçindeki Projeler

| Proje | Açıklama | Dokümantasyon |
|-------|----------|---------------|
| **TicketFlow** | Kullanıcıların yalnızca kendilerine ait ticket'ları yönetebildiği (CRUD + arama, filtre, sıralama, sayfalama) ticket yönetim API'si. Sanctum SPA cookie authentication. | [`_docs/ticketflow.md`](_docs/ticketflow.md) |

> Her yeni projenin ayrıntılı API sözleşmesi [`_docs/`](_docs/) klasöründedir.
> Buradaki tablo yalnızca kısa bir özet sunar.

## Mimari (özet)

```
app/
├── Http/                      # PAYLAŞIMLI çekirdek (tüm projeler kullanır)
│   ├── Controllers/Auth/      # register / login / logout / user
│   ├── Requests/Auth/
│   └── Resources/UserResource.php
├── Models/User.php            # Paylaşımlı kullanıcı modeli
└── Modules/                   # HER PROJE KENDİ MODÜLÜNDE
    └── TicketFlow/
        ├── Enums/ · Models/ · Policies/ · Routes/
        ├── Http/{Controllers,Requests,Resources}/
        ├── Database/{Migrations,Factories,Seeders}/
        └── TicketFlowServiceProvider.php
```

Detaylı mimari kuralları ve **yeni proje ekleme rehberi** için `CLAUDE.md` dosyasına bakın.

## Gereksinimler

- PHP 8.2+
- Composer
- MySQL / MariaDB (XAMPP ile birlikte gelir)
- Node.js (yalnızca Vite/asset gerekiyorsa; API için şart değil)

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate

# MySQL veritabanını oluştur (MariaDB/MySQL çalışıyor olmalı)
#   CREATE DATABASE educational_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# .env içindeki DB_* değerlerini kendi ortamına göre ayarla (XAMPP varsayılanı: root / şifresiz)

php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

## Testler

```bash
php artisan test
```

Testler ayrı bir **in-memory SQLite** veritabanı kullanır (`phpunit.xml`), bu yüzden
geliştirme veritabanına asla dokunmaz.

## Frontend Entegrasyonu (SPA)

- Backend: `http://localhost:8000`
- Frontend (Vite): `http://localhost:5173`
- Kimlik doğrulama: **Sanctum SPA cookie/session** (Bearer token değil).
- Akış: frontend önce `GET /sanctum/csrf-cookie` çağırır, ardından `POST /api/login`
  veya `POST /api/register` yapar. Axios/fetch **`withCredentials: true`** ile çalışmalıdır.

Her projenin endpoint sözleşmesi, örnek istek/yanıtlar ve frontend entegrasyon
notları için ilgili `_docs/<proje>.md` dosyasına bakın.

## Demo Kullanıcılar (yalnızca geliştirme verisi)

| Email | Şifre |
|-------|-------|
| `nurullah@example.com` | `password123` |
| `demo@example.com` | `password123` |
