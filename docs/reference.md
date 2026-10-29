---
title: "Reference"
order: 45
---

# Reference

## Routes

The staff's routes check `restaurant.staff_role` (`ROLE_STAFF`), the floor
editor's `restaurant.manager_role` (`ROLE_ADMIN`), inside the action. The
guests' table routes are reached by the table's token, the reservation's by
its management token.

| Name | Path | Methods | Access |
|---|---|---|---|
| `restaurant_menu` | `/carte` | GET | everyone |
| `restaurant_menu_print` | `/carte/imprimer` | GET | everyone |
| `restaurant_book` | `/reserver` | GET, POST | everyone |
| `restaurant_book_slots` | `/reserver/creneaux` | GET | everyone; `jour`, `couverts` (2 by default); JSON |
| `restaurant_manage` | `/reservation/{token}` | GET, POST | the management token (20 to 64 characters) |
| `restaurant_manage_ics` | `/reservation/{token}/agenda.ics` | GET | the same; `reservation.ics` as an attachment |
| `restaurant_table` | `/t/{token}` | GET | the table's token (16 to 32 characters) |
| `restaurant_table_state` | `/t/{token}/etat` | GET | the same |
| `restaurant_table_round` | `/t/{token}/tournee` | POST | the same; JSON |
| `restaurant_table_call` | `/t/{token}/appel` | POST | the same |
| `restaurant_table_bill` | `/t/{token}/addition` | POST | the same |
| `restaurant_table_pay` | `/t/{token}/payer` | POST | the same; JSON |
| `restaurant_takehome` | `/traiteur` | GET | everyone |
| `restaurant_takehome_dish` | `/traiteur/{slug}` | GET | everyone |
| `restaurant_takehome_order` | `/traiteur/commander` | POST | everyone |
| `restaurant_tracking` | `/suivi/{token}` | GET | the order's tracking token (32 to 43 characters) |
| `restaurant_pass` | `/service` | GET | staff |
| `restaurant_pass_board` | `/service/tableau` | GET | staff |
| `restaurant_pass_state` | `/service/etat` | GET | staff; JSON |
| `restaurant_pass_ticket` | `/service/ticket/{id}/{action}` | POST | staff; `forward`, `accept`, `start`, `ready`, `serve`, `refuse`, `cancel` |
| `restaurant_pass_table` | `/service/table/{id}/{action}` | POST | staff; `settle`, `answer`, `ordering`, `transfer`, `open` |
| `restaurant_pass_reservation` | `/service/reservation/{id}/{action}` | POST | staff; `confirm`, `refuse`, `seat`, `finish`, `no_show`, `cancel`, `move` |
| `restaurant_pass_book` | `/service/reservation` | POST | staff: a reservation taken at the pass |
| `restaurant_pass_dish` | `/service/plat/{id}/{action}` | POST | staff; `out`, `back` |
| `restaurant_pass_platform` | `/service/plateforme/{name}/{action}` | POST | staff; `pause`, `resume` |
| `restaurant_platform_webhook` | `/restaurant/plateforme/{name}/webhook` | POST | the platform, by its signature ([The platforms](platforms.md)) |
| `restaurant_admin_floor` | `/admin/restaurant/salle` | GET | manager |
| `restaurant_admin_floor_room` | `/admin/restaurant/salle/nouvelle` | POST | manager: a new room |
| `restaurant_admin_floor_layout` | `/admin/restaurant/salle/{room}/disposition` | POST | manager: a new layout of a room |
| `restaurant_admin_floor_save` | `/admin/restaurant/salle/{room}/enregistrer` | POST | manager: the floor editor's save |
| `restaurant_admin_posters` | `/admin/restaurant/affiches` | GET | manager: the QR posters |
| `restaurant_admin_table_token` | `/admin/restaurant/table/{id}/jeton` | POST | manager: a table's new token (the old poster stops working) |

The pass's taps are POSTs with the pass's token, answered with the new state
when the page's script asks, with the page otherwise ([The pass](pass.md)).

## Commands

| Command | Options | Does |
|---|---|---|
| `restaurant:menu:push` | `--photos=<public address of the drawings>`, `--dry-run` | sends the menu to the delivery platforms (with omnisense/omnifood) |
| `restaurant:reservations:sync` | `--days=30` | reads the booking platforms' reservations into the book |

## Events

| Event | Carries | Listened to by |
|---|---|---|
| `Base\Restaurant\Event\TicketMovedEvent` | the ticket, the status it came from (null: new), a reason, whether the platform moved it | `PlatformSubscriber` (tells a platform's order: accepted, denied, ready), `TakeawayListener` |
| `Base\Restaurant\Event\DishAvailabilityEvent` | the dish, whether it is available | `PlatformSubscriber` (out-of-stock pushed to the platforms) |
| `Base\Restaurant\Event\ReservationChangedEvent` | the reservation, its previous status (null: new), whether the platform changed it | `PlatformSubscriber` |

omnibase/marketplace's events too: `TakeawayListener` turns a hand-over
received (`PickupChangedEvent`: its order is paid) into a ticket on the pass,
and moves the hand-over its customer follows when the ticket moves;
`QuickOrderListener` settles the table's bill a paid quick order pays
(`OrderPaidEvent`, a card payment come back from the provider) and ends a
quick order on the restaurant's own page (`QuickOrderDoneEvent`: the table's
page for a bill, the page after a take-home order). `RevisionListener`
(Doctrine's `onFlush` and `postFlush`) grows the revision the pass watches
whenever anything of the restaurant is written, so the pass redraws.

## Twig functions

| Function | Returns |
|---|---|
| `restaurant_reviews()` | the places of omnisense/omnireview, with their rating and latest reviews; none without it |
| `restaurant_reviews_limit()` | how many reviews a place shows (3 by default) |
| `restaurant_review_widget(gateway, ...)` | the family's own widget for a platform ([What guests say elsewhere](reviews.md)) |
