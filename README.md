# Stockly

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tests](https://github.com/Gabriele06-local/Stockly/actions/workflows/tests.yml/badge.svg)
![License](https://img.shields.io/badge/license-MIT-green)

**Inventory, orders & customers for small retail — web dashboard + REST API.**

Stockly is a production-style SaaS starter for small shops: sell from any warehouse with
automatic stock handling, track every unit movement, watch margins on a live dashboard,
and integrate anything through a Sanctum-powered REST API.

---

## ✨ Features

**Sell**
- Orders with dynamic line items, tax & discount totals, auto-generated order numbers
- Stock decremented in a `DB::transaction` with row-level locks (no overselling)
- Status workflow `draft → confirmed → paid → shipped → completed`, `cancelled` restores stock
- Payments with balance validation + auto-mark-as-paid

**Stock**
- Multi-warehouse inventory, per-product low-stock thresholds
- Full movements ledger (in / out / adjustment) linked to orders
- Low-stock alerts: dashboard widget, filtered views, API endpoint
- Atomic warehouse-to-warehouse transfers (admin)

**Catalog & customers**
- Products (SKU, prices, costs, categories), search / filter / pagination
- Customers with lifetime value and order history
- Safe deletes: blocked when dependent records exist

**Insights**
- Dashboard KPIs: revenue today & month, **gross profit & margin %**, avg ticket, stock value
- 14-day revenue chart, top products with per-product profit, low-stock & recent-orders tables

**Platform**
- Roles `admin` / `staff` (Policies + middleware), session auth for web, Sanctum tokens for API
- Form Requests everywhere; JSON error shapes (`401 / 403 / 422`) on the API
- 29 automated feature tests, Docker dev stack, Postman collection included

---

## 🚀 Quick start

**Requirements:** PHP 8.3 + Composer *or* Docker. MySQL 8 for Docker mode, SQLite works locally.

### Windows — one click (no Docker)

Double-click **`start-stockly.bat`** (migrate + demo seed on first run), then open:

👉 http://127.0.0.1:8000/login

### Docker (MySQL + Redis)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
# open http://localhost:8000/login
```

### Manual (PHP + SQLite)

```bash
cp .env.example .env
# in .env set: DB_CONNECTION=sqlite
#              DB_DATABASE=database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve          # http://127.0.0.1:8000/login
```

### Demo accounts (seeded)

| Email | Password | Role | Can |
|---|---|---|---|
| `admin@stockly.test` | `password` | admin | everything, incl. deletes, warehouses, transfers |
| `staff@stockly.test` | `password` | staff | sales, customers, products, stock adjustments |

Seed data: 3 warehouses, 10 categories, ~48 products, 35 customers, ~90 orders.

---

## 🔌 REST API

Import [`postman/Stockly.postman_collection.json`](postman/Stockly.postman_collection.json)
into Postman — login auto-saves `{{token}}`. Or with curl:

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"admin@stockly.test","password":"password"}' | cut -d'"' -f4)

# search + paginate products
curl -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  'http://127.0.0.1:8000/api/products?search=Lavazza&per_page=5'

# create an order (transactional, decrements stock)
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  http://127.0.0.1:8000/api/orders \
  -d '{"customer_id":1,"warehouse_id":1,"items":[{"product_id":1,"quantity":2}]}'

# low-stock alerts
curl -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  http://127.0.0.1:8000/api/inventory/low-stock
```

| Method | Endpoint | Notes |
|---|---|---|
| `POST` | `/api/auth/login` | `{email,password,device_name?}` → `{token,user}` |
| `GET` / `POST` | `/api/auth/me`, `/api/auth/logout` | Sanctum |
| `GET` `POST` `GET` `PUT` `DELETE` | `/api/products`, `/api/products/{id}` | `search, category_id, is_active, sort, dir, per_page`; delete = admin only |
| `GET` `POST` `GET` `PUT` `DELETE` | `/api/customers`, `/api/customers/{id}` | `search`; delete blocked with orders (`422`) |
| `GET` | `/api/inventory`, `/low-stock`, `/movements` | `warehouse_id, product_id, low_stock` filters |
| `POST` | `/api/inventory/adjust` | `{product_id, warehouse_id, quantity (!=0), reason?}` |
| `GET` `POST` `GET` | `/api/orders`, `/api/orders/{id}` | `status, customer_id, search` filters |
| `POST` | `/api/orders/{id}/status`, `/api/orders/{id}/cancel` | transitions with stock side-effects |

---

## 🧱 Architecture

```
app/Enums/              Role, OrderStatus, MovementType, PaymentMethod
app/Models/             User, Category, Warehouse, Product, Inventory,
                        InventoryMovement, Customer, Order, OrderItem, Payment
app/Services/           OrderService (transactions), InventoryService (adjust/transfer),
                        DashboardService (KPIs incl. profit & margin)
app/Http/Controllers/   Dashboard, Product, Category, Warehouse, Customer,
                        Order, Inventory, Auth  +  Api/*
app/Http/Requests/      Store/Update* · StoreOrder · AdjustInventory · StorePayment
app/Http/Resources/     Product, Customer, Inventory, Order (+items), Category
app/Policies/           per-model authorization (deletes & transfers = admin)
app/Http/Middleware/    EnsureRole  (role:admin …)
database/               migrations · factories · seeders (demo dataset)
resources/views/        Tailwind + Blade: dashboard, CRUD, JS order builder, inventory
tests/Feature/          Auth · Order · Inventory · Api · Dashboard  (29 tests)
docker/                 PHP 8.3-FPM + Nginx + MySQL 8 + Redis
postman/                ready-to-import API collection
```

Business rules live in **Services** (reused by web + API), HTTP layers only validate
and authorize. Stock writes always go through `InventoryService` inside transactions
with `lockForUpdate()` and append to the movements ledger.

## ✅ Testing

```bash
php artisan test
# 29 tests, 89 assertions: auth & roles, order transactions & rollbacks,
# inventory adjustments & transfers, API shapes & errors, dashboard KPIs
```

CI runs the suite on every push (`.github/workflows/tests.yml`).

## ⚙️ Configuration

`config/stockly.php` (override via env):

| Key | Env | Default |
|---|---|---|
| Low-stock default threshold | `STOCKLY_LOW_STOCK_DEFAULT` | `5` |
| Tax rate | `STOCKLY_TAX_RATE` | `0.22` |
| Currency label | `STOCKLY_CURRENCY` | `EUR` |

## 🔒 Security

- Hashed passwords, session regeneration on login, CSRF on web routes
- Per-device Sanctum tokens, revocable on logout
- Policies + `EnsureRole` middleware; `$fillable` guarding; Eloquent bindings
- Service-level checks: insufficient stock, over-payment, discount bounds
- No stack traces in production (`APP_DEBUG=false`); consistent API error shapes

## 🗺️ Roadmap ideas

PDF invoices · email low-stock digest · barcode/SKU scanner search · CSV import/export ·
multi-currency · audit log. PRs welcome.

## 🤝 Contributing

1. Fork & branch (`feat/...`)
2. `php artisan test` must stay green (Pint style: `./vendor/bin/pint --test`)
3. Open a PR describing the business rule + tests

## 📄 License

MIT — see [LICENSE](LICENSE).
