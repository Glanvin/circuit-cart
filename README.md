# Circuit Cart

An all-in-one web app for a tech repair shop — customers browse the parts catalog (phone/laptop screens, batteries, Windows licenses, etc.) and check out, while employees manage inventory, stock and orders.

Built on **CodeIgniter 3** with **Bootstrap 5**, **MySQL**, and a custom **Toastify** flash-notification library.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database schema](#database-schema)
- [Demo accounts](#demo-accounts)
- [Routes](#routes)
- [Project structure](#project-structure)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Releases](#releases)

## Features

**Customer side**

- Home page with repair booking pitch and catalog links
- Browse page: product card grid with client-side filtering by device type and category
- Online checkout: guests supply a delivery email + address, or create an account to carry the address over

**Employee side**

- Separate employee sign-in using a 6-digit employee ID + password
- Role-based portal: cashier (POS), manager (analytics & order fulfilment), technician/developer (bookings / bug reports), logistics (inventory), admin (everything)
- POS cash register: record store sales, automatic stock decrement, exact-change computation, receipt numbers
- Analytics with locally-bundled Chart.js: store vs. delivered-online sales, user registrations, attendance, low-stock — export each chart as PNG/PDF (`jsPDF` via CDN); the range selector redraws charts, KPIs and tables in place via AJAX (`/employee/analytics/data`)
- Online order fulfilment: move orders from processing → shipped → delivered / cancelled; only `delivered` online sales count toward analytics
- Attendance: clock in/out with per-employee history stored in `tbl_employees.attendance_history`
- Admin hub for managing employees (create, role, password reset, activate/deactivate) and customer users (role promotion to admin), including a bulk user import from `.xlsx`/`.xls`/`.csv` (PhpSpreadsheet) that validates row by row, skips existing usernames and emails, and reports per-row reasons
- Inventory management: SKU CRUD, stock adjustments, soft-removal (`is_active`)

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
3. Create the database and import the schema. `db_schema.sql` is the single source of truth — it creates every table the app uses and seeds the demo catalog, employees and customer account:
   ```bash
   mysql -u root -e "CREATE DATABASE circuit_cart"
   mysql -u root circuit_cart < db_schema.sql
   ```
   The script is idempotent (`IF NOT EXISTS` / `ON DUPLICATE KEY UPDATE`), so re-running it against an existing database is safe.
4. Point the app at that database. Credentials live in `.env` at the project root, not in `application/config/database.php` (that file reads them from environment variables):
   ```ini
   DATABASE_NAME="circuit_cart"
   DATABASE_HOSTNAME="127.0.0.1"
   DATABASE_PORT="3306"
   DATABASE_USERNAME="root"
   DATABASE_PASSWORD=""
   BASE_URL="http://localhost/ci3/"
   ```
   > The defaults above are XAMPP defaults (`root`, empty password). Do not ship them to production.
5. Ensure `storage/booking/`, `storage/bugs/` and `storage/tmp/` exist and are writable. Device photos are stored in the first (5 MB max each); bug-report screenshots go in the second (3 MB max each, up to 3 per report, signed-in users only); the third holds spreadsheets uploaded through the admin user import and is cleared after every import. All three folders are already git-ignored.
6. Open `http://localhost/ci3/` in your browser.

## Database schema

`db_schema.sql` contains the full database — six tables, plus seed data.

| Table | Purpose |
| --- | --- |
| `tbl_users` | Customer accounts. `userhistory` is a JSON array of `{action, timestamp}`. |
| `tbl_sku` | Parts catalog. `compatibility` is a JSON array of supported devices, `technical_specs` a JSON object; `is_active` is a soft-delete flag. |
| `tbl_employees` | Employee accounts, mirroring `tbl_users` but with `attendance_history` (JSON array of `{date, time_in, time_out}`) instead of `userhistory`. There is no `username` column — see [Demo accounts](#demo-accounts). |
| `tbl_sales` | POS and online orders. `items` is a JSON array of line items. Store sales always count toward analytics; online sales only count when `status = 'delivered'`. `sold_by` holds the employee's full name. |
| `tbl_booking` | Repair bookings (`status`: pending/approved/rescheduled/declined/finished), `images` is a JSON array of stored photo paths. |
| `tbl_bug_reports` | Bug reports (`status`: new/in_progress/fixed), `images` is a JSON array. No IP addresses are ever stored. |

Note on JSON columns: on MySQL these are native `JSON`; MariaDB implements `JSON` as `LONGTEXT` plus a `json_valid()` check constraint, so the dumped DDL may differ cosmetically from what the schema file declares.

### The parts catalog (`tbl_sku`)

One row per part, keyed by a unique `sku`. `part_id` is zero-filled and pre-seeded from `100001` so IDs sort readably in the UI.

| Column | Type | Notes |
| --- | --- | --- |
| `part_id` | `INT(6) UNSIGNED ZEROFILL` | Primary key, auto-incrementing |
| `sku` | `VARCHAR(64)` | Unique stock-keeping code, e.g. `SCR-APL-IP11` |
| `part_name` | `VARCHAR(255)` | Display name shown on the product card |
| `category` | `VARCHAR(50)` | `Screen`, `Battery`, `Camera`, … — drives the category filter |
| `device_type` | `VARCHAR(50)` | `Smartphone`, `Laptop`, `Console`, … — drives the device filter |
| `cost_price` | `DECIMAL(10,2)` | What the shop pays |
| `selling_price` | `DECIMAL(10,2)` | Retail price; defaults to `0.00` |
| `stock_quantity` | `INT(11)` | Decremented automatically by the POS |
| `compatibility` | `JSON` | Array of device names this part fits |
| `technical_specs` | `JSON` | Free-form spec object, rendered as a definition list |
| `is_active` | `TINYINT(1)` | Soft-delete flag, defaults to `1` |
| `created_at` | `TIMESTAMP` | Insert time |

`technical_specs` has no fixed shape — each category uses whatever keys are meaningful, and the browse view renders whatever it finds:

```json
{
  "compatibility": ["iPhone 14 Pro Max"],
  "technical_specs": {
    "capacity_mah": 4323,
    "voltage_v": 3.86,
    "chemistry": "Li-ion"
  }
}
```

## Demo accounts

Every demo password is `password123`. Employees sign in with their 6-digit Employee ID — there is deliberately no employee username, so the ID is the only login. The customer signs in with a username:

| Portal | Login | Role |
| --- | --- | --- |
| Employee | `000001` | Admin |
| Employee | `000002` | Cashier |
| Employee | `000003` | Manager |
| Employee | `000004` | Technician |
| Employee | `000005` | Developer |
| Employee | `000006` | Logistics |
| Customer | `demo` | Customer (user) |

## Routes

| URL | Controller |
| --- | --- |
| `/` | `HomeController` |
| `/browse` | `BrowseController` |
| `/booking` | `BookingController` |
| `/booking/appointments` | `BookingController::appointments()` |
| `/bugs` | `BugController` |
| `/storage/booking` | `EmployeeBookingController` |
| `/storage/booking/approve/{id}` | `EmployeeBookingController::approve()` |
| `/storage/booking/reschedule/{id}` | `EmployeeBookingController::reschedule()` |
| `/storage/booking/decline/{id}` | `EmployeeBookingController::decline()` |
| `/storage/bugs` | `EmployeeBugController` |
| `/storage/bugs/status/{id}/{status}` | `EmployeeBugController::status()` |
| `/employee/dashboard` | `DashboardController` (admin) |
| `/employee/analytics` | `AnalyticsController` (manager/admin) |
| `/employee/analytics/data` | `AnalyticsController::data()` — JSON feed for the range selector (manager/admin) |
| `/employee/orders` | `AnalyticsController::orders()` (manager/admin) |
| `/employee/orders/status/{id}/{status}` | `AnalyticsController::orderStatus()` |
| `/employee/attendance` | `AttendanceController` (all employees) |
| `/employee/attendance/in\|out` | `AttendanceController::timeIn()\|timeOut()` |
| `/employee/manage/users` | `ManagementController::users()` (admin) |
| `/employee/manage/employees` | `ManagementController::employees()` (admin) |
| `/employee` / `/employee/signin` / `/employee/signout` | `EmployeeAuthController` |
| `/pos` / `/pos/complete` | `PosController` (cashier/admin) |
| `/inventory/stocks` | `StockController` (admin/manager/logistics) |
| `/inventory/stocks/new` | `StockController::newSKU()` |
| `/inventory/stocks/edit/{id}` | `StockController::edit()` |
| `/inventory/stocks/add/{id}` | `StockController::addStock()` |
| `/inventory/stocks/remove/{id}` | `StockController::remove()` |

## Project structure

```
application/
├── controllers/       # HomeController, BrowseController, StockController, ...
├── libraries/         # Toastify.php — flashdata-driven Bootstrap toasts
├── models/            # PartsModel.php — tech_parts queries
├── views/
│   ├── templates/     # header, footer, navbars (customer + employee)
│   └── pages/         # home, browse, inventory, employee dashboard
assets/                # Bootstrap, Chart.js, toast.js, style.css
system/                # CodeIgniter 3 core (do not modify)
tests/                 # PHPUnit tests (see Testing)
vendor/                # Composer dependencies incl. PHPUnit
```

## Testing

The app code depends on CodeIgniter's global singleton (`get_instance()`, `$this->db`, `$this->session`), which is hard to boot in isolation. Instead of loading the whole framework, the test suite uses a **stub bootstrap**: fake `CI_Controller` / `CI_Model` classes plus lightweight fakes for the session and database layer.

### Running the tests

```bash
vendor/bin/phpunit     # or: composer test
```

Run a single class or method:

```bash
vendor/bin/phpunit --filter ToastifyTest
vendor/bin/phpunit --filter testRenderEscapesHtmlInMessage
```

The suite is **not currently green**: 111 tests, 10 errors and 1 failure, all pre-existing and unrelated to recent work. Two of the causes are test doubles that have fallen behind the app (`FakeDb` missing `order_by()`, an `addStock()` arity change, and `EmailControllerTest` still exercising an API that moved to helpers when the old controller was retired). Treat these as the baseline rather than as regressions.

Not covered at all yet: the spreadsheet user import (`UserImporter`, `ManagementController::importUsers`) and the email composer. A release gate needs `tests/libraries/UserImporterTest.php` at minimum, since the row validation and the bcrypt 72-byte cut-off are pure logic and cheap to test.

### How it works

```
phpunit.xml.dist              # points PHPUnit at the bootstrap, registers test suites
tests/bootstrap.php           # defines BASEPATH/APPPATH, stubs CI_Controller/CI_Model/get_instance(),
                              #   provides FakeSession, FakeDb, FakeLoader/Input/Validation/Config,
                              #   a redirect() stub, and loads the real app classes
tests/support/FakeDb.php      # FakeDb: records where() calls; FakeDbResult: returns canned rows;
                              #   set $db->failQuery = TRUE to simulate a failed query
tests/libraries/ToastifyTest.php
tests/models/                 # PartsModelTest, UserModelTest (SQLi binding, edge cases, concurrency)
tests/security/               # AuthController + MY_Controller auth, form-rules & route integrity
tests/controllers/            # render + error-path tests for the page controllers
tests/helpers/                # check_session() and is_invalid() helper tests
```

Key idea from `tests/bootstrap.php`: a plain `CI_Controller` stub stores itself as the singleton, so when `Toastify` calls `get_instance()` it finds our fake controller with a `FakeSession` attached. Likewise, `PartsModel` extends the stubbed `CI_Model`, whose magic `__get` pulls `$this->db` from the same instance — where we've attached a `FakeDb`.

Controllers extend `CI_Controller`/`MY_Controller`, whose constructors validate the session before any method runs. `make_my_controller()` injects the `FakeSession`/`FakeToastify`/`FakeDb`/`FakeLoader` before invoking the constructor, so authorization and `render()` behaviour is testable. `redirect()` is stubbed: it records the destination (assert via `redirect_target()`) and, unless `redirect_stop(FALSE)` is called, throws a `RedirectException` to mimic CI halting the request.

### Writing a new test

Tests follow the **Arrange–Act–Assert** pattern:

```php
use PHPUnit\Framework\TestCase;

class MyModelTest extends TestCase {

    private $model;

    protected function setUp(): void {
        // Arrange: fresh CI instance + fakes per test
        $ci = new CI_Controller();
        $ci->db = new FakeDb();
        $ci->db->rows = [['id' => 1, 'name' => 'Screen']]; // what get() will return
        $this->model = new MyModel();
    }

    public function testSomething() {
        // Act
        $result = $this->model->getSomething();

        // Assert
        $this->assertSame('Screen', $result[0]['name']);
    }
}
```

Tips:

- One `setUp()` per test keeps tests isolated — state never leaks between them.
- Use `$this->db->whereCalls` to assert *which* filters were applied, and `$db->lastTable` to assert which table was queried.
- Set `$this->db->failQuery = TRUE;` then `expectException(Exception::class)` to test error paths.
- Save the file under `tests/libraries/` or `tests/models/` — both directories are registered in `phpunit.xml.dist`.

## Troubleshooting

- **"Access denied for user"** — check `DATABASE_USERNAME` / `DATABASE_PASSWORD` in `.env` match your server.
- **Tables not found** — the database name in `.env` must match the one you imported into; re-run `mysql -u root circuit_cart < db_schema.sql`.
- **Images fail to upload** — confirm `storage/booking/` and `storage/bugs/` are writable by the web server user.
- **User import fails** — confirm `storage/tmp/` is writable. Only `.xlsx`, `.xls` and `.csv` are accepted (macro-enabled `.xlsm` is rejected), up to 5 MB and 2000 data rows. Download the sample file from the import modal for the exact column layout.
- **Base URL wrong in links** — set `BASE_URL` in `.env` to match the folder the app is served from.

## Releases

`.github/workflows/release.yml` cuts a version on every push to `main`. There is no version to bump by hand: the workflow reads the commits since the last tag and picks the bump itself, writes it to `index.php` and `.env.example`, commits, tags, opens a release, and mirrors the version onto `dev`.

The bump is the highest of:

| Signal | Bump |
| --- | --- |
| `!` after the type (`feat(api)!:`) or `BREAKING CHANGE:` in the body | major |
| `feat:` / `feature:`, or a subject opening with Add, Implement, Introduce, Create, Build, Support, Enable, Develop | minor |
| anything else, including a subject it cannot place | patch |

Because dev reaches main as a squash merge, **only the prefix on the squash subject matters** — that single commit is all the classifier sees.

Two consequences worth knowing:

- Enabling the workflow releases it. The commit that adds or edits `release.yml` is itself a push to `main`, so it produces a version containing no functional change.
- A manual re-run is a no-op. If the tip of `main` is already a `Release vX.Y.Z` commit, the workflow exits successfully without cutting anything.

The dropdown on a manual run still works and overrides the derived bump, for when the commits do not tell the story.