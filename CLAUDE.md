# CLAUDE.md — Educational Laravel

Bu dosya, projenin nasıl kurgulandığını ve **yeni bir proje eklerken** hangi
kurallara uyulacağını anlatır. Amaç: her seferinde mimarinin baştan anlatılmasına
gerek kalmaması.

## Proje Amacı

Tek bir Laravel backend'i içinde, **frontend pratiği için** birden fazla küçük ama
gerçekçi REST API projesi barındırıyoruz. Kullanıcı (proje sahibi) sadece frontend'i
(React/Vue/vb.) geliştirir; bu repo **yalnızca API üretir**. Projeler giriş seviyesi
değil ama aşırı kapsamlı da değil — "öğretici ama ciddi" seviyede.

**Frontend kodu YAZMA.** Yalnızca backend/API.

## Mimari: Modüler Monolit

Her "proje" `app/Modules/<ProjeAdı>/` altında kendi kendine yeten bir modüldür.
Kimlik doğrulama (auth) ve `User` modeli **paylaşımlı çekirdektir** — tüm projeler
aynı hesapları kullanır, her proje kendi auth'unu tekrar yazmaz.

```
app/
├── Http/                        # PAYLAŞIMLI (tüm modüller kullanır)
│   ├── Controllers/
│   │   ├── Controller.php       # base — AuthorizesRequests, ValidatesRequests
│   │   └── Auth/AuthController.php
│   ├── Requests/Auth/{Register,Login}Request.php
│   └── Resources/UserResource.php
├── Models/User.php              # paylaşımlı; modüller ilişki ekleyebilir (örn. tickets())
└── Modules/
    └── TicketFlow/              # örnek modül — yeni projeler bunu taklit eder
        ├── Enums/
        ├── Models/
        ├── Policies/
        ├── Http/{Controllers,Requests,Resources}/
        ├── Database/{Migrations,Factories,Seeders}/
        ├── Routes/api.php
        └── TicketFlowServiceProvider.php
```

Namespace: `App\` → `app/` olduğundan `App\Modules\TicketFlow\...` otomatik
autoload edilir; `composer.json`'a dokunmaya gerek yoktur.

### Modülü uygulamaya bağlayan noktalar

- **Service Provider** (`<Modül>ServiceProvider`): her modülün girişidir. `boot()` içinde:
  - `loadMigrationsFrom(__DIR__.'/Database/Migrations')` — modül migration'ları
  - `Gate::policy(Model::class, Policy::class)` — policy'ler (modül namespace'inde
    olduğu için otomatik keşfedilmez, **elle** kaydet)
  - `Route::middleware('api')->prefix('api')->group(__DIR__.'/Routes/api.php')`
  - Provider'ı `bootstrap/providers.php`'ye ekle.
- **Factory**: modül namespace'inde olduğundan model içinde `newFactory()` override et:
  ```php
  protected static function newFactory(): TicketFactory { return TicketFactory::new(); }
  ```
- **Seeder**: `database/seeders/DatabaseSeeder.php` içindeki `$this->call([...])`'e ekle.

## Kimlik Doğrulama (Paylaşımlı)

- **Laravel Sanctum SPA cookie/session** auth. Bearer/personal access token KULLANMA.
- Endpoint'ler: `POST /api/register`, `POST /api/login`, `POST /api/logout`, `GET /api/user`
  (bkz. `routes/api.php` + `App\Http\Controllers\Auth\AuthController`).
- Frontend akışı: önce `GET /sanctum/csrf-cookie`, sonra login/register; `withCredentials`.
- `bootstrap/app.php`:
  - `->withRouting(api: routes/api.php, ...)`
  - `$middleware->statefulApi()` (Sanctum stateful middleware)
  - Exception'lar `api/*` ve `expectsJson()` isteklerinde **JSON** döner; yetkisizde
    HTML redirect değil `401` JSON.
- CORS: `config/cors.php`, `supports_credentials = true`, origin `FRONTEND_URL`
  (`.env` → `http://localhost:5173`).
- `SESSION_DOMAIN=localhost`, `SANCTUM_STATEFUL_DOMAINS` frontend+backend host'larını içerir.
- `localhost` ile `127.0.0.1`'i karıştırma.

## Veritabanı

- **Dev:** MySQL/MariaDB (XAMPP). DB adı `educational_laravel`, `.env`'de `DB_*`.
- **Test:** ayrı **in-memory SQLite** (`phpunit.xml`). Testler dev DB'ye asla dokunmaz.
- Migration'ları **schema builder** ile yaz (raw SQL değil) → hem MySQL hem SQLite uyumlu.
- Native DB enum kullanma: `string` kolon + **PHP backed enum** + model cast.

## Testler (Pest)

- `php artisan test`. Her test dosyası `uses(RefreshDatabase::class)`.
- `tests/TestCase.php` her isteğe `Origin: http://localhost` header'ı ekler — böylece
  Sanctum isteği "frontend'den geliyor" sayıp stateful (session) middleware'i uygular.
  Bu olmadan `$request->session()` erişimi patlar.
- Auth gerektiren testlerde `actingAs($user)`. Auth akışını (login/logout) uçtan test
  ederken guard durumunu `auth()->guard('web')->check()` ile doğrula (array session
  driver istekler arası kalıcı olmadığı için `assertGuest()` cross-request güvenilmez).
- Yeni proje eklerken feature testleri şart: CRUD, validation, **kullanıcı izolasyonu**
  (başkasının kaydına erişememe), filtre/arama/sıralama/sayfalama, geçersiz query → 422.

## Güvenlik Kuralları (her modülde)

- Sorguyu **her zaman** `$request->user()->relation()` üzerinden başlat → ownership garanti.
- Başkasının / olmayan kaydı için `findOrFail` ile **404** (enumeration'ı azaltır).
- Ek savunma olarak Policy tanımla ve `$this->authorize(...)` çağır.
- İlişki foreign key'i (`user_id` gibi) istemciden **mass-assign edilmez**; model
  `$fillable`'ına koyma, auth kullanıcıdan ata.
- Hassas alanları Resource'ta gizle. Production stack trace'i JSON'a sızdırma.

## Dokümantasyon Kuralı (yeni proje eklerken ZORUNLU)

1. `_docs/<proje>.md` — tam API sözleşmesi (endpoint'ler, request/response, validation,
   frontend entegrasyon notları, örnekler). `_docs/ticketflow.md`'yi şablon al.
2. `_docs/README.md` tablosuna satır ekle.
3. Kök `README.md` "İçindeki Projeler" tablosuna kısa açıklama + `_docs` linki ekle.
4. Demo kullanıcı/seed bilgisi varsa README + doküman içinde "yalnızca geliştirme" notuyla belirt.

## Referans Modül

`TicketFlow` tam çalışan referanstır (24 feature testi geçiyor). Yeni bir projeye
başlarken yapısını birebir taklit et.
