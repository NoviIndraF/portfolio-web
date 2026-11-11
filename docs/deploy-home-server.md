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

## Update kode
```bash
rsync ... (perintah yang sama)      # di Mac
ssh myhome 'cd /srv/apps/portofolio && docker compose up -d --build'
```
Migrasi jalan otomatis saat container start. Konten dan upload aman di volume.

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
- Belum publik. Langkah berikutnya: Caddy di `/srv/apps/proxy` + Tailscale Funnel/Cloudflare Tunnel, lalu ganti `APP_URL` ke domain HTTPS.
