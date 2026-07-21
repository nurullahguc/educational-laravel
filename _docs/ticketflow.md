# TicketFlow API

Kullanıcıların hesap oluşturup **yalnızca kendilerine ait** ticket'ları yönetebildiği
küçük bir ticket yönetim API'si. Kullanıcı izolasyonu her endpoint'te zorunludur:
bir kullanıcı başka birinin ticket'ına hiçbir şekilde erişemez.

- **Base URL:** `http://localhost:8000`
- **Format:** Tüm istek/yanıtlar JSON
- **Auth:** Laravel Sanctum **SPA cookie/session** (Bearer token yok)

---

## 1. Kimlik Doğrulama Akışı (SPA)

Auth, backend'deki tüm projeler için ortaktır. Session/cookie tabanlıdır.

1. Frontend, login/register'dan **önce** CSRF cookie'sini alır:
   ```
   GET /sanctum/csrf-cookie
   ```
   Bu, `XSRF-TOKEN` cookie'sini set eder.
2. Sonraki tüm state değiştiren isteklerde tarayıcı `XSRF-TOKEN` cookie'sini
   `X-XSRF-TOKEN` header'ı olarak geri gönderir (axios bunu otomatik yapar).
3. İstemci **credentials (cookie)** göndermelidir.

### Axios kurulumu (örnek)

```js
import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true,               // cookie gönder/al
  withXSRFToken: true,                 // axios >= 1.x
  headers: { Accept: "application/json" },
});

// login öncesi bir kez:
await api.get("/sanctum/csrf-cookie");
await api.post("/api/login", { email, password });
```

> **Önemli:** Frontend ve backend için `localhost` kullanın; `localhost` ile
> `127.0.0.1`'i karıştırmayın (cookie domain'i buna bağlıdır).

---

## 2. Auth Endpoint'leri

| Method | Endpoint | Erişim | Açıklama |
|--------|----------|--------|----------|
| POST | `/api/register` | Guest | Yeni kullanıcı oluşturur ve oturum açar (201) |
| POST | `/api/login` | Guest | Oturum açar (200) |
| POST | `/api/logout` | Auth | Oturumu kapatır (204) |
| GET | `/api/user` | Auth | Giriş yapan kullanıcıyı döndürür (200) |

### POST `/api/register`

