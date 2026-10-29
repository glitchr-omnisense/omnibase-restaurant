---
title: "Configuration reference"
order: 40
---

# Configuration reference

## The `restaurant` tree

Dumped with Symfony's `YamlReferenceDumper` from
`Base\Restaurant\DependencyInjection\RestaurantConfiguration`. The keys are
explained where they act: [Installation](installation.md#configuration), the
reservations in [The room and the reservations](room-and-reservations.md),
the take-home shop in [The take-home shop](take-home.md), the platforms in
[The platforms](platforms.md), the reviews in
[What guests say elsewhere](reviews.md).

```yaml
restaurant:

    # The site's page frame the public pages extend (blocks title, content, stylesheets, javascripts).
    layout:               layout1.html.twig

    # Who runs the pass (/service): seats guests, moves tickets along, settles tables.
    staff_role:           ROLE_STAFF

    # Who edits the floor plan, prints the QR posters, pauses the platforms.
    manager_role:         ROLE_ADMIN

    # The band across the top of the QR posters (a hex colour): the restaurant's own.
    poster_color:         '#432B52'

    # Sender of the reservation mails; null: the mailer's default sender.
    from_email:           null

    # Where a reservation request to confirm is announced; null: nobody is mailed.
    notify_email:         null
    reservation:

        # Minutes between now and the first time a guest may book online.
        notice:               60

        # Days ahead a guest may book online.
        horizon:              60

        # More guests than this: the form says to call.
        max_covers:           8

        # Seconds: a form sent faster than this is a robot's.
        min_delay:            3

        # Minutes before the time a guest may still change or cancel online.
        cancel_until:         120
    table:

        # Guests order from their phone after scanning their table's code; false: the code shows the menu only.
        ordering:             true

        # "Pay from my phone" at the end (needs omnibase/marketplace's quick order); false: the bill is settled at the till.
        pay_online:           false

        # Requests a table's phones may make in a minute (rounds, calls, bill).
        rate_limit:           20

        # Lines a round may hold.
        max_lines:            40

        # Of one dish in one round.
        max_quantity:         20
    takehome:

        # The shop's pages (/traiteur) answer.
        enabled:              true

        # Orders are collected at the restaurant, on a slot (omnibase/marketplace's marketplace.pickup.slots).
        pickup:               true

        # Days the kitchen needs: 1, an order is for tomorrow at the earliest; 0, for today.
        notice_days:          1

        # Of one dish in one order.
        max_quantity:         20
    delivery:

        # Where the restaurant brings orders itself: postcodes, or a prefix ("67*"). Empty: no local delivery.
        zip_codes:            []

        # The smallest basket delivered, in cents before VAT.
        minimum:              0

        # Slug of the product sold as the delivery charge (a line of the order); null: delivered free.
        fee_product:          null
    parcel:
        enabled:              true

        # Slug of the shop's shipping method that carries chilled parcels (its carrier: a glitchr/omnibus gateway, omnibus/chronopost with product "fresh"). Null, or its carrier not configured: no parcel.
        shipping_method:      null

        # Slug of the product sold as the parcel's charge (a line of the order).
        fee_product:          null

        # ISO weekdays a parcel is handed to the carrier: Monday to Wednesday.
        ship_days:

            # Defaults:
            - 1
            - 2
            - 3

        # Days on the road.
        transit_days:         1

        # Days of use-by date left on arrival, at least.
        margin_days:          2

        # With notice_days 0: past this hour, a parcel leaves tomorrow at the earliest.
        cutoff:               '12:00'
    reviews:

        # The restaurant on each platform, by its omnireview gateway's name: {google: "ChIJ...", tripadvisor: "1234567", trustpilot: "<business unit>"}.
        places:

            # Prototype
            gateway:              ~

        # The owner's side of a platform: {google: "accounts/<a>/locations/<l>"} (Business Profile: every review, and whether it was answered).
        owners:

            # Prototype
            gateway:              ~

        # Reviews shown per platform on the restaurant's page.
        limit:                3
    pass:

        # Seconds between two looks at the state by the pass and the guests' phones.
        poll:                 6

        # Seconds before a platform order's deadline the pass turns it red.
        accept_warning:       180
```

Each key is a container parameter (`%restaurant.staff_role%`,
`%restaurant.manager_role%`...).
