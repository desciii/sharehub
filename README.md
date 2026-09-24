# ShareHub

A student project prototype for splitting subscriptions (Netflix, Canva, Microsoft 365, Turnitin and more) with other students. Browse a plan, join an open group or start your own, and pay only your share. **Payments are simulated, so nothing is ever charged.**

## Features

- **Landing page** with a login / register panel that slides into the hero
- **Home** with search, category tabs and quick actions
- **Browse** subscriptions with search, category and open-slot filters
- **Subscription page** listing every group for a plan, with slots left and price per slot
- **Create a group** by choosing a subscription, the number of slots and the price per slot
- **Edit a group** (owners only) to change slots and price
- **Checkout** to join a group or pay your unpaid slot, with a simulated payment method (GCash, Maya, Card)
- **Dashboard** showing every group you're in, its status, your payment state and total paid

Pricing is your share of the plan plus a flat platform fee (`PLATFORM_FEE` in `includes/app.php`, currently ₱10).

## Tech stack

- PHP 8+ with PDO and SQLite (the `pdo_sqlite` extension must be enabled)
- Plain HTML, CSS and JavaScript, with no build step
- [Hotwire Turbo](https://turbo.hotwired.dev/) loaded from a CDN for fast, no-reload page navigation
- Company logos from Google's favicon service, so logos need an internet connection

## Getting started

Run these from the project root.

### 1. Create the database

```
php database/init.php
```

This creates `database/sharehub.sqlite` from `database/schema.sql`. You should see `Database created at .../sharehub.sqlite`.

### 2. Seed sample data

```
php database/seed.php
```

This adds sample subscriptions, groups and two users:

| Email | Password | Role |
| --- | --- | --- |
| `karyl@student.com` | `password123` | student |
| `admin@sharehub.com` | `admin123` | admin |

### 3. Run the app

```
php -S localhost:8000 -t public
```

Open <http://localhost:8000> in your browser and log in with one of the accounts above.

To check the database connection on its own, open <http://localhost:8000/test.php>. Delete `public/test.php` once you've confirmed it works.

## Project structure

```
sharehub/
├── actions/
│   └── auth-handler.php      POST handler for login / register / logout
├── database/
│   ├── schema.sql            Table definitions
│   ├── init.php              Creates the .sqlite file (run once)
│   ├── seed.php              Inserts sample data (run once)
│   └── sharehub.sqlite       Generated, don't commit
├── includes/
│   ├── config.php            PDO connection, required by every page
│   ├── auth.php              Sessions, login helpers, CSRF
│   ├── app.php               Shared helpers and the page shell (app_header / app_footer)
│   ├── nav.php               Shared header bar (app_nav) and head assets
│   └── catalog.php           Subscription catalog shown on the home page
└── public/                   Web root
    ├── index.php             Landing page + login / register
    ├── home.php              Logged-in home
    ├── browse.php            Browse and filter subscriptions
    ├── subscription.php      One subscription and its groups
    ├── create-group.php      Start a group
    ├── edit-group.php        Owner edits slots and price
    ├── checkout.php          Join and pay (simulated)
    ├── dashboard.php         Your groups and spending
    ├── auth-handler.php      Auth POST entry point
    ├── logout.php            Ends the session
    ├── test.php              Database sanity check (delete after use)
    ├── assets/               app.css, nav.css, style.css
    ├── js/                   nav.js
    └── images/               hero.jpg, home-1.jpg, home-2.jpg
```

## How the pages fit together

Every logged-in page requires `config.php`, `auth.php` and `app.php`, calls `require_login()`, then wraps its content in `app_header('Title', 'active-key')` and `app_footer()`. The header includes the shared nav from `includes/nav.php`, which is styled by `public/assets/nav.css`. `home.php` and `index.php` have their own full layouts, and `home.php` still uses the shared nav.

Typical user flow:

1. Browse or search for a subscription.
2. Open it to see its groups, then hit **Join** to go to checkout, or **Create a group**.
3. Pay at checkout (simulated) and land on a confirmation screen.
4. Manage your groups from the dashboard. Owners get a **Manage** button that opens the edit page.

## Notes

- Forms use CSRF tokens (`csrf_field()` and `verify_csrf()`).
- Pages that redirect after a successful POST send a `422` status on validation errors so Turbo renders the error messages.
- Page-specific styles live in the page's own `<style>` inside `<body>`, so Turbo drops them when you navigate away and they can't leak into other pages.
- Payment is a prototype only: the checkout records a paid transaction but never contacts a payment provider.