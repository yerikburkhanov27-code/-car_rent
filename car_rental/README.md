# NurDrive — Car Rental

PHP 8+ + MySQL car rental app with Kazakh-first UI (KK / RU / EN), live booking, payments, and admin CRUD.

## Requirements

- PHP 8.0+
- MySQL 5.7+ / 8.x on `127.0.0.1`
- User `root` with an empty password
- Database `car_rental_db` (created automatically on first request)

## Run locally

From this folder:

```bash
php -S localhost:8080
```

Open http://localhost:8080

On first load the app creates tables and seeds sample data.

## Admin

- URL: `/login.php`
- Username: `admin`
- Password: `admin123`

## Layout

- `config/db.php` — PDO connection
- `includes/` — header, footer, i18n, auth
- `api/` — JSON endpoints for cars, clients, rentals, payments, employees, admins
- `assets/js/app.js` — language toggle, modals, calculator, CRUD
- Main pages: `index.php`, `cars.php`, `rentals.php`, `clients.php`, `payments.php`, `admin.php`
