# 🎨 VISUAL ARCHITECTURE GUIDE - Sentinel Backend

Panduan visual lengkap untuk memahami arsitektur, deployment, dan operasional sistem.

---

## 📊 RINGKASAN DIAGRAM

Kami telah membuat **9 diagram komprehensif** untuk membantu Anda memahami sistem:

| #   | Diagram               | Fokus                        | Pengguna          |
| --- | --------------------- | ---------------------------- | ----------------- |
| 1️⃣  | Deployment Flow       | Proses push ke production    | DevOps, Developer |
| 2️⃣  | System Architecture   | Struktur container & layanan | Architect, DevOps |
| 3️⃣  | Request Flow          | Alur request HTTP            | Developer, QA     |
| 4️⃣  | Crawler Pipeline      | Data ingestion workflow      | Data Engineer     |
| 5️⃣  | Observer Pattern      | Database integrity           | Developer         |
| 6️⃣  | Security & Middleware | Authentikasi & validasi      | Security, Backend |
| 7️⃣  | Maintenance Schedule  | Operasional harian/mingguan  | DevOps, Ops       |
| 8️⃣  | Rollback Procedures   | Emergency recovery           | DevOps, SRE       |
| 9️⃣  | Data Query Flow       | Database operation           | DBA, Developer    |

---

## 🔍 PENJELASAN SINGKAT SETIAP DIAGRAM

### 1️⃣ DEPLOYMENT FLOW

**Kapan menggunakan:** Sebelum deploy ke production
**Yang ditunjukkan:**

- Pull dari Git
- Setup konfigurasi
- Pembedaan: Local vs Production
- Testing dan verification
- Monitoring pasca-deployment

**Aksi Penting:**

```bash
git clone → cp .env.example .env → docker-compose up -d →
php artisan migrate --force → Health check ✅
```

---

### 2️⃣ SYSTEM ARCHITECTURE

**Kapan menggunakan:** Memahami struktur sistem keseluruhan
**Komponen:**

```
┌─ External Services (GitHub, X API, Let's Encrypt)
│
├─ Jagoan Hosting Server
│  ├─ Web Container
│  │  ├─ Nginx (reverse proxy)
│  │  ├─ PHP-FPM (runtime)
│  │  ├─ Supervisor (process manager)
│  │  ├─ Laravel App
│  │  ├─ Queue Worker
│  │  └─ Task Scheduler
│  │
│  └─ Database Container
│     └─ PostgreSQL (data persistence)
│
├─ Storage & Backup
│  ├─ Logs
│  ├─ Backups
│  └─ Files
│
└─ Security Layer
   ├─ SSL Certificates
   ├─ Security Headers
   └─ API Key Auth
```

**Interaksi:**

- GitHub → Pull code ke App
- X API → Data via Queue Worker
- PostgreSQL ← App queries/writes
- Supervisor ← Manage all processes
- SSL ← Certbot auto-renew

---

### 3️⃣ REQUEST FLOW

**Kapan menggunakan:** Debug HTTP requests
**Alur Client → Server → Database:**

```
Client Browser
    ↓
HTTPS/TLS (Port 443)
    ↓
Nginx Reverse Proxy
    ├─ Verify SSL ✅
    ├─ Add Security Headers
    └─ Route ke PHP-FPM
    ↓
PHP-FPM
    ├─ Middleware Chain
    │  ├─ API Key Auth ✅
    │  ├─ CORS ✅
    │  └─ Rate Limit ✅
    │
    ├─ Controller
    │  ├─ Validate Input ✅
    │  ├─ Process Logic
    │  └─ Query Database
    │
    └─ Generate Response (JSON)
    ↓
Back to Browser (HTTPS)
```

---

### 4️⃣ CRAWLER PIPELINE

**Kapan menggunakan:** Debugging data ingestion
**Proses:**

