# Copilot instructions for stockmanager

Purpose: short, actionable guidance for future Copilot CLI / assistant sessions working on this repository.

-- Build, test, lint commands

- PHP / backend
  - Install dependencies: composer install
  - Common composer scripts (project root):
    - composer run-script setup  (runs install, generates key, migrates, builds assets)
    - composer run-script dev    (starts Laravel serve, queue listener, pail, vite via concurrently)
    - composer run-script test   (clears config and runs tests via `php artisan test`)
  - Run a single PHPUnit test (class or method):
    - php artisan test --filter "Tests\\Feature\\MyTest"  
    - or using phpunit binary: vendor\bin\phpunit --filter "MyTest::test_methodName"
  - Lint / code style: vendor\bin\pint (or composer exec -- vendor/bin/pint)

- Frontend
  - Install: npm install
  - Dev server: npm run dev
  - Build assets: npm run build

- Docker / compose
  - Dev compose: docker compose -f compose.dev.yaml up --build -d
  - Prod compose: docker compose -f compose.prod.yaml up --build -d

- Artisan helpers referenced in docs
  - php artisan stock:monitor-drop --threshold=15
  - php artisan app:run-backup [--now]

-- Tests environment notes

- phpunit.xml config uses sqlite in-memory (DB_CONNECTION=sqlite, DB_DATABASE=:memory:). Tests run without an external DB when run normally.
- Tests live under tests/Unit and tests/Feature.

-- High-level architecture (big picture)

- Framework: Laravel (app/ directory), PHP >=8.4. Frontend built with Vite + Tailwind + Bootstrap.
- Layered/Service architecture:
  - Controllers: HTTP entry points and request validation (FormRequest).
  - Services: application/business logic lives in app/Services (SaleService, StockService, SupplierService, etc.). Controllers delegate to Services.
  - Models/Eloquent: app/Models represent persistence; stock movement audit is captured in stock_movements.
- Concurrency & data integrity:
  - Critical operations use DB::transaction and lockForUpdate() to prevent race conditions on stock updates.
- Background & scheduling:
  - Scheduler + artisan commands handle monitoring (stock:monitor-drop), backups (Spatie backup), and queue jobs.
- DevOps & packaging:
  - Docker multi-stage images (compose.dev.yaml / compose.prod.yaml). Frontend built in separate Node stage, final image is minimal Alpine with PHP runtime.
- CI: GitHub Actions workflows (see .github/workflows/) run Pint (lint), tests and build/push images to registry.

-- Key repository conventions and patterns (repo-specific)

- Service-first pattern: put reusable business logic in app/Services. Avoid putting business rules in Controllers or Models.
- Centralized import module: app/Imports and ImportController handle Excel/bulk imports; they validate external rows before creating models.
- Stock movements audit: every stock change writes into stock_movements table; follow existing helpers for creating movement entries to preserve consistency.
- Route grouping & middleware:
  - Admin/back-office routes are under prefix "admin" and use ensure.back.office middleware.
  - Saler/front-office routes are under prefix "saler" and use ensure.front.office middleware.
  - Note: some GET routes for purchases are intentionally declared before resource() to avoid route parameter conflicts (see routes/web.php comments).
- Tests:
  - Tests assume sqlite :memory: and array/session caches (see phpunit.xml); do not require external services to run unit/feature tests.
- Composer scripts commonly used by maintainers:
  - setup, dev, test — prefer those over running individual commands when setting up or running the project in development.
- Helpers & global functions: app/Helpers/helpers.php is auto-loaded from composer.json; use these helpers when present instead of duplicating logic.

-- Files & places to check when adding features or debugging

- app/Services -> business logic
- app/Imports -> CSV/Excel import logic
- routes/web.php -> route ordering matters for some purchase routes
- phpunit.xml -> test environment overrides (sqlite in-memory)
- compose.dev.yaml / compose.prod.yaml & install/setup scripts (install.bat, setup-dev.bat)
- .github/workflows -> CI definitions (lint/test/build)

-- AI / assistant config files checked

- No CLAUDE.md, AGENTS.md, .cursorrules, .windsurfrules, CONVENTIONS.md, AIDER_CONVENTIONS.md or .clinerules were found at repo root. If adding assistant behaviours, place Copilot-specific guidance here (.github/copilot-instructions.md) and CI-related workflows under .github/workflows.

-- Quick troubleshooting commands commonly referenced in docs

- Check PHP binary inside container: docker compose exec php-fpm php -v
- Verify pg_dump availability (for backups): docker compose exec php-fpm pg_dump --version
- Run a single artisan command in container: docker compose exec php-fpm php artisan migrate --force


---

If something in this file should reference a specific file or developer contact (eg. owners of modules), append short notes under the relevant section.

Generated from README.md, composer.json, package.json and phpunit.xml; keep this file short and focused so Copilot sessions can act reliably.
