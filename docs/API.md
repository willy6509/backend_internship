# SENTINEL — API Endpoints & Examples

This document lists the public API endpoints provided by the application, with request and response examples, plus recommended cybersecurity controls to keep the system safe.

Base path: `/api`

---

**POST /api/login**
- Purpose: obtain authentication token (Sanctum)
- Auth: public (throttled)

Request (application/json):
```
{
  "email": "user@example.com",
  "password": "secret-password"
}
```

Success response (200):
```
{
  "token": "1|abcd...",
  "user": { "id": 1, "name": "Admin" }
}
```

Failure (401):
```
{
  "message": "Unauthorized"
}
```

---

**POST /api/internal/ingest**
- Purpose: crawler (Python) sends scraped JSON to ingest into `CrawledData`.
- Auth: Protected by `CheckApiKey` (IP whitelist + `x-api-key`) and rate-limited.
- Headers:
  - `Content-Type: application/json`
  - `x-api-key: <SENTINEL_API_KEY>`

Request (application/json):
```
{
  "type": "post",
  "username": "johndoe",
  "posted_at": "2026-02-25T10:23:00Z",
  "content": "This is scraped content",
  "url": "https://x.com/post/123",
  "parent_url": null
}
```

Success (inserted, 201):
```
{
  "status": "inserted"
}
```

Ignored duplicate (200):
```
{
  "status": "ignored_duplicate"
}
```

Ignored (duplicate content check) (200):
```
{
  "status": "ignored",
  "message": "Konten teks sudah ada di database, diabaikan.",
  "url": "https://x.com/post/123"
}
```

Errors:
- 401 Unauthorized — invalid or missing API key
- 403 Forbidden — IP not whitelisted
- 422 Unprocessable Entity — validation failed

---

**POST /api/logout**
- Purpose: revoke current Sanctum token
- Auth: `auth:sanctum`

Request headers:
- `Authorization: Bearer <token>`

Response (200):
```
{ "message": "Logged out" }
```

---

**GET /api/crawled-data**
- Purpose: list paginated crawled data for dashboard
- Auth: `auth:sanctum` + role `officer,analyst,admin,superadmin`
- Query params: `limit` (default 50), `search`, `start_date`, `end_date`

Example request:
`GET /api/crawled-data?limit=25&search=keyword&start_date=2026-01-01&end_date=2026-02-25`

Success (200):
```
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ { "id": 123, "type": "post", "username": "johndoe", ... } ],
    "per_page": 25,
    "total": 100
  }
}
```

---

**GET /api/crawled-data/{id}**
- Purpose: retrieve full post details plus replies
- Auth: `auth:sanctum` + role `officer,analyst,admin,superadmin`

Success (200):
```
{
  "success": true,
  "data": {
    "post": { "id": 123, "type": "post", "content": "...", "raw_payload": { ... } },
    "replies": [ { "id": 124, "content": "..." } ]
  }
}
```

Not found (404):
```
{ "success": false, "message": "Data tidak ditemukan." }
```

---

**POST /api/ai/sync**
- Purpose: analyst/admin triggered AI sync (stubbed endpoint)
- Auth: `auth:sanctum` + role `analyst,admin,superadmin`

Response (202):
```
{ "message": "Syncing with AI database..." }
```

---

**GET /api/audit-logs**
- Purpose: fetch audit logs (admin)
- Auth: `auth:sanctum` + role `admin,superadmin`

Response (200): paginated `ActivityLog` records.