```
php artisan crawl:x
    ↓
CrawlXPosts Command
    ↓
Fetch X/Twitter API
    ↓
Parse JSON Response
    ↓
Loop Tweets
    ├─ Extract data
    ├─ Check duplicate
    └─ Save to DB (with Observer hash)
    ↓
Dispatch CrawlXReplies Job
    ↓
Queue Worker
    ├─ Fetch replies
    ├─ Parse JSON
    ├─ Save to DB
    └─ Chain hash for audit
    ↓
✅ Dashboard ready
```

---

### 5️⃣ DATABASE OBSERVER PATTERN

**Kapan menggunakan:** Memahami blockchain hashing
**Mekanisme:**

```
Insert/Update Event
    ↓
Trigger Observer
    ├─ CrawledDataObserver
    │  └─ Create SHA-256 hash (blockchain)
    │
    └─ ActivityLogObserver
       └─ Create audit trail hash
    ↓
Save dengan hash chain:
[previous_hash] → [data] → [current_hash]
    ↓
Immutable record ✅
```

---

### 6️⃣ SECURITY & MIDDLEWARE

**Kapan menggunakan:** Memahami security layers
**Lapisan Keamanan:**

```
1. SSL/TLS Verification
   ↓ ❌ Invalid cert → Reject
   ↓ ✅ Valid cert → Continue

2. Security Headers
   - X-Frame-Options: DENY
   - X-Content-Type-Options: nosniff
   - HSTS: 31536000 seconds
   - CSP headers

3. API Key Middleware
   - Extract header: x-api-key
   - Validate IP whitelist
   - hash_equals() comparison

4. Role Middleware
   - Check user permissions
   - Enforce RBAC

5. Input Validation
   - Date format: Y-m-d
   - URL regex: twitter.com
   - Type enum: post|reply

✅ Request allowed → Process
❌ Failed any check → Reject
```

---

### 7️⃣ MAINTENANCE SCHEDULE

**Kapan menggunakan:** Operasional rutin
**Jadwal:**

```
⏰ Hourly
   └─ Health check (curl /up)
   └─ Resource monitoring

📆 Daily
   ├─ Review error logs
   ├─ Rotate old logs
   └─ Verify backups

📅 Weekly
   ├─ Update dependencies
   ├─ Run tests
   ├─ Optimize cache
   └─ Clear Redis

📊 Monthly
   ├─ Full database maintenance
   ├─ Upgrade base image
   ├─ Security audit
   └─ Full backup
```

---

### 8️⃣ ROLLBACK PROCEDURES

**Kapan menggunakan:** Emergency recovery
**Skenario & Solusi:**

```
Issue detected?
    ↓
├─ Code problem
│  └─ git revert HASH
│     → docker-compose restart
│
├─ Database problem
│  └─ Restore from backup
│     → Verify data integrity
│
└─ Configuration problem
   └─ Edit .env
      → docker-compose restart

→ Health check (curl /up)
  ├─ ✅ OK? → Document & fix root cause
  └─ ❌ Fail? → Try different rollback
```

---

### 9️⃣ DATA QUERY FLOW

**Kapan menggunakan:** Optimization & debugging
**Proses Query:**

```
1. Client Request
   GET /api/data?search=polda&limit=50

2. Route Matching
   → DataController@index

3. Middleware Check
   ✅ API Key valid
   ✅ User permission

4. Input Validation
   ✅ search is string
   ✅ limit is integer
   ✅ dates in Y-m-d format

5. Build Query
   SELECT ... FROM crawled_data
   WHERE content ILIKE '%polda%'
   ORDER BY posted_at DESC
   LIMIT 50

6. Database Execution
   - Index lookup on posted_at
   - Full-text search on content
   - Sort & paginate

7. Format Response
   {
     "data": [...],
     "pagination": {...}
   }
```

---

## 💡 CARA MENGGUNAKAN FLOWCHART INI

### Untuk Developer:

1. Baca **Diagram 1** untuk memahami deployment
2. Baca **Diagram 3** untuk REST API
3. Baca **Diagram 4** untuk data processing
4. Baca **Diagram 6** untuk security checks