Request:
```json
{
  "name": "Nurullah Güç",
  "email": "nurullah@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Kurallar:
- `name`: required, string, max 100
- `email`: required, geçerli email, max 255, unique (kaydetmeden önce trim + lowercase)
- `password`: required, confirmed, min 8

Başarılı yanıt `201`:
```json
{
  "data": {
    "id": 1,
    "name": "Nurullah Güç",
    "email": "nurullah@example.com",
    "created_at": "2026-07-21T18:00:00.000000Z"
  }
}
```

### POST `/api/login`

Request:
```json
{ "email": "nurullah@example.com", "password": "password123", "remember": false }
```

- Başarılı: `200`, gövdede kullanıcı (`data` sarmalı). Session ID yenilenir.
- Hatalı bilgiler: `422`, genel hata mesajı (kullanıcının var olup olmadığı sızdırılmaz).
- Rate limit: email + IP başına dakikada 5 deneme; aşılırsa `422` throttle mesajı.

### POST `/api/logout`
Session'ı invalidate eder, CSRF token'ı yeniler → `204 No Content`.

### GET `/api/user`
```json
{ "data": { "id": 1, "name": "...", "email": "...", "created_at": "..." } }
```
Password / token gibi hassas alanlar **asla** dönmez.

---

## 3. Ticket Endpoint'leri

Tümü `auth:sanctum` altında; giriş yapılmamışsa `401` (JSON) döner.

| Method | Endpoint | Açıklama | Başarı |
|--------|----------|----------|--------|
| GET | `/api/tickets` | Kullanıcının ticket'larını listeler (arama/filtre/sıralama/sayfalama) | 200 |
| POST | `/api/tickets` | Ticket oluşturur | 201 |
| GET | `/api/tickets/{id}` | Ticket detayı | 200 |
| PUT/PATCH | `/api/tickets/{id}` | Günceller (PATCH kısmi) | 200 |
| DELETE | `/api/tickets/{id}` | Siler | 204 |

Başka kullanıcının `{id}`'si istenirse **`404`** döner (kaynak enumeration'ı azaltmak için).

### Create / Update payload

```json
{
  "title": "Login sayfasındaki hata",
  "description": "Yanlış şifreden sonra hata mesajı görünmüyor.",
  "status": "open",
  "priority": "high",
  "due_date": "2026-08-01"
}
```

Validation:
- `title`: required, string, min 3, max 150
- `description`: required, string, min 10, max 5000
- `status`: required, şu değerlerden biri: `open`, `in_progress`, `resolved`, `closed`
- `priority`: required, şu değerlerden biri: `low`, `medium`, `high`, `critical`
- `due_date`: nullable, `Y-m-d` formatı

> **PATCH** kısmi güncellemeyi destekler: yalnızca gönderilen alanlar doğrulanır/güncellenir.
> `user_id` istemciden **asla** kabul edilmez; her zaman giriş yapan kullanıcıya atanır.

### Ticket resource formatı

```json
{
  "data": {
    "id": 12,
    "title": "Login sayfasındaki hata",
    "description": "Yanlış şifreden sonra hata mesajı görünmüyor.",
    "status": "open",
    "priority": "high",
    "due_date": "2026-08-01",
    "created_at": "2026-07-21T18:00:00.000000Z",
    "updated_at": "2026-07-21T18:00:00.000000Z"
  }
}
```

---

## 4. Listeleme: arama, filtre, sıralama, sayfalama

`GET /api/tickets` query parametreleri:

| Parametre | Örnek | Davranış |
|-----------|-------|----------|
| `search` | `login` | `title` ve `description` içinde arar (case-insensitive) |
| `status` | `open` | Status filtresi |
| `priority` | `high` | Priority filtresi |
| `sort` | `created_at` | İzinli: `created_at`, `updated_at`, `title`, `due_date` |
| `direction` | `desc` | `asc` veya `desc` |
| `page` | `2` | Sayfa numarası |
| `per_page` | `10` | Sayfa boyutu (min 5, max 50) |

Varsayılanlar: `sort=created_at`, `direction=desc`, `per_page=10`.
Geçersiz `status` / `priority` / `sort` / `direction` / `per_page` değerleri → `422`.

Yanıt, Laravel API Resource collection'ın standart yapısıdır:

```json
{
  "data": [ { "...ticket..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 10, "total": 42, "last_page": 5 }
}
```

---

## 5. Hata Yanıtları

Validation (`422`):
```json
{
  "message": "The given data was invalid.",
  "errors": { "title": ["The title field is required."] }
}
```

- Yetkisiz (auth gerekli): `401` JSON `{ "message": "Unauthenticated." }` (HTML redirect yok).
- Başkasının / var olmayan ticket'ı: `404`.

---

## 6. Endpoint Özet Tablosu (handoff)

| Method | Path | Auth | Body | Başarı |
|--------|------|------|------|--------|
| GET | `/sanctum/csrf-cookie` | — | — | 204 |
| POST | `/api/register` | Guest | name, email, password, password_confirmation | 201 |
| POST | `/api/login` | Guest | email, password, remember? | 200 |
| POST | `/api/logout` | Auth | — | 204 |
| GET | `/api/user` | Auth | — | 200 |
| GET | `/api/tickets` | Auth | query params | 200 |
| POST | `/api/tickets` | Auth | title, description, status, priority, due_date? | 201 |
| GET | `/api/tickets/{id}` | Auth | — | 200 |
| PUT/PATCH | `/api/tickets/{id}` | Auth | kısmi/tam alanlar | 200 |
| DELETE | `/api/tickets/{id}` | Auth | — | 204 |

## 7. Demo Kullanıcılar (yalnızca geliştirme)

| Email | Şifre |
|-------|-------|
| `nurullah@example.com` | `password123` |
| `demo@example.com` | `password123` |

Her demo kullanıcının farklı status/priority değerlerine sahip 10 örnek ticket'ı vardır.
`php artisan migrate:fresh --seed` ile yeniden üretebilirsin.
