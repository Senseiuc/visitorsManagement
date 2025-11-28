# Switch to PHP 8.2 in Laravel Sail

## Changes Made

✅ Updated `compose.yaml`:
- Changed runtime from `8.4` to `8.2`
- Changed image from `sail-8.4/app` to `sail-8.2/app`

✅ Updated `composer.json`:
- Set PHP requirement to `^8.2.0`

## Next Steps

Run these commands to rebuild Sail with PHP 8.2:

```bash
# Stop current containers
sail down

# Rebuild with PHP 8.2 (no cache to ensure clean build)
sail build --no-cache

# Start containers
sail up -d

# Verify PHP version
sail php -v
```

Expected output: `PHP 8.2.x`

## After Rebuild

```bash
# Update dependencies
sail composer update

# Clear caches
sail artisan config:clear
sail artisan cache:clear
```

Done! Your application will now run on PHP 8.2 exactly.
