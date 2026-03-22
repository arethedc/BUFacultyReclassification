# Faculty Reclassification System

Web-based workflow platform for faculty reclassification, with role-based review stages (`Dean -> HR -> VPAA -> President`), evidence review, comment tracking, and audit trails.

## Tech Stack

- Laravel 12 (PHP 8.2)
- Blade + Alpine.js + Tailwind CSS
- PostgreSQL (production)
- Vite
- Render (Docker deployment)

## Quick Start (Local)

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Configure environment:
   - `cp .env.example .env`
   - Set database and mail credentials
   - `php artisan key:generate`
3. Prepare app:
   - `php artisan migrate`
   - `php artisan db:seed`
   - `php artisan storage:link`
4. Run:
   - `php artisan serve`
   - `npm run dev`

## Documentation

For complete project docs, open: `docs/README.md`

## Useful Commands

- Run tests: `php artisan test`
- Build assets: `npm run build`
- Clear caches: `php artisan optimize:clear`

## License

MIT
