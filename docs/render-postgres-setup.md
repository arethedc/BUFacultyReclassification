# Render + PostgreSQL Setup

## 1. Push repo with `render.yaml`
Render will detect Blueprint config and create:
- Web service: `faculty-reclassification-web`
- Cron service: `faculty-reclassification-scheduler`
- Postgres DB: `faculty-reclassification-db`

## 2. Set required secret env vars (both services)
- `APP_KEY`
- `APP_URL`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`

You can generate app key locally:

```bash
php artisan key:generate --show
```

Copy the full value (example starts with `base64:`) into Render `APP_KEY`.

## 3. Deploy
- First deploy runs:
  - `composer install`
  - `npm run build`
  - `php artisan migrate --force` (post-deploy)

## 4. Verify
- Open the app URL.
- Create/login user.
- Check email verification flow.
- Check scheduled reminders by watching Cron logs for:
  - `reclassification:notify-deadlines`

