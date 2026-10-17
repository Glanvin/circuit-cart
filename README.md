# Circuit Cart

An all-in-one web app for a tech repair shop — customers browse the parts catalog (phone/laptop screens, batteries, Windows licenses, etc.) and check out, while employees manage inventory, stock and orders.

Built on **CodeIgniter 3** with **Bootstrap 5**, **MySQL**, and a custom **Toastify** flash-notification library.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database](#database)
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
- Promo codes: shoppers apply a code on the cart page; the discount is applied to the subtotal before tax and re-validated server-side at checkout (minimum subtotal, total and per-user limits, and start/expiry windows)
- Browse pagination and price-range filter: the catalog loads 12 products per page with Previous/Next controls that keep the active search, filters and sort, plus a server-side min/max price range
- Product detail gallery and recommendations: extra product photos appear as clickable thumbnails that swap the main image, the page shows the live stock count with a low-stock warning at 5 or fewer, and a "You might also need" row lists up to four related parts from the same category or device type
- Order history with self-service cancellation: a customer can cancel their own order while it is still `processing`; cancelling restocks the items and records the change in the audit history
- Wishlist: a signed-in shopper can save a part from any browse card or the product detail page with a heart button and see it all under **My Wishlist** in the navbar; saved parts stay in sync with the live catalog price and stock
- Product reviews: signed-in shoppers rate a part 1–5 stars with a title and comment, one review per product (re-submitting updates it), and reviews post live on the product page with an average rating; a reviewer with a delivered order containing the part is badged **Verified purchase** (computed server-side, never trusted from the client)
- Booking review follows the repair lifecycle: a `pending` request can be approved, rescheduled or declined. Approving (or rescheduling) opens the repair job straight away; an approved/rescheduled booking is then **Picked Up**, which assigns the acting technician to the job and moves the booking to `picked`; only then can it be **Mark Finished**, which closes the job. A finished booking can be **Mark Unfinished**, reopening both the booking and its job. The booking row links to the repair job throughout, and no duplicate job is created if a booking is finished again
- Repair tracking portal: every repair job has an unguessable public link (`/repair/status/{token}`) showing the device, diagnosis, estimate and a live progress timeline — no login required
- Estimate approval by email: when a technician records a diagnosis with an estimate, the customer is emailed a tokenised review link (valid 14 days) where they can approve, or decline with a reason, without signing in
- Service catalog at booking: the booking form offers an optional list of repair services (with a starting-price hint) managed by staff; the chosen service is stored on the booking, and the hint is display-only — the amount actually billed is built from parts and labor on the job
- Self-service appointments: a customer can cancel or reschedule their own booking from **My Appointments** (owner-only, with a 24-hour cutoff before the appointment and only while it is still cancellable); cancelling closes any linked repair job and returns used parts to stock, and every change is written to the booking history timeline

**Employee side**

- Separate employee sign-in using a 6-digit employee ID + password
- Role-based portal: cashier (POS), manager (analytics & order fulfilment), technician/developer (bookings / bug reports), logistics (inventory), admin (everything)
- POS cash register: record store sales, automatic stock decrement, exact-change computation, receipt numbers; the cart mirrors the server's live tax rates (no hard-coded VAT) and a lone cash tender follows the amount due. The page reloads after the receipt is dismissed so stock levels refresh
- POS refund/void: look up a sale by receipt (or pick from recent sales) and reverse it — the items are returned to stock through a `refund` stock movement, and the reversal (amount, reason, employee, timestamp) is stamped on the sale and its JSON history. Refunds over ₱2,000 need a manager or admin session
- Analytics with locally-bundled Chart.js: store vs. delivered-online sales, user registrations, attendance, low-stock — export each chart as PNG/PDF (`jsPDF` via CDN); the range selector redraws charts, KPIs and tables in place via AJAX (`/employee/analytics/data`)
- Online order fulfilment: move orders from processing → shipped → delivered / cancelled; cancelling restocks the order, and only `delivered` online sales count toward analytics
- Review moderation: managers and admins see all product reviews under **Employee Portal → Reviews**, filter by status (published/hidden) or search title/body/username, and hide or delete a review (hiding keeps the record for history)
- Repair jobs: a guarded status workflow (diagnosis → estimate → approved → in progress → ready for pickup → completed, with cancellation allowed until the job is closed) where every change is timestamped and appended to the job history. Managers, admins and technicians can work the queue. Technicians can be assigned or reassigned from the job page, and cancelling a job returns any recorded parts to stock through a `return` movement tagged with the acting employee
- Repair payments and deposits: staff record a deposit or final payment against a job from the job page (amount, tender, reference, and deposit/payment type); the page shows the recorded total (parts + labor), the amount paid and the outstanding balance, and a device cannot be marked picked up while a balance remains — a manager or admin can explicitly release it "on account", which is written to the job history
- Repair service management: managers and admins maintain the booking service catalog (create, edit, activate/deactivate) under **Employee Portal → Services**
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
3. Create the database and import the schema and seed data. `db_schema.sql` creates every table the app uses (structure only); `db_seed.sql` then seeds the demo catalog, employees, customer account, promo codes, a demo sales history and the demo repair workflow (bookings, repair jobs and payments, wishlist, reviews, stock movements and bug reports). Import `db_schema.sql` first, then `db_seed.sql`:
   ```bash
   mysql -u root circuit_cart < db_schema.sql
   mysql -u root circuit_cart < db_seed.sql
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
5. Ensure `storage/booking/`, `storage/bugs/`, `storage/products/` and `storage/tmp/` exist and are writable. Device photos are stored in the first (5 MB max each); bug-report screenshots go in the second (3 MB max each, up to 3 per report, signed-in users only); product images go in `storage/products/` (JPG, PNG, or WebP, up to 5 MB); the last holds spreadsheets uploaded through the admin user import and is cleared after every import. These folders are git-ignored.
6. Open `http://localhost/ci3/` in your browser.

### Local tmux launcher

On Linux with tmux, OpenCode, and XAMPP installed, run `./xstart` from the repository (or invoke the script by its path). It opens a `circuit-cart` tmux session with `git`, `opencode`, and `services` windows. The services window starts Apache and MySQL through XAMPP and may prompt for your sudo password. If XAMPP is installed outside `/opt/lampp`, set `XAMPP_DIR` before running the script.

## Database

`db_schema.sql` contains the plain schema — every table (catalog, accounts, sales, bookings, repair jobs, inventory movements and their lookup tables) with no rows. `db_seed.sql` holds the demo seed data, including a demo sales history (recent store sales and online orders across the last three weeks) so Analytics, POS history and order tracking are populated out of the box. Import the schema first, then the seed.

| Table | Purpose |
| --- | --- |
| `tbl_users` | Customer accounts. `userhistory` is a JSON array of `{action, timestamp}`. |
| `tbl_sku` | Parts catalog. `image_path` stores the uploaded product image path, `compatibility` is a JSON array of supported devices, `technical_specs` a JSON object; `is_active` is a soft-delete flag. |
| `tbl_employees` | Employee accounts, mirroring `tbl_users` but with `attendance_history` (JSON array of `{date, time_in, time_out}`) instead of `userhistory`, plus a hashed `refund_pin_hash` for manager/admin-authorized POS refunds and voids. There is no `username` column — see [Demo accounts](#demo-accounts). |
| `tbl_sales` | POS and online orders. `items`, `payment_methods`, `tax_breakdown` and `history` are JSON arrays. Store sales always count toward analytics; online sales only count when `status = 'delivered'`. `status` is `processing`/`shipped`/`delivered`/`completed`/`cancelled`/`refunded`/`voided`. Refunded/voided store sales carry refund details, `refunded_by` for the acting employee, and `refund_authorized_by` for the approving manager. |
| `tbl_booking` | Repair bookings (`status`: pending/approved/rescheduled/picked/declined/finished), `images` is a JSON array of stored photo paths. |
| `tbl_repair_jobs` | The repair workflow after a booking is approved: diagnosis, estimate, parts/labor, status history. Carries `public_token` (customer status page) and `estimate_token`/`estimate_token_expires_at` (emailed approval link). |
| `tbl_stock_movements` | Append-only audit of every inventory change. `reason` is `sale`/`adjustment`/`receive`/`repair_use`/`refund`/`return`/`count`/`write_off`. |
| `tbl_bug_reports` | Bug reports (`status`: new/in_progress/fixed), `images` is a JSON array. No IP addresses are ever stored. |
| `tbl_password_resets` | Single-use password reset tokens (`token_hash` sha256, `expires_at`, `used_at`). Raw tokens are never stored. |
| `tbl_product_images` | Extra product photos beyond the primary `tbl_sku.image_path` (`part_id`, `image_path`, `sort_order`). Shown as thumbnails on the product detail page. |
| `tbl_promo_codes` | Online checkout promo codes (`discount_type` percent/fixed, `discount_value`, `min_subtotal`, optional `max_total_uses`/`per_user_limit`/`starts_at`/`expires_at`, `used_count`, `is_active`). `tbl_sales.promo_code` and `tbl_sales.discount_total` record the applied code and discount. |
| `tbl_wishlist` | Saved parts, keyed by a unique `(username, part_id)` pair. Rows cascade-delete with the product. |
| `tbl_product_reviews` | Product reviews (`rating` 1–5, `title`, `body`, `status` published/hidden, `order_verified`). One review per `(username, part_id)`; `order_verified` is computed server-side from delivered online orders. |

Note on JSON columns: on MySQL these are native `JSON`; MariaDB implements `JSON` as `LONGTEXT` plus a `json_valid()` check constraint, so the dumped DDL may differ cosmetically from what the schema file declares.

### Upgrading an existing installation

`db_schema.sql` is the canonical plain schema and `db_seed.sql` the canonical seed source: the earlier per-phase upgrade scripts are folded back into them. Import the schema then the seed into a fresh database, or re-run both against an existing one:

```bash
mysql -u root circuit_cart < db_schema.sql
mysql -u root circuit_cart < db_seed.sql
```

The file is idempotent — table creation uses `IF NOT EXISTS` and every seed uses `ON DUPLICATE KEY UPDATE` or a `NOT EXISTS` guard — so re-running it never duplicates or overwrites existing rows. It does not alter tables that already exist, so apply a migration by hand (or rebuild from this file) when upgrading a database created before a schema change.

Seed products ship **without** images. Product photos are store-supplied: upload the primary photo and any gallery photos from **Inventory → Stocks → Edit**. The storefront shows a neutral placeholder until a real photo is uploaded, so replace the placeholder with the exact supplier images before using the catalog for live sales.

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
| `/wishlist` | `WishlistController` — saved parts (signed-in) |
| `/wishlist/toggle/{id}` | `WishlistController::toggle()` — add/remove a saved part |
| `/review/create/{id}` | `ReviewController::create()` — post or update the shopper's product review |
| `/booking` | `BookingController` |
| `/booking/appointments` | `BookingController::appointments()` |
| `/booking/cancel/{id}` | `BookingController::cancel()` — customer cancels own booking (24h cutoff) |
| `/booking/reschedule/{id}` | `BookingController::reschedule()` — customer reschedules own pending booking |
| `/orders` | `OrdersController` |
| `/orders/view/{id}` | `OrdersController::view()` |
| `/orders/cancel/{id}` | `OrdersController::cancel()` — customer self-service cancellation |
| `/repair/jobs` | `RepairJobController` — staff repair job list |
| `/repair/jobs/view/{id}` | `RepairJobController::view()` |
| `/repair/jobs/assign/{id}` | `RepairJobController::assign()` — technician assign/reassign |
| `/repair/jobs/status/{id}` | `RepairJobController::status()` — guarded status change |
| `/repair/jobs/payment/{id}` | `RepairJobController::recordPayment()` — record a deposit/payment |
| `/repair/jobs/pickup/{id}` | `RepairJobController::pickup()` — complete a job (balance must be settled) |
| `/repair/status/{token}` | `RepairStatusController` — public, login-free repair status page |
| `/repair/estimate/{token}` | `RepairStatusController::estimate()` — public estimate review |
| `/repair/estimate/{token}/approve\|decline` | `RepairStatusController::approve()\|decline()` |
| `/bugs` | `BugController` |
| `/storage/booking` | `EmployeeBookingController` |
| `/storage/booking/approve/{id}` | `EmployeeBookingController::approve()` — pending only; opens the repair job |
| `/storage/booking/reschedule/{id}` | `EmployeeBookingController::reschedule()` — pending only; opens the repair job |
| `/storage/booking/decline/{id}` | `EmployeeBookingController::decline()` — pending only |
| `/storage/booking/pick/{id}` | `EmployeeBookingController::pick()` — approved/rescheduled only; assigns the acting technician and moves the booking to picked |
| `/storage/booking/finish/{id}` | `EmployeeBookingController::finish()` — picked only; closes the linked repair job |
| `/storage/booking/unfinish/{id}` | `EmployeeBookingController::unfinish()` — reopens a finished booking (and its job) to picked |
| `/storage/bugs` | `EmployeeBugController` |
| `/storage/bugs/status/{id}/{status}` | `EmployeeBugController::status()` |
| `/employee/dashboard` | `DashboardController` (admin) |
| `/employee/analytics` | `AnalyticsController` (manager/admin) |
| `/employee/analytics/data` | `AnalyticsController::data()` — JSON feed for the range selector (manager/admin) |
| `/employee/orders` | `AnalyticsController::orders()` (manager/admin) |
| `/employee/orders/status/{id}/{status}` | `AnalyticsController::orderStatus()` |
| `/employee/reviews` | `ReviewAdminController` (manager/admin) |
| `/employee/reviews/status/{id}/{status}` | `ReviewAdminController::status()` — publish/hide |
| `/employee/reviews/delete/{id}` | `ReviewAdminController::delete()` |
| `/employee/services` | `RepairServiceAdminController` (manager/admin) |
| `/employee/services/new` | `RepairServiceAdminController::new()` |
| `/employee/services/edit/{id}` | `RepairServiceAdminController::edit()` |
| `/employee/services/toggle/{id}` | `RepairServiceAdminController::toggle()` — activate/deactivate |
| `/employee/attendance` | `AttendanceController` (all employees) |
| `/employee/attendance/in\|out` | `AttendanceController::timeIn()\|timeOut()` |
| `/employee/manage/users` | `ManagementController::users()` (admin) |
| `/employee/manage/employees` | `ManagementController::employees()` (admin) |
| `/employee` / `/employee/signin` / `/employee/signout` | `EmployeeAuthController` |
| `/pos` / `/pos/complete` | `PosController` (cashier/manager/admin) |
| `/pos/lookup` | `PosController::lookup()` — recent sales / lookup by receipt |
| `/pos/refund` | `PosController::refund()` — refund or void a store sale |
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

The suite is green: **155 tests, 4487 assertions** (`vendor/bin/phpunit`). Earlier revisions of this README described a red baseline (111 tests with pre-existing failures and missing `UserImporter` coverage); both have since been resolved — the stale test doubles were updated and `tests/libraries/UserImporterTest.php` now covers the spreadsheet import.

New coverage added for the refund/cancellation and repair-estimate flows lives in `tests/models/SalesModelTest.php` and `tests/models/RepairJobModelTest.php`, alongside the existing `PartsModelTest` / `UserModelTest`. `RepairJobModelTest` also covers repair status transitions, technician assignment, parts reversal on cancellation and pickup timestamps.

### How it works

```
phpunit.xml.dist              # points PHPUnit at the bootstrap, registers test suites
tests/bootstrap.php           # defines BASEPATH/APPPATH, stubs CI_Controller/CI_Model/get_instance(),
                              #   provides FakeSession, FakeDb, FakeLoader/Input/Validation/Config,
                              #   a redirect() stub, and loads the real app classes
tests/support/FakeDb.php      # FakeDb: records where()/update() calls; FakeDbResult: returns canned rows;
                              #   set $db->failQuery = TRUE to simulate a failed query or
                              #   $db->transStatus = FALSE to simulate a failed transaction
tests/libraries/              # ToastifyTest, UserImporterTest (row validation, caps, duplicate detection)
tests/models/                 # PartsModelTest, UserModelTest, SalesModelTest (refund/cancel),
                              #   RepairJobModelTest (estimate tokens)
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
- **Tables not found** — the database name in `.env` must match the one you imported into; re-run `mysql -u root circuit_cart < db_schema.sql` then `mysql -u root circuit_cart < db_seed.sql`.
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
