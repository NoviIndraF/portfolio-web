# Deploy ke home server (pribadi, lewat Tailscale)

Server `myhome-server` (Ubuntu, Docker). Folder: `/srv/apps/portofolio`. Akses: `http://100.76.192.19:8080` (hanya dari perangkat di tailnet).

## Isi
- `Dockerfile`: build aset Vite (Node 24) lalu app di FrankenPHP (PHP 8.4).
- `compose.yaml`: service `app` + `db` (MySQL 8.4), `restart: unless-stopped`. Volume `storage` (upload, log) dan `mysql`.
- Port hanya di-bind ke IP Tailscale (`APP_BIND`, default `100.76.192.19`). Port Docker melewati ufw, jadi jangan ganti ke `0.0.0.0`. MySQL tidak di-publish.
- `.env` produksi hanya ada di server (`chmod 600`, tidak di git). Berisi `APP_KEY`, password DB, dan `ADMIN_PASSWORD` acak.

## Deploy pertama
```bash
# Di Mac, dari folder project
rsync -az --delete --exclude-from=.dockerignore --include='.env.example' --exclude='.env' --exclude='docs/images' ./ myhome:/srv/apps/portofolio/
php artisan portfolio:export storage/app/exports/home-server
rsync -az storage/app/exports/home-server/ myhome:/srv/apps/portofolio-import/

# Di server
cd /srv/apps/portofolio
docker compose up -d --build
docker compose exec app php artisan db:seed --force           # buat akun admin dari ADMIN_* di .env
docker compose cp /srv/apps/portofolio-import app:/tmp/import
docker compose exec app php artisan portfolio:import /tmp/import --force
```
Login admin: `/admin`, email `ADMIN_EMAIL`, password `ADMIN_PASSWORD` di `.env` server. Ganti password setelah login pertama.

## Update kode (otomatis, CI/CD)
Push ke `master` → job `tests` → `image` (build, push ke `ghcr.io/noviindraf/portfolio-web`) → `deploy`.
Job `deploy` masuk ke tailnet sebagai node sementara `tag:ci`, lalu SSH ke server dengan deploy key yang
dikunci ke `deploy/server-deploy.sh` (pull image, `up -d`, cek situs menjawab). Lihat `.github/workflows/tests.yml`.

Migrasi jalan otomatis saat container start. Konten dan upload aman di volume.
`compose.yaml` dan `.env` di server tidak ikut CD: kalau berubah, rsync manual (perintah di atas).

### Setup sekali
1. Tailscale admin → Access controls: tambahkan `"tagOwners": {"tag:ci": ["autogroup:admin"]}` dan grant
   `{"src": ["tag:ci"], "dst": ["100.76.192.19"], "ip": ["tcp:22"]}`. Pastikan tidak ada rule `"src": ["*"]`
   (ganti dengan `autogroup:member`), supaya `tag:ci` hanya bisa ke SSH server.
2. Tailscale admin → Settings → OAuth clients: scope Auth Keys (write), tag `tag:ci`.
3. GitHub secrets: `TS_OAUTH_CLIENT_ID`, `TS_OAUTH_SECRET`, `SSH_DEPLOY_KEY` (private key ed25519 khusus).
4. Public key deploy di server `~/.ssh/authorized_keys` dengan awalan
   `restrict,command="/srv/apps/portofolio/deploy/server-deploy.sh"`.

## Update manual (tanpa CI)
```bash
rsync ... (perintah yang sama)      # di Mac
ssh myhome 'cd /srv/apps/portofolio && docker compose up -d --build'
```

## Perintah berguna
```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan portfolio:export /app/storage/app/exports/backup   # backup konten
```

## Catatan
- Kalau server reboot dan Docker start sebelum Tailscale punya IP, bind port bisa gagal. Perbaikan (sekali, butuh sudo): izinkan bind ke IP yang belum ada.
  ```bash
  echo 'net.ipv4.ip_nonlocal_bind=1' | sudo tee /etc/sysctl.d/99-nonlocal-bind.conf && sudo sysctl --system
  ```
- Publik lewat Cloudflare Tunnel (`https://noviindra.dev`). Header keamanan, redirect `www`, blok file tersembunyi dan aturan proxy tepercaya ada di `deploy/Caddyfile` (ikut di image). Di `.env` server: `APP_URL=https://noviindra.dev`, `SESSION_SECURE_COOKIE=true`, `CADDY_HTTPS=on`, `CADDY_TRUSTED_PROXIES=100.76.192.19/32` (alamat cloudflared di host). Service tunnel dan kredensialnya ada di luar repo, di `/srv/apps/portofolio-public` (lihat README di sana).
