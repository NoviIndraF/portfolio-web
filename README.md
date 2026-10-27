# Novi Indra Fanani – Interactive Portfolio

Laravel 13 portfolio for a Mobile App Developer, aimed at recruiters: the key message (role, stats, Download CV / Contact Me) is on the first screen, each project has an interactive 3D phone with a themed scene, and all content is edited in an admin panel.

![Home page](docs/images/home-desktop.png)

| Project page | All projects (mobile) | Admin |
|---|---|---|
| ![Project page](docs/images/project-desktop.png) | ![All projects](docs/images/projects-mobile.png) | ![Admin](docs/images/admin-projects.png) |

**Stack:** Laravel 13 · Blade · Vite 8 · Tailwind CSS 4 · Filament 5 (admin) · Three.js · GSAP + ScrollTrigger · Lenis · simple-icons · SQLite / MySQL

**Lighthouse (mobile, with gzip + caching):** Performance 98–100 · Accessibility 100 · Best Practices 100 · SEO 100

## Documentation

| Guide | For |
|---|---|
| [User guide](docs/user-guide.md) | Website visitors (English) |
| [Panduan admin](docs/panduan-admin.md) | Mengelola isi website lewat `/admin` (Bahasa Indonesia) |
| [Technical documentation](docs/technical/README.md) | Architecture, data model, backend, frontend & 3D, admin panel, testing, operations, AI chatbot (English) |

---

## 1. Run it locally

Requirements: PHP 8.3+ (with `gd`, `pdo_sqlite`, `intl`, `zip`), Composer 2, Node.js 20+.

```bash
composer install
cp .env.example .env              # set ADMIN_EMAIL (and ADMIN_PASSWORD, or one is generated)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # imports data/content.json and seed/ images
php artisan storage:link
npm install
npm run build                     # or `npm run dev` while editing
php artisan serve
```

- Website: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/admin (log in with `ADMIN_EMAIL` and `ADMIN_PASSWORD`, or the random password printed by `migrate --seed`)
- Tests: `php artisan test`

> `npm run build` downloads the font (Plus Jakarta Sans) once and serves it from your own site. It needs internet during the build only.

## 2. Folder structure

```
app/
  Filament/                Admin panel: Projects (+ images), Skills, Home sections, Profile, Settings
  Http/Controllers/        Home, Project detail, robots.txt + sitemap.xml
  Models/                  Profile, Project, ProjectLink, ProjectFeature, ProjectTech, ProjectImage,
                           SkillGroup, Skill, Setting, PageSection
                           (+ Concerns: OptimizesImages = auto WebP, HasVisibility = show/hide)
  Services/ImageOptimizer  Original + WebP (max 1600 px) + small WebP (560 px wide)
  Support/                 Site (settings/profile per request), Icon (simple-icons), Img (srcset)
  Console/Commands/        portfolio:export / portfolio:import (backup, move database), portfolio:images
data/content.json          Source data for the first import
seed/<slug>/               Source images for the first import (seed/profile/photo.jpg, optional cv.pdf)
database/
  migrations/              All portfolio tables
  seeders/                 DatabaseSeeder (admin user + guard), PortfolioSeeder (imports content.json)
deploy/nginx.conf          Example production Nginx config (gzip, caching, uploads)
docs/                      User guide, admin guide (Indonesian), technical documentation
lang/en/site.php           All interface text (copy to lang/<locale>/ for another language)
resources/
  css/app.css              Theme (colors from Settings), phone frame, 3D/motion styles
  js/app.js                Entry: small UI modules; 3D and motion load later
  js/ui/                   nav, rail, phone-screens, lightbox, carousel3d, motion, three-boot
  js/three/                PhoneViewer3D, phone model, screen textures, shared helpers
  js/scenes/               One module per scene: hero (particles), scroll-ornament, learning, donation, shoe-store,
                           chat, health-iot, hydroponic, analytics, generic
  views/                   Layout, sections (hero, projects, skills, about, contact, text, timeline),
                           pages (home, all projects, project detail), components
tests/Feature/             Feature tests (pages, CV button, drafts, admin, sitemap, images, seeder guard)
```

