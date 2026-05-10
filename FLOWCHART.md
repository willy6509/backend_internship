# 📊 FLOWCHART & ARCHITECTURE DIAGRAMS - Sentinel Backend

## 1. 🚀 DEPLOYMENT PROCESS FLOW

```mermaid
flowchart TD
    A["🌐 Start: Pull dari Git"] --> B["📋 Clone Repository"]
    B --> C["⚙️ Copy .env.example → .env"]
    C --> D["🔐 Edit .env dengan Credentials"]
    D --> E{"Environment<br/>Type?"}

    E -->|Local Development| F["🏠 Local Setup"]
    E -->|Production| G["🏭 Production Setup"]

    F --> F1["composer install"]
    F1 --> F2["php artisan key:generate"]
    F2 --> F3["php artisan migrate"]
    F3 --> F4["docker-compose up -d"]
    F4 --> F5["✅ Local Ready"]

    G --> G1["composer install --no-dev"]
    G1 --> G2["docker-compose up -d"]
    G2 --> G3["php artisan migrate --force"]
    G3 --> G4["Setup Nginx Reverse Proxy"]
    G4 --> G5["Setup SSL Certificate"]
    G5 --> G6["✅ Production Ready"]

    F5 --> H["🧪 Testing"]
    G6 --> H

    H --> I{"Health<br/>Check<br/>OK?"}
    I -->|No| J["🐛 Debug & Fix"]
    J --> K["Check Logs: docker-compose logs web"]
    K --> L["Fix Issues"]
    L --> H

    I -->|Yes| M["✨ Deployment Success"]
    M --> N["📊 Monitor & Maintain"]
    N --> O["⏰ Daily: Check logs"]
    N --> P["📅 Weekly: Update & optimize"]
    N --> Q["🔄 Monthly: Backup & cleanup"]
```

---

## 2. 🏗️ SYSTEM ARCHITECTURE DIAGRAM

```mermaid
graph TB
    subgraph "🌍 External Services"
        GitHub["GitHub Repository"]
        TwitterAPI["X/Twitter API"]
        Certbot["Let's Encrypt"]
    end

    subgraph "🖥️ Jagoan Hosting Server"
        subgraph "🐳 Docker Containers"
            subgraph "Web Container"
                Nginx["⚙️ Nginx<br/>Port 80/443"]
                PHP["🐘 PHP-FPM<br/>8.3 Alpine"]
                Supervisor["👁️ Supervisor<br/>Process Manager"]
                App["📦 Laravel App<br/>Sentinel"]
                Queue["📬 Queue Worker"]
                Scheduler["⏲️ Task Scheduler"]
            end

            subgraph "Database Container"
                Postgres["🐘 PostgreSQL<br/>Port 5432"]
                DB[("📊 Database<br/>sentinel_db")]
            end
        end
    end

    subgraph "💾 Storage & Backup"
        Logs["📝 Logs<br/>storage/logs"]
        Backup["💿 Backups<br/>sentinel_db.sql.gz"]
        Files["📁 Application Files<br/>storage/"]
    end

    subgraph "🔐 Security"
        SSL["🔒 SSL Certificate"]
        SecureHeaders["🛡️ Security Headers"]
        APIKey["🔑 API Key Auth"]
    end

    GitHub -->|git clone| App
    TwitterAPI -->|crawl:x command| Queue
    Queue -->|store data| Postgres

    Nginx -->|reverse proxy| PHP
    PHP -->|query| Postgres
    PHP -->|run jobs| Queue
    PHP -->|schedule tasks| Scheduler

    Supervisor -->|manage| Nginx
    Supervisor -->|manage| PHP
    Supervisor -->|manage| Queue
    Supervisor -->|manage| Scheduler

    Postgres --> DB
    App -->|read/write| DB

    App -->|write| Logs
    PHP -->|apply| SecureHeaders
    PHP -->|verify| APIKey

    Certbot -->|renew| SSL
    Nginx -->|use| SSL

    App -->|backup| Backup
    App -->|store| Files

    style Nginx fill:#ff6b6b
    style PHP fill:#4ecdc4
    style Postgres fill:#45b7d1
    style App fill:#96ceb4
    style Queue fill:#ffeaa7
    style Scheduler fill:#dfe6e9
```

