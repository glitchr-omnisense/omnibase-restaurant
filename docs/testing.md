---
title: "Testing"
order: 60
---

# Testing

## Running the suite

Inside a host application:

```bash
php vendor/bin/phpunit -c vendor/omnibase/restaurant/phpunit.xml.dist
```

In omnisense/omnibase-harness, mounting the checkout in place of the
installed package:

```bash
docker run --rm --network none \
    -v "$PWD":/srv/demo/vendor/omnibase/restaurant:ro \
    omnisense/omnibase-harness:latest test restaurant
```

On this checkout, in the harness:

```text
Tests: 69, Assertions: 462, Skipped: 11.
```

The skipped tests are the platforms' (`OrderTicketsTest`,
`PlatformHealthTest`: omnisense/omnifood is not in the image), the reviews'
(`ReviewsTest`: omnisense/omnireview is not either) and the captcha's.

## What the tests check

| Test | Checks |
|---|---|
| `BookingGuardTest`, `TakeHomeGuardTest` | the reservation form and the take-home order are guarded in place of their own trap: a person goes through; a filled trap, a form sent too fast, a form without the captcha's token once it is shown are refused; without a captcha, the trap and the time alone; a wrong address refused by the model |
| `ColdChainTest` | a fresh dish collected tomorrow; local delivery by postcode; a chilled parcel leaves Monday to Wednesday, ordered on a Thursday it waits for Monday; the whole basket must travel and keep; no carrier, no parcel; made to order, past the cut-off; the basket's use-by date is its shortest |
| `EntitiesTest` | a ticket moves one step at a time, never back nor served twice; refused or cancelled; its lines by station; a platform's order late near its deadline; moments in UTC; a bill adds its rounds but not the refused ones; a reservation holds its tables; a service's times; a layout's places, joins and dates; settling a table |
| `EntityNamesTest` | every entity of the back office named singular and plural in each language |
| `OrderTicketsTest` | a platform's order becomes its ticket, its lines keep their choices and prices, one already answered elsewhere arrives where it stands |
| `PlatformHealthTest` | a platform without its keys says which are missing; a failed call marks a configured platform in error until one succeeds; without a cache nothing breaks |
| `RestaurantDemoAccountsTest` | one account for each of the restaurant's people, the roles configured, nobody above the manager, each label and sentence in the four catalogues |
| `ReviewsTest` | the platforms shown are those configured; the latest reviews and those not answered; the page's origin, date and link, no JSON-LD; the tile |
| `SeatingTest` | every time of a service on an empty floor; the smallest table that fits; joined tables; the staff may use what is not offered online; held and cancelled reservations; closed days and opening hours; the notice; a service's own limit of covers |
| `Controller\CrudRequiredFieldsTest` | omnibase/admin's `CrudFieldsTestCase` on the screens |
