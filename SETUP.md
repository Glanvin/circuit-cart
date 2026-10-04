# Setup

Installation walkthrough for **Circuit Cart** (CodeIgniter 3 + MySQL). For a feature overview see [README.md](README.md); for the test suite see [TESTING.md](TESTING.md).

## Requirements

- PHP >= 7.2 (developed on PHP 8.1)
- MySQL / MariaDB
- XAMPP (or any Apache + PHP + MySQL stack)
- Composer

## Installation

1. Clone the repo into your web root (e.g. `C:/xampp/htdocs/ci3`).
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create the database and import the schema. `db_schema.sql` is the single
   source of truth — it creates every table the app uses and seeds the demo
   catalog, employees and customer account:
   ```bash
   mysql -u root -e "CREATE DATABASE circuit_cart"
   mysql -u root circuit_cart < db_schema.sql
   ```
   The script is idempotent (`IF NOT EXISTS` / `ON DUPLICATE KEY UPDATE`), so
   re-running it against an existing database is safe.
4. Point the app at that database. Credentials live in `.env` at the project
   root, not in `application/config/database.php` (that file reads them from
   environment variables):
   ```ini
   DATABASE_NAME="circuit_cart"
   DATABASE_HOSTNAME="127.0.0.1"
   DATABASE_PORT="3306"
   DATABASE_USERNAME="root"
   DATABASE_PASSWORD=""
   BASE_URL="http://localhost/ci3/"
   ```
   > The defaults above are XAMPP defaults (`root`, empty password). Do not ship
   > them to production.
5. Ensure `storage/booking/`, `storage/bugs/` and `storage/tmp/` exist and are
   writable. Device photos are stored in the first (5 MB max each); bug-report
   screenshots go in the second (3 MB max each, up to 3 per report, signed-in
   users only); the third holds spreadsheets uploaded through the admin user
   import and is cleared after every import. All three folders are already
   git-ignored.
6. Open `http://localhost/ci3/` in your browser.

## Schema

`db_schema.sql` contains the full database — six tables, plus seed data.

| Table | Purpose |
| --- | --- |
| `tbl_users` | Customer accounts. `userhistory` is a JSON array of `{action, timestamp}`. |
| `tbl_sku` | Parts catalog. `compatibility` is a JSON array of supported devices, `technical_specs` a JSON object; `is_active` is a soft-delete flag. |
| `tbl_employees` | Employee accounts, mirroring `tbl_users` but with `attendance_history` (JSON array of `{date, time_in, time_out}`) instead of `userhistory`. There is no `username` column — see below. |
| `tbl_sales` | POS and online orders. `items` is a JSON array of line items. Store sales always count toward analytics; online sales only count when `status = 'delivered'`. `sold_by` holds the employee's full name. |
| `tbl_booking` | Repair bookings (`status`: pending/approved/rescheduled/declined/finished), `images` is a JSON array of stored photo paths. |
| `tbl_bug_reports` | Bug reports (`status`: new/in_progress/fixed), `images` is a JSON array. No IP addresses are ever stored. |

Note on JSON columns: on MySQL these are native `JSON`; MariaDB implements
`JSON` as `LONGTEXT` plus a `json_valid()` check constraint, so the dumped DDL
may differ cosmetically from what the schema file declares.

## Demo accounts

Every demo password is `password123`. Employees sign in with their 6-digit
Employee ID — there is deliberately no employee username, so the ID is the only
login. The customer signs in with a username:

| Portal | Login | Role |
| --- | --- | --- |
| Employee | `000001` | Admin |
| Employee | `000002` | Cashier |
| Employee | `000003` | Manager |
| Employee | `000004` | Technician |
| Employee | `000005` | Developer |
| Employee | `000006` | Logistics |
| Customer | `demo` | Customer (user) |

## Troubleshooting

- **"Access denied for user"** — check `DATABASE_USERNAME` / `DATABASE_PASSWORD`
  in `.env` match your server.
- **Tables not found** — the database name in `.env` must match the one you
  imported into; re-run `mysql -u root circuit_cart < db_schema.sql`.
- **Images fail to upload** — confirm `storage/booking/` and `storage/bugs/`
  are writable by the web server user.
- **User import fails** — confirm `storage/tmp/` is writable. Only `.xlsx`,
  `.xls` and `.csv` are accepted (macro-enabled `.xlsm` is rejected), up to
  5 MB and 2000 data rows. Download the sample file from the import modal for
  the exact column layout.
- **Base URL wrong in links** — set `BASE_URL` in `.env` to match the folder
  the app is served from.
