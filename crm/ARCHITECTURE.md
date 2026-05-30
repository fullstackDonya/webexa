# 🏗️ Architecture - Email Integration OAuth2

## 📊 Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                         CRM Application                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐      ┌──────────────────┐                │
│  │  email-settings  │      │   OAuth Flow     │                │
│  │     .php         │◄────►│  connect.php     │                │
│  │                  │      │  callback.php    │                │
│  │  - Boutons OAuth │      │                  │                │
│  │  - Liste comptes │      │  Google/Microsoft│                │
│  │  - Sync manuelle │      └──────────────────┘                │
│  └──────────────────┘                │                          │
│           │                           │                          │
│           │                           ▼                          │
│           │              ┌─────────────────────┐                │
│           │              │   EmailCrypto.php   │                │
│           │              │  AES-256-CBC+HMAC   │                │
│           │              └─────────────────────┘                │
│           │                           │                          │
│           ▼                           ▼                          │
│  ┌──────────────────────────────────────────┐                  │
│  │         email_configurations             │                  │
│  │  ┌────────────────────────────────────┐  │                  │
│  │  │ - oauth_access_token (encrypted)   │  │                  │
│  │  │ - oauth_refresh_token (encrypted)  │  │                  │
│  │  │ - oauth_token_expires_at           │  │                  │
│  │  │ - last_error, error_count          │  │                  │
│  │  └────────────────────────────────────┘  │                  │
│  └──────────────────────────────────────────┘                  │
│                      │                                           │
└──────────────────────┼───────────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────┐
         │   Redis Queue (ZSET)    │
         │                         │
         │  Priority + Timestamp   │
         │  Retry + Backoff        │
         │  Dead Letter Queue      │
         └─────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────┐
         │  Worker Pool (PHP CLI)  │
         │  ┌──────────────────┐   │
         │  │ worker-1.php     │   │
         │  │ worker-2.php     │   │
         │  └──────────────────┘   │
         │                         │
         │  Managed by Supervisord │
         └─────────────────────────┘
                       │
           ┌───────────┴───────────┐
           │                       │
           ▼                       ▼
   ┌──────────────┐       ┌──────────────┐
   │ Gmail API    │       │ Graph API    │
   │ (OAuth)      │       │ (OAuth)      │
   └──────────────┘       └──────────────┘
           │                       │
           └───────────┬───────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  emails table   │
              │                 │
              │  - from/to      │
              │  - subject/body │
              │  - config_id    │
              └─────────────────┘
```

---

## 🔄 Flux OAuth2 (Gmail)

```
┌──────────┐              ┌──────────────┐              ┌─────────────┐
│  User    │              │  CRM Server  │              │   Google    │
└──────────┘              └──────────────┘              └─────────────┘
     │                            │                            │
     │ 1. Clic "Connecter Gmail"  │                            │
     ├───────────────────────────►│                            │
     │                            │                            │
     │                            │ 2. GET /connect.php        │
     │                            ├───────────────────────────►│
     │                            │    client_id, redirect_uri │
     │                            │    scopes, state (CSRF)    │
     │                            │                            │
     │  3. Redirect vers Google   │                            │
     │◄───────────────────────────┤                            │
     │                            │                            │
     │ 4. Consent Screen          │                            │
     ├────────────────────────────┼───────────────────────────►│
     │    (User authorizes)       │                            │
     │                            │                            │
     │ 5. Callback with code      │                            │
     │◄───────────────────────────┼────────────────────────────┤
     │                            │                            │
     │ 6. Redirect /callback.php  │                            │
     ├───────────────────────────►│                            │
     │    ?code=xxx&state=yyy     │                            │
     │                            │                            │
     │                            │ 7. Exchange code for tokens│
     │                            ├───────────────────────────►│
     │                            │    (POST /token)           │
     │                            │                            │
     │                            │ 8. access_token +          │
     │                            │    refresh_token           │
     │                            │◄───────────────────────────┤
     │                            │                            │
     │                            │ 9. Encrypt tokens (AES)    │
     │                            │    Store in DB             │
     │                            │                            │
     │ 10. Redirect to settings   │                            │
     │     Success message        │                            │
     │◄───────────────────────────┤                            │
     │                            │                            │
