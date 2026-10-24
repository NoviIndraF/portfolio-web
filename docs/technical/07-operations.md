# 7. Operations

## Run locally

```bash
php artisan serve          # website on http://127.0.0.1:8000, admin on /admin
composer run dev           # while changing CSS/JS (server + Vite with hot reload)
npm run build              # production assets (also copies simple-icons, downloads fonts once)
```

## Inspect the database

```bash
php artisan db:show                # connection and tables
php artisan db:table projects      # columns of one table
php artisan db                     # SQL shell (SQLite or MySQL)
```

Or a GUI: phpMyAdmin / Sequel Ace / TablePlus for MySQL, DB Browser for SQLite (`database/database.sqlite`). Edit content through `/admin` rather than the database, because the admin also manages image files.

## Backups and moving databases

```bash
php artisan portfolio:export                    # → storage/app/exports/<date>/ (content.json + files/)
php artisan portfolio:import <folder> --force   # restore / move (replaces all content, keeps admin users)
```

Moving from SQLite to MySQL:

1. `php artisan portfolio:export`
2. Set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`, then `php artisan config:clear`.
3. `php artisan migrate --force`
4. `php artisan portfolio:import <export folder> --force`
5. `php artisan db:seed --force` (creates the admin user if missing; content is not touched).

Also back up `storage/app/public` (uploaded files) if you back up the database by other means.

## After moving the project folder

The `public/storage` symlink must point to `storage/app/public`. If images return 404:

```bash
rm public/storage && ln -s ../storage/app/public public/storage
php artisan optimize:clear
```

## Troubleshooting

| Symptom | Fix |
|---|---|
| Images don't show | Recreate the storage link (above); check `APP_URL` in `.env`. |
| Styles or 3D missing | `npm run build`; for 3D also check Settings → 3D effects and that the browser supports WebGL. |
| Admin changes not visible | `php artisan optimize:clear` (in production, run `php artisan optimize` again afterwards). |
| "Content already exists: skipped" when seeding | Expected. Use `portfolio:import` or `SEED_FORCE=true php artisan db:seed` to replace content on purpose. |
| Forgot admin password | `php artisan tinker --execute="App\Models\User::first()->update(['password' => 'new-password'])"` |
| Upload fails on the server | Raise `upload_max_filesize` / `post_max_size` in PHP and `client_max_body_size` in Nginx (25 MB). |
| Small images missing after restoring files | `php artisan portfolio:images` |
