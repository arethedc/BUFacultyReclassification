# Troubleshooting

## Mail Not Sending on Deploy

Symptoms:
- User created but password setup email not sent.
- Forgot password returns 500.

Checks:
1. Ensure SMTP values are set in deploy environment.
2. Use a valid `MAIL_ENCRYPTION` / scheme supported by Laravel mail transport.
3. Verify outbound SMTP host and port are reachable from host platform.
4. Confirm `MAIL_FROM_ADDRESS` is valid and accepted by provider.

## Evidence File 404 / Broken Preview

Symptoms:
- Old evidence links open 404.

Checks:
1. Confirm `php artisan storage:link` exists in startup/deploy process.
2. Ensure disk URL is configured to `/storage` or valid public URL.
3. Verify file exists under `storage/app/public/...`.

## Blade Changes Not Reflected

Symptoms:
- Page still shows old markup after deploy.

Fix:
- Run `php artisan optimize:clear`.
- Rebuild caches only after successful deploy state.

## Alpine Panel / UI Errors

Symptoms:
- `x-collapse` warning, or undefined Alpine state errors.

Checks:
1. Avoid loading Alpine twice (CDN + Vite bundle).
2. Ensure collapse plugin is registered in app JS if using `x-collapse`.
3. Validate `x-data` expressions and JSON encoding in Blade (`@js(...)`).

## Queue / Schedule Related Notifications Missing

Checks:
1. Confirm scheduler is running (`php artisan schedule:work` or cron).
2. Confirm queue worker is running for queued notifications.
3. Review application logs in `storage/logs/laravel.log`.
