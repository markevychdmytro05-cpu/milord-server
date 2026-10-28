# Що робити після `git pull` (інструкція для Claude на сервері)

Проєкт: Laravel 13, PHP 8.4, Vite. Сайт numis.in.ua (публічна частина + адмінка Backpack на `/admin`).

## Порядок дій після появи нового коміту

```bash
cd /шлях/до/проєкту
git pull origin main
git log --oneline -5    
git diff --name-only ORIG_HEAD HEAD 
```

1. **PHP-залежності** (лише якщо змінились `composer.json` або `composer.lock`):
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```
2. **Фронт**:
   ```bash
   npm ci
   npm run build
   ```
3. **Міграції**:
   ```bash
   php artisan migrate --force
   ```
4. **Посилання на сховище**:
   ```bash
   php artisan storage:link
   ```
5. **Кеші**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```
6. **Черга**: перезапусти воркери, щоб вони підхопили новий код:
   ```bash
   php artisan queue:restart
   ```