---

## 3. 📡 REQUEST FLOW DIAGRAM

```mermaid
flowchart LR
    subgraph Client["🖥️ Client"]
        Browser["Web Browser<br/>or API Client"]
    end

    subgraph Internet["🌐 Internet"]
        HTTPS["HTTPS/TLS<br/>Port 443"]
    end

    subgraph Hosting["🏭 Jagoan Hosting"]
        subgraph Nginx["Nginx<br/>Reverse Proxy"]
            Cert["✅ Verify<br/>SSL Cert"]
            Headers["📝 Add<br/>Security Headers"]
            Routing["🔀 Route<br/>Request"]
        end

        subgraph PHP_FPM["PHP-FPM"]
            Middleware["🔐 Middleware<br/>- API Key Auth<br/>- CORS<br/>- Rate Limit"]
            Controller["🎮 Controller<br/>- Validate Input<br/>- Process Logic<br/>- Generate Response"]
        end

        subgraph Database["PostgreSQL"]
            Query["🔍 Query<br/>Data"]
            Store["💾 Store<br/>Data"]
        end
    end

    subgraph Cache["⚡ Cache Layer"]
        Redis["Redis<br/>Optional"]
    end

    Browser -->|HTTPS Request| HTTPS
    HTTPS -->|Encrypted| Cert
    Cert -->|Pass| Headers
    Headers -->|Forward| Routing

    Routing -->|GET /api/data| Middleware
    Middleware -->|Validate<br/>Authenticate| Controller

    Controller -->|SELECT| Query
    Query -->|Return Data| Controller

    Controller -->|INSERT/UPDATE| Store
    Store -->|Confirm| Controller

    Controller -->|JSON Response| Nginx
    Nginx -->|HTTPS<br/>Headers<br/>Body| Browser

    Controller -->|Check Cache| Redis
    Redis -->|Cache Hit?| Controller

    style Cert fill:#4ecdc4
    style Middleware fill:#ff6b6b
    style Controller fill:#96ceb4
    style Query fill:#45b7d1
    style Store fill:#45b7d1
    style Headers fill:#ffd93d
```

---

## 4. 🔄 CRAWLER & DATA INGESTION FLOW

```mermaid
flowchart TD
    A["⏰ Manual Trigger<br/>php artisan crawl:x<br/>or Scheduled Task"]

    B["🔗 CrawlXPosts Command"]
    B --> C["🌐 Fetch X/Twitter API<br/>Search: 'polda jateng'"]

    C --> D{"Response<br/>Success?"}
    D -->|No| E["❌ Log Error<br/>HTTP Status"]
    D -->|Yes| F["📄 Parse JSON<br/>GraphQL Response"]

    F --> G["🔄 Loop Through Tweets"]

    G --> H["✅ Extract Tweet Data<br/>- URL<br/>- Username<br/>- Content<br/>- Timestamp"]

    H --> I["🔍 Check Duplicate<br/>FirstOrCreate"]

    I --> J{"Already<br/>Exists?"}
    J -->|Yes| K["⏩ Skip Tweet"]
    J -->|No| L["💾 Save to Database"]

    L --> M["🔗 CrawledDataObserver<br/>Generate Blockchain Hash"]
    M --> N["🔐 Chain Hash<br/>previous_hash | data | current_hash"]

    N --> O["📤 Dispatch CrawlXReplies Job<br/>Queue Worker"]
    O --> P["⏱️ Random Delay 2-5s<br/>Avoid API Ban"]

    P --> Q["🎯 CrawlXReplies Job<br/>Fetch Reply Data"]
    Q --> R["📋 Parse Replies JSON<br/>Extract Reply Tweets"]

    R --> S["💾 Save Replies<br/>CrawledData + parent_url"]
    S --> T["🔗 Observer Hash Chain<br/>For Replies"]

    T --> U["✨ Ingestion Complete"]
    U --> V["📊 Dashboard Ready<br/>Data Available for Analysis"]

    E --> W["🚨 Alert & Log<br/>Check Error Logs"]
    W --> X["🔧 Manual Review<br/>Fix & Retry"]

    style A fill:#ffeaa7
    style C fill:#fab1a0
    style F fill:#74b9ff
    style L fill:#00b894
    style M fill:#6c5ce7
    style N fill:#a29bfe
    style O fill:#fd79a8
    style V fill:#00cec9
```