### Untuk DevOps/SRE:

1. Baca **Diagram 2** untuk architecture overview
2. Baca **Diagram 7** untuk maintenance schedule
3. Baca **Diagram 8** untuk emergency procedures
4. Baca **DEPLOYMENT.md** untuk step-by-step

### Untuk Database Admin:

1. Baca **Diagram 5** untuk integrity mechanism
2. Baca **Diagram 9** untuk query optimization
3. Baca **DEPLOYMENT.md** section "Backup & Restore"

### Untuk Security Engineer:

1. Baca **Diagram 2** untuk architecture
2. Baca **Diagram 6** untuk middleware checks
3. Baca **FIX_SUMMARY.md** section "Security Improvements"
4. Baca **DEPLOYMENT.md** section "Security Checklist"

---

## 🎯 TROUBLESHOOTING MENGGUNAKAN FLOWCHART

### Problem: API returns 401

→ Ikuti **Diagram 6: Security & Middleware**

- Check API key in request header
- Verify .env SENTINEL_API_KEY
- Test with: `curl -H "x-api-key: KEY" https://domain/api/data`

### Problem: Data tidak tersimpan

→ Ikuti **Diagram 4: Crawler Pipeline**

- Check crawler logs: `docker-compose logs web`
- Verify database connection
- Check observer hashing

### Problem: Server crash

→ Ikuti **Diagram 8: Rollback Procedures**

- Check service status: `docker-compose ps`
- View logs: `docker-compose logs -f web`
- Rollback previous commit if needed

### Problem: Slow queries

→ Ikuti **Diagram 9: Data Query Flow**

- Check database indexes
- Monitor query performance
- Review query logs

---

## 📚 FILE REFERENCES

Semua flowchart tersedia dalam format Mermaid di:
**`FLOWCHART.md`**

Untuk render sebagai gambar:

1. Copy Mermaid syntax
2. Paste di: https://mermaid.live
3. Export sebagai PNG/SVG

Atau gunakan VS Code extension:

- Install "Markdown Preview Mermaid Support"
- Preview langsung di editor

---

## 🔗 DIAGRAM RELATIONSHIPS

```
FLOWCHART.md (9 Diagrams)
    ├─ 1: Deployment Flow
    │  └─ References DEPLOYMENT.md
    │
    ├─ 2: System Architecture
    │  ├─ Uses: Dockerfile, docker-compose.yml
    │  └─ Manages: FIX_SUMMARY.md components
    │
    ├─ 3: Request Flow
    │  ├─ Implements: bootstrap/app.php middleware
    │  └─ Handles: Security, CORS, Rate Limit
    │
    ├─ 4: Crawler Pipeline
    │  ├─ Uses: CrawlXPosts, CrawlXReplies
    │  └─ Stores: CrawledData model
    │
    ├─ 5: Observer Pattern
    │  ├─ Observes: CrawledData, ActivityLog
    │  └─ In: app/Observers/
    │
    ├─ 6: Security & Middleware
    │  ├─ Uses: CheckApiKey, RoleMiddleware, SecureHeaders
    │  └─ Validates: Input in DataController
    │
    ├─ 7: Maintenance Schedule
    │  └─ References: DEPLOYMENT.md (Maintenance section)
    │
    ├─ 8: Rollback Procedures
    │  └─ References: DEPLOYMENT.md (Rollback section)
    │
    └─ 9: Data Query Flow
       ├─ Uses: DataController@index
       └─ Queries: PostgreSQL database
```

---

## ✨ SUMMARY

Dengan 9 diagram ini, Anda memiliki complete visual documentation:

- ✅ Deployment workflows
- ✅ System architecture
- ✅ Data flows
- ✅ Security layers
- ✅ Operational procedures
- ✅ Emergency recovery
- ✅ Performance optimization

**Semua file tersedia di root project directory untuk referensi cepat.**

---

**Generated**: 2024-05-10  
**Format**: Mermaid (text-based, version control friendly)  
**Status**: Production Ready ✅