```

---

## 🔄 Flux Synchronisation Emails

```
┌─────────────┐         ┌────────────┐         ┌────────────┐
│   Trigger   │         │   Queue    │         │   Worker   │
└─────────────┘         └────────────┘         └────────────┘
      │                       │                       │
      │ 1. User clic "Sync"   │                       │
      │   ou CRON             │                       │
      ├──────────────────────►│                       │
      │   enqueue(config_id)  │                       │
      │                       │                       │
      │                       │ 2. dequeue()          │
      │                       │◄──────────────────────┤
      │                       │   (priority order)    │
      │                       │                       │
      │                       │ 3. Job data           │
      │                       ├──────────────────────►│
      │                       │   {config_id: 123}    │
      │                       │                       │
      │                       │                       │ 4. Fetch config
      │                       │                       │    from DB
      │                       │                       │
      │                       │                       │ 5. Check token
      │                       │                       │    expiry
      │                       │                       │
      │                       │                       │ 6. Refresh if
      │                       │                       │    needed
      │                       │                       │
      │                       │                       │ 7. Call API
      │                       │                       │    (Gmail/Graph)
      │                       │                       │
      │                       │                       │ 8. Parse emails
      │                       │                       │
      │                       │                       │ 9. Insert DB
      │                       │                       │
      │                       │ 10. complete(id)      │
      │                       │◄──────────────────────┤
      │                       │    or fail(id, error) │
      │                       │                       │
```

---

## 🔐 Chiffrement des Tokens

```
┌──────────────────────────────────────────────────────────┐
│                     Token Storage                        │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Plaintext Token:  "ya29.a0AfH6SMB..."                  │
│         │                                                │
│         ▼                                                │
│  ┌────────────────────────┐                             │
│  │  EmailCrypto::encrypt  │                             │
│  │                        │                             │
│  │  1. Generate random IV │                             │
│  │  2. AES-256-CBC        │                             │
│  │  3. HMAC-SHA256        │                             │
│  └────────────────────────┘                             │
│         │                                                │
│         ▼                                                │
│  Encrypted: [IV][HMAC][Ciphertext] → base64             │
│         │                                                │
│         ▼                                                │
│  ┌────────────────────────┐                             │
│  │  Store in MySQL        │                             │
│  │  oauth_access_token    │                             │
│  │  (LONGTEXT)            │                             │
│  └────────────────────────┘                             │
│                                                          │
│  Master Key (MAIL_CRYPTO_KEY):                          │
│  - 256-bit (32 bytes)                                   │
│  - Base64 encoded                                       │
│  - Stored in .env                                       │
│  - NEVER in code or DB                                  │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 🎯 Architecture Multi-tenant

```
┌──────────────────────────────────────────────────────────┐
│                   Customer Isolation                     │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Customer A (customer_id = 1)                           │
│  ┌─────────────────────────────────────────┐            │
│  │  - Gmail: alice@company-a.com           │            │
│  │  - Outlook: bob@company-a.com           │            │
│  │  - IMAP: info@company-a.com             │            │
│  └─────────────────────────────────────────┘            │
│                      │                                   │
│                      ▼                                   │
│         WHERE customer_id = 1 (ACL)                     │
│                                                          │
│  Customer B (customer_id = 2)                           │
│  ┌─────────────────────────────────────────┐            │
│  │  - Gmail: john@company-b.com            │            │
│  └─────────────────────────────────────────┘            │
│                      │                                   │
│                      ▼                                   │
│         WHERE customer_id = 2 (ACL)                     │
│                                                          │
│  ⚠️  CRITICAL: Toujours filtrer par customer_id         │
│      dans toutes les requêtes SQL                       │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## ⚙️ Worker Architecture

```
┌──────────────────────────────────────────────────────────┐
│                    Supervisord                           │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────────────────────────────────────────┐        │
│  │  email-sync-worker_01                       │        │
│  │  ├─ PID: 12345                              │        │
│  │  ├─ Status: RUNNING                         │        │
│  │  ├─ Uptime: 2h 34m                          │        │
│  │  └─ Auto-restart: yes                       │        │
│  └─────────────────────────────────────────────┘        │
│                                                          │
│  ┌─────────────────────────────────────────────┐        │
│  │  email-sync-worker_02                       │        │
│  │  ├─ PID: 12346                              │        │
│  │  ├─ Status: RUNNING                         │        │
│  │  ├─ Uptime: 2h 34m                          │        │
│  │  └─ Auto-restart: yes                       │        │
│  └─────────────────────────────────────────────┘        │
│                                                          │
│  Logs: /path/to/crm/logs/worker.log                    │
│  Rotation: 10MB max                                     │
│  Graceful shutdown: SIGTERM (30s timeout)               │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 🚦 Rate Limiting

