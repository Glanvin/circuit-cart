# Circuit Cart

An all-in-one web app for a tech repair shop — customers can browse the parts catalog (phone/laptop screens, batteries, Windows licenses, etc.), while employees manage inventory and stock.

Built on **CodeIgniter 3** with **Bootstrap 5**, **MySQL**, and a custom **Toastify** flash-notification library.

## Documentation

| Guide | Contents |
| --- | --- |
| [SETUP.md](SETUP.md) | Requirements and full installation walkthrough (database, schema, demo accounts) |
| [TESTING.md](TESTING.md) | How the PHPUnit suite works, how to run it, how to write new tests |

## Features

- **Customer side**
  - Home page with repair booking pitch and catalog links
  - Browse page: product card grid with client-side filtering by device type and category
  - Online checkout: guests supply a delivery email + address, or create an account to carry the address over
- **Employee side**
  - Separate employee sign-in using a 6-digit employee ID + password
  - Role-based portal: cashier (POS), manager (analytics & order fulfilment), technician/developer (bookings / bug reports), logistics (inventory), admin (everything)
  - POS cash register: record store sales, automatic stock decrement, exact-change computation, receipt numbers
  - Analytics with locally-bundled Chart.js: store vs. delivered-online sales, user registrations, attendance, low-stock — export each chart as PNG/PDF (`jsPDF` via CDN); the range selector redraws charts, KPIs and tables in place via AJAX (`/employee/analytics/data`)
  - Online order fulfilment: move orders from processing → shipped → delivered / cancelled; only `delivered` online sales count toward analytics
  - Attendance: clock in/out with per-employee history stored in `tbl_employees.attendance_history`
  - Admin hub for managing employees (create, role, password reset, activate/deactivate) and customer users (role promotion to admin)
  - Inventory management: SKU CRUD, stock adjustments, soft-removal (`is_active`)

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
tests/                 # PHPUnit tests (see TESTING.md)
vendor/                # Composer dependencies incl. PHPUnit
```