---

## 5. 🗄️ DATABASE & OBSERVER PATTERN

```mermaid
flowchart TD
    A["📥 Insert/Update Event<br/>CrawledData or ActivityLog"]

    B["🔔 Observer Triggered"]

    B --> C{"Which<br/>Model?"}

    C -->|CrawledData| D["CrawledDataObserver"]
    C -->|ActivityLog| E["ActivityLogObserver"]

    D --> D1["creating() Event"]
    D1 --> D2["🔍 Get Previous Hash"]
    D2 --> D3["Set previous_hash<br/>Chain Link"]
    D3 --> D4["📦 Prepare Data<br/>previous_hash | user_id | data"]
    D4 --> D5["🔐 Hash SHA-256<br/>current_hash"]
    D5 --> D6["💾 Save with Hash<br/>Blockchain Integrity"]

    E --> E1["creating() Event"]
    E1 --> E2["🔍 Get Last ActivityLog"]
    E2 --> E3["Set previous_hash<br/>Audit Trail Chain"]
    E3 --> E4["📦 Prepare Data<br/>user_id | event | properties"]
    E4 --> E5["🔐 Hash SHA-256"]
    E5 --> E6["💾 Save Audit Log<br/>Immutable Record"]

    D6 --> D7["✅ CrawledData Saved<br/>url, content, hash"]
    E6 --> E7["✅ ActivityLog Saved<br/>user actions, hash"]

    D7 --> F["📊 Available for Query<br/>Dashboard Access"]
    E7 --> G["🔍 Available for Audit<br/>Security Review"]

    style D1 fill:#6c5ce7
    style E1 fill:#6c5ce7
    style D5 fill:#a29bfe
    style E5 fill:#a29bfe
    style D6 fill:#00b894
    style E6 fill:#00b894
```

---

## 6. 🔐 SECURITY & MIDDLEWARE FLOW

```mermaid
flowchart LR
    A["📩 Incoming Request"]

    A --> B["🔒 SSL/TLS Verify"]
    B --> C["✅ Certificate Valid?"]
    C -->|No| C1["❌ Reject"]
    C -->|Yes| D["📝 SecureHeaders<br/>Add Security Headers"]

    D --> E["🛡️ Headers Set<br/>X-Frame-Options: DENY<br/>X-Content-Type-Options: nosniff<br/>HSTS: max-age=31536000<br/>CSP Headers"]

    E --> F["🔐 CheckApiKey Middleware"]
    F --> G["🔑 Extract API Key<br/>from Header: x-api-key"]

    G --> H["🔍 Validate<br/>IP Whitelist?<br/>CIDR Match?"]
    H -->|IP Not Allowed| I["❌ 403 Forbidden"]

    H -->|IP OK| J["🔑 Compare Key<br/>hash_equals"]
    J -->|Wrong Key| K["❌ 401 Unauthorized"]
    J -->|Correct Key| L["✅ Authenticate"]

    L --> M["👤 RoleMiddleware<br/>Check User Role"]
    M --> N{"User Has<br/>Permission?"}
    N -->|No| O["❌ 403 Access Denied"]
    N -->|Yes| P["✅ Allow Request"]

    P --> Q["🎮 Controller<br/>Process Request"]

    Q --> R["✔️ Validate Input<br/>- Date Format: Y-m-d<br/>- URL Regex: x.com|twitter.com<br/>- Type: post|reply"]

    R --> S{"Valid<br/>Data?"}
    S -->|No| T["❌ 422 Unprocessable<br/>Show Error Messages"]
    S -->|Yes| U["💾 Save to Database<br/>Observer Auto-Hash"]

    U --> V["✅ 200/201 Response<br/>JSON Data"]

    C1 --> W["🔚 End: Reject"]
    I --> W
    K --> W
    O --> W
    T --> W
    V --> W

    style B fill:#ff6b6b
    style D fill:#fd79a8
    style F fill:#6c5ce7
    style M fill:#6c5ce7
    style R fill:#74b9ff
    style U fill:#00b894
    style V fill:#55efc4
```

