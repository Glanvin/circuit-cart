# Circuit Cart

An all-in-one web app for a tech repair shop — customers can browse the parts catalog (phone/laptop screens, batteries, Windows licenses, etc.), while employees manage inventory and stock.

Built on **CodeIgniter 3** with **Bootstrap 5**, **MySQL**, and a custom **Toastify** flash-notification library.

## Features

- **Customer side**
  - Home page with repair booking pitch and catalog links
  - Browse page: product card grid with client-side filtering by device type and category
  - Online checkout: guests supply a delivery email + address, or create an account to carry the address over
- **Employee side**
  - Separate employee sign-in using a 6-digit employee ID + password
  - Role-based portal: cashier (POS), manager (analytics & order fulfilment), technician/developer (bookings / bug reports), logistics (inventory), admin (everything)
  - POS cash register: record store sales, automatic stock decrement, exact-change computation, receipt numbers
  - Analytics with locally-bundled Chart.js: store vs. delivered-online sales, user registrations, attendance, low-stock — export each chart as PNG/PDF (`jsPDF` via CDN)
  - Online order fulfilment: move orders from processing → shipped → delivered / cancelled; only `delivered` online sales count toward analytics
  - Attendance: clock in/out with per-employee history stored in `tbl_employees.attendance_history`
  - Admin hub for managing employees (create, role, password reset, activate/deactivate) and customer users (role promotion to admin)
  - Inventory management: SKU CRUD, stock adjustments, soft-removal (`is_active`)

## Requirements

- PHP >= 7.2 (developed on PHP 8.1)
- MySQL / MariaDB
- XAMPP (or any Apache + PHP + MySQL stack)

## Setup

1. Clone the repo into your web root (e.g. `C:/xampp/htdocs/ci3`).
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create a database named `ci3_db` (or edit `application/config/database.php`) and create the `tech_parts` table:
   ```sql
   CREATE TABLE tech_parts (
       part_id        INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       sku            VARCHAR(32) NOT NULL,
       part_name      VARCHAR(128) NOT NULL,
       device_type    VARCHAR(64) NOT NULL,
       category       VARCHAR(64) NOT NULL,
       cost_price     DECIMAL(10,2) NOT NULL,
       selling_price  DECIMAL(10,2) NOT NULL,
       stock_quantity INT NOT NULL DEFAULT 0,
       is_active      TINYINT(1) NOT NULL DEFAULT 1
   );
   ```
4. Add the email-verification flag to the `tbl_users` table:
   ```sql
   ALTER TABLE tbl_users ADD COLUMN verified_email TINYINT(1) NOT NULL DEFAULT 0 AFTER email;
   ```
5. Create the repair-booking table:
   ```sql
   CREATE TABLE tbl_booking (
       id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       username        VARCHAR(20)   NOT NULL,
       device          VARCHAR(128)  NOT NULL,
       issue           TEXT          NOT NULL,
       preferred_date  DATE          NOT NULL,
       preferred_time  TIME          NOT NULL,
       status          ENUM('pending','approved','rescheduled','declined','finished') NOT NULL DEFAULT 'pending',
       images          JSON          NULL,
       created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   If the table was created before the `finished` status existed, migrate it with:
    ```sql
    ALTER TABLE tbl_booking
        MODIFY COLUMN status ENUM('pending','approved','rescheduled','declined','finished') NOT NULL DEFAULT 'pending';
    ```
    If the table was created with the older `image1`/`image2`/`image3` columns, migrate it with:
   ```sql
   ALTER TABLE tbl_booking
       ADD COLUMN images JSON NULL AFTER status,
       DROP COLUMN image1,
       DROP COLUMN image2,
       DROP COLUMN image3;
   ```
6. Ensure `storage/booking/` exists and is writable (device photos are stored there, 5 MB max each). The folder is already git-ignored.
7. Create the bug-report table:
   ```sql
   CREATE TABLE tbl_bug_reports (
       id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       username    VARCHAR(20)   NULL,
       email       VARCHAR(255)  NULL,
       title       VARCHAR(150)  NOT NULL,
       description TEXT          NOT NULL,
       images      JSON          NULL,
       status      ENUM('new','in_progress','fixed') NOT NULL DEFAULT 'new',
       created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   `username`/`email` hold the submitter (guests fill in the email, signed-in users are recorded by username). No IP addresses are ever stored.
8. Ensure `storage/bugs/` exists and is writable (bug-report screenshots are stored there, 3 MB max each, up to 3 per report, only for signed-in users). The folder is already git-ignored.
9. Create the employee and sales tables (or run the bundled `db_schema.sql`, which also seeds 6 demo employees and a customer `demo` account):
   ```sql
   CREATE TABLE tbl_employees (
       employee_id        INT(6) UNSIGNED ZEROFILL AUTO_INCREMENT PRIMARY KEY,
       password           VARCHAR(255) NOT NULL,
       firstname          VARCHAR(50)  NOT NULL,
       middlename         VARCHAR(50)  NULL,
       lastname           VARCHAR(50)  NOT NULL,
       contactnumber      VARCHAR(10)  NULL,
       email              VARCHAR(255) NOT NULL UNIQUE,
       verified_email     TINYINT(1)   NOT NULL DEFAULT 0,
       role               ENUM('cashier','manager','technician','admin','developer','logistics') NOT NULL DEFAULT 'cashier',
       is_active          TINYINT(1)   NOT NULL DEFAULT 1,
       attendance_history JSON         NULL,
       created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
   );

   CREATE TABLE tbl_sales (
       id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       receipt_no        VARCHAR(32)      NOT NULL UNIQUE,
       origin            ENUM('store','online') NOT NULL DEFAULT 'store',
       sold_by           VARCHAR(255)     NULL,
       customer_email    VARCHAR(255)     NULL,
       customer_address  TEXT             NULL,
       items             JSON             NOT NULL,
       subtotal          DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
       total             DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
       status            ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
       created_at        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   Demo accounts — every demo password is `password123`. Employees sign in with
   their 6-digit Employee ID (there is no employee username); the customer signs
   in with username `demo`:

   | Portal | Login | Role |
   |---|---|---|
   | Employee | `000001` | Admin |
   | Employee | `000002` | Cashier |
   | Employee | `000003` | Manager |
   | Employee | `000004` | Technician |
   | Employee | `000005` | Developer |
   | Employee | `000006` | Logistics |
   | Customer | `demo` | Customer (user) |
10. Point your browser at `http://localhost/ci3/`.

> The DB config uses XAMPP defaults (`root`, empty password). Do not ship that to production.

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
tests/                 # PHPUnit tests (see below)
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
