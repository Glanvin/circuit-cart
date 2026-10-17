# Instructions for coding assistants

## Project purpose

Circuit Cart is an all-in-one technology repair shop platform. It combines an online catalog and checkout, repair appointment handling, in-store point of sale (POS), inventory, and employee operations. The long-term product direction is to connect online sales, in-store sales, bookings, repair work, inventory, customers, and reporting in one dependable system.

This repository already contains working customer shopping and checkout flows, repair bookings, an employee portal with role-based access, POS, stock management, online order fulfillment, analytics, attendance, and administration. Inspect the current implementation before deciding a feature is absent. Prefer extending existing conventions over replacing the app wholesale.

## Technology and layout

- PHP application built on CodeIgniter 3.
- MySQL/MariaDB database. `db_schema.sql` is the canonical plain schema (structure only) and `db_seed.sql` the canonical demo seed data source; import the schema first, then the seed.
- Bootstrap 5 UI, with local assets under `assets/`.
- `application/controllers/` contains request handling; `application/models/` contains data access; `application/views/` contains templates and pages.
- `application/config/routes.php` defines routes. `application/config/roles.php` defines employee roles and landing pages; keep these in sync with controller authorization.
- `application/core/MY_Controller.php` contains shared controller behavior and authorization/session handling.
- `tests/` contains PHPUnit tests using a lightweight CodeIgniter stub bootstrap.
- `system/` is the CodeIgniter framework. Do not modify framework files to implement application features.

Read `README.md`, relevant controllers/models/views, route and role configuration, and `db_schema.sql` before making changes. Check for local or feature-specific instructions before editing a subdirectory.

## Product and workflow guidance

- Keep online checkout and POS sales consistent with a single source of truth for catalog prices and stock.
- Treat bookings and repair jobs as related but distinct stages: a requested appointment may need approval, while an accepted repair should have trackable diagnosis, estimate/approval, parts and labor, status, and customer communication. Inspect existing behavior before extending this workflow.
- Model state changes explicitly and preserve a useful history for important operations such as order status, repair progress, refunds, and inventory adjustments.
- Keep customer experiences and staff workflows clear and responsive. Reuse the existing Bootstrap layout and templates where practical.
- Implement large product goals incrementally. For broad requests, first summarize the current state, identify gaps, and propose a prioritized plan; then work in focused, reviewable changes unless the user asks for a different approach.

## Implementation conventions

- Follow the project’s existing CodeIgniter 3 patterns and PHP style. Avoid introducing a new framework or major dependency without a concrete need.
- Put database access in models where the existing code does so. Use query bindings/query builder and server-side validation; never trust client-submitted prices, totals, roles, ownership, or stock quantities.
- Escape dynamic output in views. Protect state-changing requests with the app’s CSRF and authorization conventions. Check both authentication and role authorization for every staff action, including direct route access.
- Use database transactions for operations that must succeed or fail together, especially checkout, POS completion, refunds, and inventory changes.
- Keep stock movements and money-related records auditable. Do not silently overwrite or delete records needed for sales, repair, or inventory history.
- Make database schema changes in `db_schema.sql` and seed/demo data changes in `db_seed.sql`, and provide a safe upgrade path for existing installations when a change requires it. Preserve existing data and maintain compatibility with supported MySQL/MariaDB behavior.
- Do not store payment card numbers or security codes. Use a payment provider for card processing if that capability is requested.
- Keep secrets and environment-specific credentials out of source control. Use the existing `.env` configuration approach.
- Update README documentation, routes, role configuration, and seed/demo data when a feature changes those areas.
- Keep changes focused. Do not reformat or rewrite unrelated files.

## Verification and reporting

- Follow the user’s requested verification scope. When asked to test or verify, run the relevant checks and report what was run and the result. Do not claim tests passed if they were not run.
- For database, permissions, checkout, POS, or repair workflow changes, explain any upgrade steps or behavior changes that an operator needs to know.
- In the final response, summarize the concrete changes, point to the relevant files, and state verification results or limitations plainly.