---

## 7. 🧹 MAINTENANCE & MONITORING FLOW

```mermaid
flowchart TD
    A["📅 Time-based Tasks"]

    A --> B{"Frequency?"}

    B -->|⏰ Hourly| B1["📊 Health Check<br/>curl /up"]
    B1 --> B2["📈 Monitor Resources<br/>CPU, Memory, Disk"]

    B -->|📆 Daily| C1["🧹 Clean Old Logs<br/>Rotate Log Files"]
    C1 --> C2["🔍 Review Error Logs<br/>Check Issues"]
    C2 --> C3["💿 Verify Backups"]

    B -->|📅 Weekly| D1["🔄 Update Deps<br/>composer update --dry-run"]
    D1 --> D2["🧪 Run Tests<br/>phpunit"]
    D2 --> D3["⚡ Optimize<br/>php artisan optimize"]
    D3 --> D4["🧹 Cache Clear<br/>Clear Redis/File Cache"]

    B -->|📊 Monthly| E1["🔧 Full Maintenance<br/>Database Vacuum"]
    E1 --> E2["📦 Upgrade Base Image<br/>docker pull"]
    E2 --> E3["🔐 Security Audit<br/>composer audit"]
    E3 --> E4["💾 Full Backup<br/>Database + Files"]

    B2 --> F["📊 Metrics Dashboard"]
    C3 --> F
    D4 --> F
    E4 --> F

    F --> G{"Issues<br/>Found?"}

    G -->|No| H["✅ All OK<br/>Continue Normal Ops"]
    G -->|Yes| I["🚨 Alert Team"]

    I --> J["🐛 Start Debugging<br/>Check Logs<br/>Review Changes<br/>Check Errors"]

    J --> K{"Quick<br/>Fix?"}
    K -->|Yes| L["🔧 Apply Fix<br/>Restart Services"]
    K -->|No| M["🔄 Rollback<br/>Previous Version"]

    L --> N["✅ Monitor Fix"]
    M --> N
    N --> O{"Stable?"}

    O -->|Yes| P["✨ Resolved<br/>Document Issue"]
    O -->|No| Q["🆘 Escalate<br/>Get Help"]

    P --> H
    Q --> H

    style B1 fill:#ffeaa7
    style C1 fill:#74b9ff
    style D1 fill:#a29bfe
    style E1 fill:#fd79a8
    style F fill:#55efc4
    style I fill:#ff7675
    style J fill:#fab1a0
    style L fill:#00b894
    style P fill:#55efc4
```

---

## 8. 🔄 ROLLBACK & RECOVERY FLOW

