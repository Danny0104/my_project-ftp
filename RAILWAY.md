# Deploy Field Training Platform on Railway

Repository: `https://github.com/Danny0104/my_project-ftp`

This Yii2 Advanced app needs **three Railway services** (two required, one optional):

| Service | Dockerfile | Public URL use |
|---------|------------|----------------|
| **frontend** | `frontend/Dockerfile` | Students & organizations |
| **backend** | `backend/Dockerfile` | Platform admin panel |
| **mysql** | Railway MySQL plugin | Database (private) |
| **chat** (optional) | `realtime/chat-server/Dockerfile` | Socket.IO realtime messaging |

---

## Step 1 — Create Railway project

1. Go to [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo**
2. Select `Danny0104/my_project-ftp`
3. Add **MySQL** from the project canvas (**+ New** → **Database** → **MySQL**)

---

## Step 2 — Frontend service

1. **+ New** → **GitHub Repo** → same repository (or duplicate the first service)
2. Rename service to `frontend`
3. **Settings** → **Build**:
   - Builder: **Dockerfile**
   - Dockerfile path: `frontend/Dockerfile`
   - Config-as-code path: `railway.frontend.toml` (optional)
4. **Settings** → **Networking** → **Generate Domain** (e.g. `frontend-production-xxxx.up.railway.app`)

---

## Step 3 — Backend service

1. **+ New** → same repo again
2. Rename to `backend`
3. **Settings** → **Build**:
   - Dockerfile path: `backend/Dockerfile`
   - Config-as-code path: `railway.backend.toml`
4. **Generate Domain** for admin access

---

## Step 4 — Environment variables

Set these on **both frontend and backend** services (Railway → service → **Variables**).

### Database (auto from MySQL plugin)

Link the MySQL service to frontend/backend, or copy variables from the MySQL service:

| Variable | Source |
|----------|--------|
| `MYSQLHOST` | MySQL service |
| `MYSQLPORT` | MySQL service |
| `MYSQLUSER` | MySQL service |
| `MYSQLPASSWORD` | MySQL service |
| `MYSQLDATABASE` | MySQL service |

The app reads these automatically (see `environments/prod/common/config/main-local.php`).

Alternatively set manually:

```
DB_DSN=mysql:host=HOST;port=PORT;dbname=DATABASE
DB_USER=...
DB_PASS=...
```

### Required secrets (generate locally)

```powershell
php -r "echo bin2hex(random_bytes(32));"
```

| Variable | Notes |
|----------|--------|
| `FRONTEND_COOKIE_KEY` | Random 64-char hex — frontend service |
| `BACKEND_COOKIE_KEY` | Different random hex — backend service |

### Mail (if sending email)

```
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=...
SMTP_PASS=...
SMTP_ENCRYPTION=tls
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=Field Training Platform
MAIL_SUPPORT=support@yourdomain.com
```

### Google OAuth (optional)

Use your **frontend** Railway domain:

```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_RETURN_URL=https://YOUR-FRONTEND-DOMAIN.up.railway.app/site/auth?authclient=google
```

Add the same URL in [Google Cloud Console](https://console.cloud.google.com/) → OAuth client → Authorized redirect URIs.

---

## Step 5 — Optional chat service

1. **+ New** → same repo
2. Rename to `chat`
3. **Settings** → **Build**:
   - Dockerfile path: `realtime/chat-server/Dockerfile`
   - Config-as-code path: `railway.chat.toml`
4. Generate domain, e.g. `https://chat-xxxx.up.railway.app`
5. On **frontend** (and backend if needed), set:

```
CHAT_WEBSOCKET_URL=https://chat-xxxx.up.railway.app
CHAT_BROADCAST_URL=https://chat-xxxx.up.railway.app/broadcast
```

---

## Step 6 — Deploy

Each push to `main` triggers a rebuild. On deploy the container:

1. Runs `composer install --no-dev`
2. Runs `php init --env=Production --overwrite=All`
3. Runs `php yii migrate --interactive=0`
4. Starts Apache

Check **Deploy Logs** for migration errors.

---

## Step 7 — Post-deploy checks

- [ ] Frontend URL loads home page
- [ ] Backend URL shows admin login
- [ ] Register / login works
- [ ] Google OAuth callback (if configured)
- [ ] File uploads work (writable `runtime/` and `uploads/`)
- [ ] **Do not** run `php yii sample-data` in production

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 on boot | Check deploy logs; verify MySQL variables linked |
| CSRF / cookie errors | Set `FRONTEND_COOKIE_KEY` / `BACKEND_COOKIE_KEY` |
| OAuth redirect mismatch | `GOOGLE_RETURN_URL` must match Google Console exactly |
| Pretty URLs 404 | Apache `mod_rewrite` enabled in Dockerfile (already configured) |
| Migrations fail | Confirm DB credentials; run `php yii migrate` from Railway shell |

### Railway shell (run once if needed)

Service → **Settings** → open shell / one-off command:

```bash
php yii migrate --interactive=0
php yii migrate/history
```

---

## Local Docker test (optional)

```powershell
docker build -f frontend/Dockerfile -t ftp-frontend .
docker run -p 8080:80 -e MYSQLHOST=host.docker.internal -e MYSQLUSER=root -e MYSQLPASSWORD= -e MYSQLDATABASE=my_project -e FRONTEND_COOKIE_KEY=testkey123 ftp-frontend
```

---

See also: `.env.example` for full variable list.