## 3. Changing content in the admin panel

Everything below is done at **/admin**. No code changes are needed.

| What | Where | Notes |
|---|---|---|
| Name, tagline, bio, hero stats, email, GitHub, LinkedIn, phone | **Profile** | The tagline is split at `\|`: the first part is the big headline, the rest become tags. Empty LinkedIn or phone is hidden on the site. |
| Profile photo | **Profile → Photo & CV** | Any JPG/PNG. A WebP copy is made automatically. |
| About gallery | **Profile → Gallery** | Several photos with captions, shown as a carousel in About. Drag to reorder, switch to hide. Empty = the profile photo is used. |
| CV | **Profile → CV (PDF)** | Upload a PDF and the **Download CV** buttons appear. Remove it and they hide. |
| Add a project | **Projects → New project**, or **⋯ → Duplicate** on a similar one | *Duplicate* copies features, technologies and links as a hidden draft (images are not copied). |
| Project order | **Projects** list | Drag rows. |
| Publish / hide a project | **Projects** list → *Published* switch (or select several → bulk actions) | Hidden projects are only visible to you while logged in (preview). |
| Home carousel vs. all projects | **Projects** list → *On home* switch | Home shows projects marked *On home*; **/projects** lists every published project with type filters. A "See all N projects" button appears when some are not on home. |
| Type and year | **Overview** → *Type*, *Year* | Type is free text (suggestions: mobile, web, iot…); it drives the filter on /projects. |
| Project texts | **Projects → Edit → Overview** | *Impact line* is the sentence on the card: add real numbers when you have them. *Problem* / *Solution* are optional. |
| Colour and 3D scene of a project | **Overview** → *Accent color*, *3D scene* | Colour drives the card, page glow and 3D scene. For new projects that fit none of the themes use **Generic** (floating shapes) or **None** (phone only). |
| Features, technologies, links | **Features** and **Tech & links** tabs | All drag-sortable, each with a **Show on website** switch. No visible links means no link buttons. |
| HKI / recognition | **Recognition & 3D model** tab | Leave empty to hide the badge. |
| Screenshots and other images | **Projects → Edit → Images** (bottom of the page) | Each image has a **Show** switch. Choose the *kind*: **Phone screenshot** images become the 3D phone screen; *Web*, *Admin panel*, *Hardware*, *Certificate* go to the gallery. Drag to reorder. WebP + small versions are created automatically; replaced or deleted files are removed. |
| Custom 3D model | **Recognition & 3D model → GLB model** | Optional `.glb` (keep it under ~3 MB). Normalised to the right size automatically. For the shoe-store scene it replaces the built-in sneaker; for other scenes the built-in visuals are used. |
| Skills | **Skills** | Groups are drag-sortable; groups and single skills have a show/hide switch. *Icon* is an optional [simple-icons](https://simpleicons.org) slug (many are detected from the name). |
| Home page sections | **Home sections** | Drag to reorder, switch to hide, edit title / small label / intro / menu label (empty = default text). **Add section** creates a *Text block* or a *Timeline* (work experience, education, certificates, awards…). Built-in sections can be hidden but not deleted. The top menu follows the visible sections. |
| Site title, SEO description, share image | **Settings** | Share image: 1200×630 PNG/JPG. If empty, the profile photo is used. |
| Theme colours | **Settings → Theme** | Primary, background and text colour. Keep text and background high-contrast. |
| Turn 3D off | **Settings → 3D effects** | Shows static phones instead (useful for very old phones). |
| Your admin password | Top-right avatar → **Profile** (`/admin/profile`) | Change the temporary password after the first login. |

Interface text that is not content (button labels, section titles) lives in `lang/en/site.php`.

## 4. Deploy

The site must be online (HTTPS) so the link / QR code in your slides works. Pick one option.

### Option A: VPS (Ubuntu 24.04, Nginx, PHP 8.4-FPM) – recommended

```bash
# On the server (once)
sudo apt install nginx php8.4-fpm php8.4-{cli,gd,sqlite3,intl,zip,mbstring,xml,curl} unzip git
# Install Composer (getcomposer.org) and Node.js 20+ (nodejs.org or nvm)

sudo mkdir -p /var/www/portfolio && sudo chown $USER:www-data /var/www/portfolio
git clone <your-repo-url> /var/www/portfolio     # or upload the project folder
cd /var/www/portfolio

composer install --no-dev --optimize-autoloader
cp .env.example .env
#   APP_ENV=production   APP_DEBUG=false   APP_URL=https://your-domain
#   ADMIN_EMAIL=...      ADMIN_PASSWORD=...  (a strong one)
#   GEMINI_API_KEY=...   (free key for the AI chatbot, see docs/technical/08-ai-chatbot.md)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force --seed
php artisan storage:link

npm ci && npm run build

php artisan optimize           # caches config, routes, views
php artisan filament:optimize  # caches admin components and icons

sudo chown -R $USER:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache database
```

Then use [`deploy/nginx.conf`](deploy/nginx.conf) (it adds gzip, long-term caching and a 25 MB upload limit), and enable free HTTPS:

```bash
sudo certbot --nginx -d your-domain -d www.your-domain
```

Also raise PHP's upload limit to match (in `/etc/php/8.4/fpm/php.ini`: `upload_max_filesize = 25M`, `post_max_size = 25M`), then `sudo systemctl restart php8.4-fpm`.

### Option B: Shared hosting (cPanel)

1. On your computer: `composer install --no-dev -o` and `npm ci && npm run build`.
2. Upload the whole project **outside** `public_html` (e.g. `~/portfolio`), then point the domain's document root to `~/portfolio/public` (cPanel → Domains). If you can't change the document root, copy the contents of `public/` into `public_html` and fix the two paths in `public_html/index.php` (`__DIR__.'/../vendor/...'` and `'/../bootstrap/app.php'`) to point to `~/portfolio`.
3. Create `.env` as above (or use MySQL: `DB_CONNECTION=mysql` plus `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
4. In cPanel → Terminal (or SSH): `php artisan key:generate`, `php artisan migrate --force --seed`, `php artisan storage:link`, `php artisan optimize`, `php artisan filament:optimize`.
5. The included `public/.htaccess` already enables gzip and browser caching on Apache.

### Which database?

| | When it fits | Notes |
|---|---|---|
| **MySQL 8 / MariaDB 10.6+** (recommended online) | Shared hosting (cPanel), VPS, most managed hosts | Available almost everywhere, easy backups in cPanel/phpMyAdmin, fully supported by Laravel and Filament. |
| SQLite (default) | Laptop, or a single VPS with a persistent disk | Zero setup; the whole database is one file (`database/database.sqlite`). Fine for a one-admin portfolio. |
| PostgreSQL | Hosts that offer Postgres (e.g. some PaaS) | Also supported without code changes (`DB_CONNECTION=pgsql`). |

> On platforms with a temporary disk (container/PaaS hosting), use MySQL/Postgres **and** S3-compatible storage for uploads (`FILESYSTEM` public disk → S3/R2), otherwise uploads disappear on redeploy.

> Tested: all migrations, a full content import and the complete test suite pass on both SQLite and MySQL.

### Using MySQL instead of SQLite

Set in `.env`, create the empty database, then run the migration and seed commands:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolio
DB_USERNAME=...
DB_PASSWORD=...
```

### Moving your content (laptop → server, SQLite → MySQL) and backups

Everything you enter in the admin (texts, sections, projects, skills, settings) plus the original files (photos, screenshots, CV, 3D models) can be exported to one folder and imported anywhere:

```bash
# On your laptop
php artisan portfolio:export                 # → storage/app/exports/<date>/ (content.json + files/)

# Upload that folder to the server (e.g. ~/portfolio-export), then on the server, after `php artisan migrate --force`:
php artisan portfolio:import ~/portfolio-export --force
```

`portfolio:import` replaces all content (admin users are kept). Run `portfolio:export` regularly as a backup; to restore, import the folder again.

### Updating the live site later

```bash
git pull                       # or upload changed files
composer install --no-dev -o
npm ci && npm run build
php artisan migrate --force
php artisan optimize && php artisan filament:optimize
```

> **Content is safe:** `php artisan db:seed` will **not** overwrite projects that already exist. To deliberately wipe everything and re-import `data/content.json`, run `SEED_FORCE=true php artisan db:seed`. Back up `database/database.sqlite` and `storage/app/public` regularly; that's all your content.

### After going live

1. Log in at `/admin`, change the password (`/admin/profile`), upload your CV.
2. Paste the URL into a link-preview checker (e.g. the LinkedIn Post Inspector) to confirm the title and image.
3. Generate a QR code for the URL and add it to the first and last slide of your presentation.

## 5. Performance and fallbacks (what's built in)

- **Fast first screen:** the page loads only ~4 KB of JS (gzip). Three.js (~150 KB gzip), each scene, GSAP and Lenis are downloaded **on the visitor's first interaction** (scroll, touch, mouse, key). Until then a crisp static phone with real screenshots is shown.
- **Images:** original kept; display WebP (max 1600 px); small WebP (560 px) served via `srcset` to phones, cards and thumbnails. Lazy-loaded below the fold. After restoring old data, run `php artisan portfolio:images`.
- **3D only when useful:** rendering pauses when off-screen or the tab is hidden; canvas resolution is capped on phones.
- **Fallbacks:** static phone when 3D is off in Settings, WebGL is missing, the GPU context is lost, data saver is on, or the first screenshot fails to load. Gallery images are normal links without JavaScript.
- **Reduced motion:** with the OS "reduce motion" setting there is no auto-rotation, auto-advance, smooth scrolling, scroll animations or page transitions. The phone still responds to your own drag.
- **SEO:** per-page title/description, Open Graph + Twitter cards, canonical URL, JSON-LD `Person`, `/sitemap.xml`, `/robots.txt` (admin excluded), custom 404 page.

## 6. Before you share the link (checklist)

- [ ] Change the admin password and upload your CV.
- [ ] Replace draft **impact lines** with real results where possible (users, downloads, grades, adoption).
- [ ] Add your LinkedIn URL in Profile.
- [ ] Only upload photos you own or may use (no third-party watermarks), and hide real names in screenshots (the Comel patients are shown as "Patient A–D").
- [ ] Check the HKI certificates (IO-Tani, Sidedi) once more for personal data.
- [ ] Add links for Comel / Donice / Chat Apps if repositories or store pages exist.

## 7. Assumptions made during the build

- Ordering columns are named `sort_order` (instead of `order`, a reserved SQL word).
- Projects have optional `problem` / `solution` fields (empty by default; the short description is shown instead).
- Settings has three theme colours (primary, background, text). If no share image is set, the profile photo is used.
- Database content is English only; interface text is translatable via `lang/`. A multilingual content field can be added later (e.g. `spatie/laravel-translatable`).
- The placeholder screens for IO-Tani and Sidedi show labels and moving gauges/bars only, with no made-up numbers.
- 3D and animations start on first interaction (not on page load) to keep mobile Lighthouse above 85.
- Zoom uses pinch (touch or trackpad); a plain mouse wheel keeps scrolling the page.
- Gyroscope tilt works where no permission prompt is required (Android); iOS uses drag only.
