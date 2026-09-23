# ShareHub — Setup

## 1. Create the database
From the project root:
```
php database/init.php
```
This creates `database/sharehub.sqlite` using `schema.sql`. You should see:
`Database created at .../sharehub.sqlite`

## 2. Seed sample data
```
php database/seed.php
```
This adds sample subscriptions, groups, and 2 users:
- `karyl@student.com` / `password123` (student)
- `admin@sharehub.com` / `admin123` (admin)

## 3. Run the app
```
php -S localhost:8000 -t public
```
Then open http://localhost:8000/test.php in your browser.

If you see a dump of 6 subscriptions (Canva Pro, Microsoft 365, etc.), your
database connection is fully working. Delete `public/test.php` once confirmed.

## Next steps
- Build `includes/auth.php` (login/register/session helpers)
- Port your existing browse.php / subscription.php pages to pull from
  `subscriptions` and `groups` instead of the hardcoded JS array
- Build `actions/join-group.php`, `actions/create-group.php`, `actions/checkout.php`
- Build `public/dashboard.php` and `public/admin.php`

## Folder structure
```
sharehub/
├── public/          ← web root (php -S points here)
│   ├── test.php
│   └── assets/
├── includes/
│   └── config.php   ← PDO connection, require this in every page
├── database/
│   ├── schema.sql
│   ├── init.php     ← run once to create the .sqlite file
│   ├── seed.php     ← run once to insert sample data
│   └── sharehub.sqlite (generated, don't commit to git)
└── actions/         ← POST handlers (join, create, checkout, auth)
```
