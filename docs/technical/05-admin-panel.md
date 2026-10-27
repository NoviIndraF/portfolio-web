# 5. Admin panel (Filament 5)

Panel: `app/Providers/Filament/AdminPanelProvider.php` – path `/admin`, login, account page (`/admin/profile`, password change), navigation groups **Content** and **Site**, a "View website" link, dashboard widgets `AccountWidget` and `App\Filament\Widgets\ContentStatus`.

## Structure

| Admin menu | Code | Notes |
|---|---|---|
| Content → Profile | `Filament/Pages/EditProfile.php` (`/admin/portfolio-profile`) | Single-record page built on `SingleRecordPage`. Section **Gallery**: `Repeater::make('photos')->relationship()` (upload, caption, show/hide, drag to reorder) |
| Content → Projects | `Filament/Resources/Projects/…` | Form in `Schemas/ProjectForm.php` (tabs), table in `Tables/ProjectsTable.php`, images in `RelationManagers/ImagesRelationManager.php` |
| Content → Skills | `Filament/Resources/SkillGroups/…` | Group form with a skills repeater |
| Site → Home sections | `Filament/Resources/PageSections/…` | Built-in sections can't be deleted (UI and model `deleting` guard) |
| Site → Settings | `Filament/Pages/SiteSettings.php` (`/admin/settings`) | Single-record page |

Filament 5 conventions used: resources split into `Schemas/*Form.php` and `Tables/*Table.php`; form layout classes live in `Filament\Schemas\Components` (`Section`, `Tabs`, `Grid`, `Utilities\Get/Set`), fields in `Filament\Forms\Components`, actions in `Filament\Actions`.

### `SingleRecordPage`
`app/Filament/Pages/SingleRecordPage.php` is a small base class for pages that edit one row: implement `getRecord()` and `getFormComponents()`; it renders the form with a Save button (`mod+s`), saves (including relationship repeaters via `saveRelationships()`), refills the form and shows a notification.

### Projects
- Tabs: **Overview** (title → slug on create, type with suggestions, year, 3D scene, color, published, on home, texts), **Features** (repeater), **Tech & links** (table repeater + repeater), **Recognition & 3D model**.
- Every child item has a "Show on website" toggle (`ProjectForm::visibleToggle()`), and repeater item labels show "(hidden)".
- Table: drag reorder (`sort_order`), toggle columns for Published / On home, filters, bulk actions (publish, hide, show on home, remove from home, delete), **Duplicate** (`ReplicateAction`: copies features, tech and links, new unique slug, unpublished).
- Empty recognition fields are stored as `null` (`ProjectResource::cleanRecognition()`).

### Images (relation manager)
`FileUpload` to `projects/{slug}` on the public disk, kind, caption, visibility. Conversion to WebP happens in the model (`OptimizesImages`), so it works for any upload path. Bulk delete deletes models one by one so files are removed.

## How to add a new field (example: `projects.client`)

1. Migration:
   ```php
   Schema::table('projects', fn (Blueprint $t) => $t->string('client')->nullable()->after('year'));
   ```
2. Model: add `'client'` to `$fillable` in `app/Models/Project.php`.
3. Admin: add `TextInput::make('client')->maxLength(100)` in `ProjectForm::overviewTab()`.
4. View: show it, e.g. in `resources/views/pages/project.blade.php`.
5. Export/import: add it to `ExportContent` (projects map) and `PortfolioSeeder::seedProjects()`.
6. Test: extend `tests/Feature/CustomizableContentTest.php`.
7. `php artisan migrate`.

## Access

Every user in `users` may log in (`User::canAccessPanel()` returns true) because there is no registration. Create another admin with `php artisan make:filament-user`, or set `ADMIN_EMAIL`/`ADMIN_PASSWORD` before the first `db:seed`.
