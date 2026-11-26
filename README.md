# Parametric Overhead Crane Drawing Generator

A small full stack web app that draws a dimensioned 2D drawing of a single girder overhead (EOT) crane from user inputs. Users can save designs, list them, reopen them and edit them.

Built as a take-home assignment for Modern Crane Co.

## What it does

- Form with the crane inputs (capacity, span, lifting height, runway height, end clearance, wheel diameter, wheelbase and some optional data).
- Live drawing (SVG) while you type: front elevation, plan view, end view, detail Z, technical data and title block.
- Every dimension is labelled. Values that are **not** in the reference drawing are marked **(Estimate)**.
- Save, list, search, reopen, edit and delete designs.
- Download the drawing as an SVG file.

## Stack and why

| Part | Choice | Why |
|---|---|---|
| Backend | Laravel 12 (PHP 8.2+) | I know it well. Validation, routing and tests are built in. |
| Database | SQLite | Just one file, nothing to install. Easy for a reviewer to run. |
| Frontend | Blade + plain JavaScript | The UI only calls the API. No build step, no Node needed. |
| Drawing | SVG built on the server (PHP) | One source of truth. Easy to test and to download. |

## How to run

You need PHP 8.2 or newer (with `pdo_sqlite`) and Composer.

```bash
git clone https://github.com/FireStarterMaria-cloud/crane-drawing-generator.git
cd crane-drawing-generator
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan key:generate
php artisan migrate
php artisan serve
```

The two `php -r` lines create the `.env` file and the empty SQLite database file. They work the same on Windows, Mac and Linux.

Open http://127.0.0.1:8000. If port 8000 is busy, use `php artisan serve --port=8001`.

I tested these steps on a fresh clone on Windows.


## API

All routes are under `/api` and answer in JSON (except the SVG).

| Method | Route | What it does |
|---|---|---|
| GET | `/api/meta/limits` | Limits, defaults and sources. The form is built from this. |
| POST | `/api/designs/preview` | Validates and calculates, returns the drawing. Does not save. |
| GET | `/api/designs?search=&page=` | List designs (10 per page). |
| POST | `/api/designs` | Create a design (201). |
| GET | `/api/designs/{id}` | One design with derived values and drawing. |
| PUT | `/api/designs/{id}` | Update a design (full replace). |
| DELETE | `/api/designs/{id}` | Delete a design (204). |
| GET | `/api/designs/{id}/drawing.svg` | The drawing as SVG. Add `?download=1` to download it. |

Errors: validation errors return **422** with a JSON `errors` object, unknown ids return **404** in JSON.

## Validation

Done on the server in `app/Http/Requests/DesignRequest.php`.

- Range checks for every input. The numbers live in `config/crane.php`.
- Cross-field rules, for example the runway height must be at least lifting height + 400 mm, and the wheelbase must be 10 to 25 percent of the span.
- Soft warnings (not errors), for example a heavy load on a long span for a single girder crane.

## Where the numbers come from

Everything is in `config/crane.php` with a source note, and every derived value in the API response has a `source` and an `estimate` flag.

- **From the reference drawing:** 10 t / 25 m sample, the 25270 overall length, 3800 wheelbase, 4310 end carriage length, 8321 runway height, 7900 lifting height, Min.235 end clearance, rail 50x30, wheel diameter 250, wheel width 75, FEM A5, and the 74.8 kN wheel load for checking.
- **Estimates (marked in the app):** all input ranges, wheelbase ratio (3800/25000 from one drawing), girder depth (span / 20), the +270 and +510 offsets for other sizes, weights for other sizes (scaled from one data point), the wheel load formula (no dynamic factors) and the hook approach minimum.
- The wheel load formula gives 72.6 kN for the sample. The drawing shows 74.8 kN (about 3 percent difference). A test checks this.

## Known limitations

- It is a schematic drawing for offers, not a manufacturing drawing. Trolley, hook, runway beam, wall bracket and girder width are symbols, not to scale.
- Only single girder, top running cranes. No double girder, no jib cranes.
- No user accounts. All designs are shared.
- Weight and wheel load are rough estimates from one reference drawing. Real values need engineering data.
- No section A-A copy, pendant, power feed point or full title block like the reference.
- Not checked against any standard (FEM, ISO). Nothing here is certified.

## Project structure

```
app/Http/Controllers/Api/   DesignController, MetaController
app/Http/Requests/          DesignRequest (validation)
app/Models/Design.php
app/Services/               CraneCalculator (numbers), CraneSvgRenderer (drawing)
config/crane.php            limits, rules and sources
database/migrations/        designs table
resources/views/app.blade.php, public/js/app.js   the UI
tests/                      unit and feature tests
```

## Time spent
Real hours: about 3 hours total (around 2.5 hours of coding, testing, and documentation, plus 15 minutes for the demo video).
(setup 35 min, backend and tests 25 min, first drawing 25 min, UI 20 min, improved drawing 30 min, README, DECISIONS.md & AI_LOG.md 30 min, demo video 15 min)

## AI use

See  `DECISIONS.md` was written by me without AI.