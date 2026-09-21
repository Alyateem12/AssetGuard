# AssetGuard

Equipment & Fleet Maintenance Tracking System — a web-based system for tracking equipment, maintenance requests, preventive schedules, spare parts, and repair history through an Admin/Technician dashboard.

## Overview

AssetGuard helps companies and organizations manage their equipment and fleet (heavy machinery, vehicles, warehouse equipment, power equipment) by tracking maintenance requests, scheduling preventive maintenance, monitoring spare parts stock, and keeping a full maintenance history for every asset.

## Tech Stack

- PHP (procedural) + PDO
- MySQL
- HTML5, CSS3, JavaScript
- Bootstrap 5
- Chart.js

## Design

- **Theme:** Dark Industrial Glass — dark background, glassmorphism panels, industrial-style live gauges
- **Colors:** Dark orange `#B34700` (primary) + gold/light orange `#F2A65A` (accent)
- **Motion:** Soft page transitions, clear hover effects, animated live gauges for equipment health indicators

## Project Structure

```
AssetGuard/
├── index.php               Entry point — redirects by login state/role
├── database.sql             Database schema + seed data
├── config/
│   ├── config.php           App constants, timezone, session
│   └── database.php         PDO connection
├── includes/
│   ├── functions.php        Shared helper functions
│   ├── header.php           Page head + navbar + sidebar include
│   ├── footer.php           Scripts + closing tags
│   └── sidebar.php          Role-based navigation
├── auth/
│   ├── login.php
│   └── logout.php
├── admin/
│   ├── dashboard.php
│   ├── manage_equipment.php
│   ├── manage_requests.php
│   ├── manage_schedule.php
│   ├── manage_technicians.php
│   ├── spare_parts_alerts.php
│   └── reports.php
├── technician/
│   ├── dashboard.php
│   ├── my_requests.php
│   └── equipment_profile.php
└── assets/
    ├── css/style.css
    └── js/script.js
```

## User Roles

- **Admin** — full access: manage equipment, technicians, requests, schedules, spare parts, and reports.
- **Technician** — limited access: view assigned equipment, update assigned maintenance requests, view equipment history.

## Database

6 tables: `users`, `equipment`, `maintenance_requests`, `maintenance_schedule`, `spare_parts_alerts`, `maintenance_history`.

## Security

- Passwords hashed with `password_hash()` / verified with `password_verify()`
- All database queries use PDO prepared statements
- Input sanitized via `clean()` helper before storage/display

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@assetguard.com | 123456 |
| Technician | tech@assetguard.com | 123456 |

## Setup

1. Import `database.sql` into MySQL (via phpMyAdmin or CLI).
2. Update `config/database.php` if your database credentials differ from the XAMPP defaults.
3. Place the project folder inside your web server's root (e.g. `htdocs` for XAMPP).
4. Visit `index.php` in your browser and log in with one of the demo accounts above.

## Roadmap

- CSRF protection on forms
- Rate limiting on login
- Email notifications for overdue preventive maintenance
- QR code generation for equipment profiles
