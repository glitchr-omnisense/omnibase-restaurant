# omnibase/restaurant

A restaurant on [omnibase](https://github.com/glitchr-omnisense/omnibase): one bundle per trade, one site per restaurant.

- **The room**: rooms, tables, named layouts (tables moved, taken out, joined), a floor-plan editor in the back office (SVG, drag on a snapping grid, turn, duplicate, join), a QR poster per table.
- **Services and reservations**: meal services (`MealService`: days, seatings, table durations, automatic confirmation), opening hours and days off from omnisense/omnibase's `OpeningHours`, availability reckoned table by table and taken under a `symfony/lock`, a public form on a model, a management link (move, cancel), an ICS file.
- **The menu**: `Dish` extends omnibase/marketplace's `Product` (price before VAT, VAT, stock), sections, set menus, the EU allergens of omnisense/omnibase, diets, a filter, a printable menu.
- **Ordering at the table**: `/t/{token}` from the table's QR code, no account; rounds (`Ticket`) on the table's bill (`Session`), a dish's options (a cooking, extras); the bill paid from the phone where the site allows it; the waiter called, the bill asked for; `symfony/rate-limiter` per table.
- **The take-home shop** (`/traiteur`): fresh dishes to cook at home (`TakeHome`: how it keeps, its use-by days, how to cook it), the cold chain (`ColdChain`: collected on a slot, brought to the postcodes around, a chilled parcel only when the whole basket travels and keeps, Monday to Wednesday), an order without an account and followed by its link (`/suivi/{token}`), prepared on the pass.
- **The pass** (`/service`): the floor live, the tickets of every channel in columns, the day's book, dishes run out - polled with omnisense/omnibase's `poll` controller.
- **The platforms**, when [omnisense/omnifood](https://github.com/glitchr-omnisense/omnifood) is installed: webhooks made tickets and reservations, the pass's answers sent back, the menu pushed, keys typed in the back office.

```sh
composer require omnibase/restaurant
```

Stable releases follow semantic versioning: this installs the latest 1.x, with omnisense/omnibase 5.1 or later
on Symfony (omnibridge/symfony) and Doctrine ORM (DoctrineBundle), omnibase/admin and omnibase/marketplace. No `minimum-stability` setting is needed.

Under Symfony, register the bundle and its pages, then create its tables:

```php
// config/bundles.php
Base\Restaurant\RestaurantBundle::class => ['all' => true],
Omnifood\Bridge\Symfony\OmnifoodBundle::class => ['all' => true],   // only with omnisense/omnifood
```

```yaml
# config/routes.yaml
restaurant_controller:
    resource: "@RestaurantBundle/src/Controller/Client"
    type: attribute
```

```sh
bin/console make:migration && bin/console doctrine:migrations:migrate
```

First use: in the back office, draw a room and its tables, add a meal service and a few dishes; each table's QR
poster then opens `/t/{token}`, its order page. [docs/installation.md](docs/installation.md) has the rest.

Documentation: [docs/](docs/index.md). Tests: `vendor/bin/phpunit` (no kernel needed).

The order without an account and the bill paid from a phone stand on omnibase/marketplace's quick order, and are loaded only where it exists; what is left is in [docs/roadmap.md](docs/roadmap.md).

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