```mermaid
flowchart TD
    A["🚨 Critical Issue Detected"]

    B{"Issue<br/>Type?"}

    B -->|Code Problem| C["🔄 Git Rollback"]
    C --> C1["git log --oneline"]
    C1 --> C2["git revert COMMIT_HASH"]
    C2 --> C3["git push origin main"]
    C3 --> C4["docker-compose down"]
    C4 --> C5["docker-compose up -d"]

    B -->|Database Problem| D["💾 Database Restore"]
    D --> D1["✅ Stop Services<br/>docker-compose down"]
    D1 --> D2["📦 Locate Backup<br/>sentinel_db_*.sql.gz"]
    D2 --> D3["⚡ Restore Data<br/>pg_restore < backup.sql.gz"]
    D3 --> D4["docker-compose up -d"]

    B -->|Configuration Problem| E["⚙️ Config Fix"]
    E --> E1["✏️ Edit .env"]
    E1 --> E2["docker-compose exec web<br/>php artisan config:cache"]
    E2 --> E3["docker-compose restart web"]

    C5 --> F["🧪 Verify Health"]
    D4 --> F
    E3 --> F

    F --> G["✅ Health Check<br/>curl https://yourdomain/up"]

    G --> H{"Status<br/>OK?"}
    H -->|No| I["🔁 Try Different Rollback"]
    I --> B

    H -->|Yes| J["✅ System Restored"]
    J --> K["📋 Document Issue<br/>Root Cause Analysis"]
    K --> L["🔧 Long-term Fix<br/>Update Code/Docs"]
    L --> M["✨ Deploy Fix"]

    style A fill:#ff7675
    style C1 fill:#fab1a0
    style D1 fill:#fab1a0
    style E1 fill:#fab1a0
    style G fill:#74b9ff
    style J fill:#00b894
```

---

## 9. 📊 DATA FLOW: API REQUEST TO DATABASE

```mermaid
flowchart TD
    A["1️⃣ Client Request<br/>GET /api/data<br/>?search=polda<br/>?limit=50"]

    B["2️⃣ Nginx<br/>Reverse Proxy"]
    B --> B1["Route to PHP-FPM<br/>Port 9000"]

    C["3️⃣ Laravel Router"]
    C --> C1["Match Route<br/>GET /api/data →<br/>DataController@index"]

    D["4️⃣ Middleware Pipeline"]
    D --> D1["CheckApiKey<br/>Validate x-api-key"]
    D1 --> D2["SecureHeaders<br/>Add Headers"]
    D2 --> D3["RoleMiddleware<br/>Check Permission"]

    E["5️⃣ DataController<br/>index()"]
    E --> E1["Extract Parameters<br/>search, limit,<br/>start_date, end_date"]

    F["6️⃣ Input Validation"]
    F --> F1["Validate<br/>search (string)<br/>limit (integer)<br/>dates (Y-m-d)"]

    G["7️⃣ Build Query"]
    G --> G1["SELECT id, type,<br/>source, username,<br/>posted_at, content"]
    G1 --> G2["WHERE content<br/>ILIKE '%search%'"]
    G2 --> G3["WHERE posted_at<br/>BETWEEN start AND end"]
    G3 --> G4["ORDER BY posted_at DESC"]

    H["8️⃣ Database Query"]
    H --> H1["PostgreSQL<br/>Execute Query"]
    H1 --> H2["Index Lookup<br/>B-tree on posted_at"]
    H2 --> H3["Filter Results<br/>Apply ILIKE"]
    H3 --> H4["Sort Results"]
    H4 --> H5["Paginate<br/>LIMIT 50 OFFSET 0"]

    I["9️⃣ Response Build"]
    I --> I1["Format JSON<br/>- data: array<br/>- pagination: meta"]
    I1 --> I2["Add Headers<br/>Content-Type: json<br/>Cache-Control"]

    J["🔟 Return to Client<br/>200 OK<br/>Content-Length<br/>JSON Body"]

    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
    G --> H
    H --> I
    I --> J

    style A fill:#ffeaa7
    style E fill:#96ceb4
    style F fill:#74b9ff
    style G fill:#a29bfe
    style H fill:#45b7d1
    style I fill:#00b894
    style J fill:#55efc4
```

---

## 🔑 LEGEND

| Emoji | Meaning             |
| ----- | ------------------- |
| 🚀    | Start/Launch        |
| ✅    | Success             |
| ❌    | Error/Failure       |
| 🔐    | Security            |
| 💾    | Database            |
| 🐳    | Docker              |
| ⏰    | Time/Scheduling     |
| 🔄    | Process/Loop        |
| 📊    | Monitoring          |
| 🧹    | Cleanup/Maintenance |
| 🐛    | Debug/Fix           |
