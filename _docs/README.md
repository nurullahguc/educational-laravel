# _docs — Proje Dokümantasyonları

Bu klasör, `Educational Laravel` backend'i içindeki her projenin ayrıntılı API
sözleşmesini içerir. Frontend'ini bağlarken buradaki dosyaları referans al.

## Projeler

| Proje | Dosya | Kısa Açıklama |
|-------|-------|----------------|
| TicketFlow | [`ticketflow.md`](ticketflow.md) | Kullanıcıya özel ticket yönetimi (CRUD + arama/filtre/sıralama/sayfalama), Sanctum SPA auth. |

## Her doküman şunları içerir

- Temel URL, auth akışı ve gerekli header'lar
- Tüm endpoint'ler (method, path, request body, response, HTTP status)
- Validation kuralları
- Frontend entegrasyon notları (CORS, CSRF, `withCredentials`)
- Örnek istek/yanıtlar

> Genel mimari ve yeni proje ekleme rehberi kök dizindeki `CLAUDE.md` dosyasındadır.
