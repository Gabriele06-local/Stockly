# Stockly — Retail Inventory & Sales SaaS

Production-style **Laravel 12 + PHP 8.3** app for small retail businesses:
customers, products, categories, warehouses, inventory movements, sales/orders + payments.

- **Stack:** Laravel 12, PHP 8.3, MySQL 8 (Docker), Redis, Eloquent, Sanctum, Blade + Tailwind (CDN), Chart.js
- **Patterns:** Controllers / Services / Form Requests / Policies / Middleware / API Resources, DB transactions, row-level locking
- **Auth:** session (web) + Sanctum tokens (API), roles `admin` / `staff`

## Quick start

### Option A — Docker (MySQL + Redis, recommended)

```bash
cp .env.example .env
# .env already points to mysql/redis hosts for Docker
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
# open http://localhost:8000
```

Demo logins (seeded):

- `admin@stockly.test / password` (admin — can delete, manage warehouses/categories, transfers)
- `staff@stockly.test / password` (staff — sales, inventory adjustments)

### Option B — Local PHP + SQLite (no Docker)

```bash
cp .env.example .env
# edit .env:
# DB_CONNECTION=sqlite
# DB_DATABASE=database/database.sqlite
# CACHE_STORE=database / SESSION_DRIVER=database / QUEUE_CONNECTION=database
php artisan key:generate
php artisan migrate --seed
php artisan serve
# open http://127.0.0.1:8000
```

### Run tests

```bash
php artisan test
# 28 tests: auth, orders/transactions, inventory, API, dashboard
```

## What’s implemented

- **Dashboard** (`/`): revenue today/month, orders, avg ticket, stock value, low-stock count, 14-day revenue chart, top products, low-stock table, recent orders.
- **Products**: CRUD, search (name/SKU), category filter, pagination, stock-by-warehouse, movement history. Staff can create/edit; only admin can delete.
- **Categories / Warehouses**: CRUD (admin-only except view). Warehouse delete blocked if stock/orders exist.
- **Customers**: CRUD, search, orders count, lifetime value. Delete blocked if orders exist.
- **Orders**: create with dynamic lines (JS), tax/discount auto-totals, `DB::transaction` + `lockForUpdate`, automatic stock decrement, `order_number` auto-generated, status workflow (`draft → confirmed → paid → shipped → completed`, `cancelled` restores stock), payments with balance validation + auto-mark paid, cancel with restore.
- **Inventory**: stock by warehouse, low-stock filter (`quantity <= threshold`), manual adjust (+/- with movement log), admin transfer between warehouses (atomic), full movements ledger with morph reference to orders.
- **Low-stock alerts**: dashboard badge + `/inventory?low_stock=1` + API `/api/inventory/low-stock` + row highlighting.
- **Validation & errors**: Form Requests everywhere; web → redirect + session errors; API → `422 { message, errors }`; `401` unauthenticated; `403` policy/middleware; `404` model binding.

## API (Sanctum)

Import `postman/Stockly.postman_collection.json` into Postman. `POST /api/auth/login` auto-saves `{{token}}`.

```bash
# login
curl -s -X POST http://localhost:8000/api/auth/login \
 -H 'Content-Type: application/json' -H 'Accept: application/json' \
 -d '{"email":"admin@stockly.test","password":"password","device_name":"curl"}'

TOKEN=...

# products
curl -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  'http://localhost:8000/api/products?search=Lavazza&per_page=5'

# create order (transactional)
curl -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -H 'Accept: application/json' \
  http://localhost:8000/api/orders \
  -d '{"customer_id":1,"warehouse_id":1,"items":[{"product_id":1,"quantity":2}]}'

# low stock
curl -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  http://localhost:8000/api/inventory/low-stock

# adjust
curl -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  http://localhost:8000/api/inventory/adjust \
  -d '{"product_id":1,"warehouse_id":1,"quantity":10,"reason":"Goods receipt"}'
```

Full endpoint list: `php artisan route:list | grep api/`

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/api/auth/login` | `{email,password,device_name?}` → `{token,user}` |
| GET/POST | `/api/auth/me`, `/api/auth/logout` | sanctum |
| GET/POST/GET/PUT/DELETE | `/api/products`, `/api/products/{id}` | search, category_id, is_active, sort/dir, per_page; delete admin-only |
| GET/POST/... | `/api/customers...` | search; delete blocked with orders (422) |
| GET | `/api/inventory`, `/low-stock`, `/movements` | warehouse_id/product_id/low_stock filters |
| POST | `/api/inventory/adjust` | `{product_id,warehouse_id,quantity(!=0),reason?}` |
| GET/POST/GET | `/api/orders`, `/api/orders/{id}` | status/customer_id/search filters |
| POST | `/api/orders/{id}/status`, `/cancel` | status transitions with stock side-effects |

## Project structure

```
app/Enums/            Role, OrderStatus, MovementType, PaymentMethod
app/Models/           User, Category, Warehouse, Product, Inventory, InventoryMovement, Customer, Order, OrderItem, Payment
app/Services/         OrderService (transactions), InventoryService (adjust/transfer), DashboardService (KPIs)
app/Http/Controllers/ Dashboard, Product, Category, Warehouse, Customer, Order, Inventory, Auth + Api/*
app/Http/Requests/    Store/Update* + StoreOrder + AdjustInventory + StorePayment
app/Http/Resources/   Product, Customer, Inventory, Order(+items), Category
app/Policies/         Product, Order, Customer, Warehouse, Category, Inventory
app/Http/Middleware/  EnsureRole (role:admin / role:admin,staff)
database/             migrations, factories, seeders (User, Category, Demo: 3 warehouses, 48 products, 35 customers, 90 orders)
resources/views/      layouts.app (Tailwind sidebar) + dashboard + CRUD + orders.create (JS lines) + inventory
tests/Feature/        Auth, Order, Inventory, Api, Dashboard (28 tests)
docker/               php/Dockerfile + nginx + php.ini
postman/              Stockly.postman_collection.json
```

## Security notes

- Passwords hashed (`password` cast), session regeneration on login, CSRF on web, Sanctum tokens (per-device, deletable on logout).
- Authorization: Policies (`delete` admin-only, transfers admin-only) + `EnsureRole` middleware available for routes.
- Validation: Form Requests + service-level checks (insufficient stock, over-payment, discount bounds); mass-assignment guarded via `$fillable`; SQL via Eloquent bindings; row locks prevent oversell races.
- Errors never leak stack traces in production (`APP_DEBUG=false`); API always returns JSON shapes.

## Config

`config/stockly.php`: `low_stock_default` (5), `tax_rate` (0.22), `currency` (EUR) — overridable via `STOCKLY_*` env vars.