```
┌──────────────────────────────────────────────────────────┐
│                   Rate Limit Strategy                    │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Gmail API (per user):                                  │
│  ├─ 250 quota units/second                              │
│  ├─ 1 billion/day                                       │
│  └─ Implementation: sleep() between requests            │
│                                                          │
│  Microsoft Graph (per app):                             │
│  ├─ Varies by endpoint                                  │
│  ├─ Throttling: HTTP 429                                │
│  └─ Implementation: Retry-After header                  │
│                                                          │
│  IMAP (per account):                                    │
│  ├─ Max 1 connection simultaneous                       │
│  ├─ Provider-specific limits                            │
│  └─ Implementation: Connection pool                     │
│                                                          │
│  Backoff Strategy (on error):                           │
│  ├─ Attempt 1: Immediate                                │
│  ├─ Attempt 2: +1 min                                   │
│  ├─ Attempt 3: +5 min                                   │
│  └─ Attempt 4+: Dead letter queue                       │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 📁 Structure des Fichiers

```
crm/
├── config/
│   ├── database.php
│   └── supervisord.conf         # Workers config
│
├── database/
│   ├── email_tables.sql
│   └── migrations/
│       └── 001_add_oauth_support.sql
│
├── includes/
│   ├── EmailCrypto.php          # Chiffrement AES-256
│   ├── GoogleOAuth.php          # OAuth Google
│   ├── MicrosoftOAuth.php       # OAuth Microsoft
│   ├── EmailSyncQueue.php       # Redis queue
│   └── env.php                  # Loader .env
│
├── oauth/
│   ├── google/
│   │   ├── connect.php          # Initiate flow
│   │   └── callback.php         # Handle callback
│   └── microsoft/
│       ├── connect.php
│       └── callback.php
│
├── workers/
│   └── email-sync-worker.php    # Background worker
│
├── scripts/
│   └── generate-key.php         # Key generator
│
├── logs/
│   ├── worker.log
│   └── email.log
│
├── .env.example                 # Template config
├── .gitignore                   # Security
├── email-settings.php           # UI principale
├── install-email-oauth.sh       # Installation
│
└── docs/
    ├── QUICK_START.md
    ├── EMAIL_OAUTH_SETUP.md
    ├── EMAIL_TESTS_CHECKLIST.md
    └── LIVRABLE_EMAIL_OAUTH.md
```

---

## 🔄 Lifecycle d'un Token OAuth

```
┌─────────────────────────────────────────────────────────┐
│                Token Lifecycle                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Authorization Flow                                 │
│     ├─ User grants permissions                         │
│     └─ Receive: access_token + refresh_token           │
│                                                         │
│  2. Storage                                            │
│     ├─ Encrypt with AES-256-CBC                        │
│     ├─ Store in email_configurations                   │
│     └─ Set expires_at = NOW() + 3600s                  │
│                                                         │
│  3. Usage                                              │
│     ├─ Worker needs token                              │
│     ├─ Check: expires_at > NOW() + 5min?               │
│     ├─ Yes: Decrypt and use                            │
│     └─ No: Go to step 4                                │
│                                                         │
│  4. Refresh                                            │
│     ├─ Use refresh_token to get new access_token       │
│     ├─ Update DB with new encrypted token              │
│     ├─ Update expires_at                               │
│     └─ Clear last_error, error_count                   │
│                                                         │
│  5. Error Handling                                     │
│     ├─ Invalid grant: User revoked                     │
│     │   └─ Set last_error, increment error_count       │
│     │   └─ UI shows "Reconnecter" button               │
│     ├─ Network error: Temporary                        │
│     │   └─ Retry with backoff                          │
│     └─ Other: Log and alert                            │
│                                                         │
│  6. Revocation (user initiated)                        │
│     ├─ Call revoke endpoint (Google)                   │
│     ├─ Set is_active = 0                               │
│     └─ Clear tokens from DB                            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 Design Patterns Utilisés

| Pattern | Usage | Bénéfice |
|---------|-------|----------|
| **Strategy** | Provider-specific sync (Gmail/Outlook/IMAP) | Extensibilité |
| **Factory** | OAuth provider instantiation | Découplage |
| **Queue** | Redis priority queue | Async processing |
| **Retry** | Exponential backoff | Resilience |
| **Repository** | DB access via PDO | Testabilité |
| **Singleton** | Redis connection | Performance |
| **Observer** | (Future) Webhooks | Real-time updates |

---

## 🔮 Roadmap / Extensions Possibles

1. **Webhooks Push** (Phase 2)
   - Gmail Pub/Sub notifications
   - Microsoft Graph change notifications
   - Éliminer polling → push uniquement

2. **AI Email Classification** (Phase 3)
   - Auto-extract leads depuis emails
   - Sentiment analysis
   - Priority scoring

3. **Send Emails** (Phase 4)
   - Templates
   - Campaigns
   - Tracking (opens, clicks)

4. **Advanced Features**
   - Attachments sync
   - Thread/conversation tracking
   - Search full-text
   - Labels/folders sync

---

**Architecture prête pour scale ! 🚀**
