---
title: What guests say elsewhere
order: 35
---

# What guests say elsewhere

The restaurant's rating and its latest reviews on Google, Tripadvisor or Trustpilot, on its page
(`/carte`, the menu) and on a tile of the back office's dashboard - through
[omnisense/omnipulse](https://github.com/glitchr-omnisense/omnipulse)'s reviews. **Without the family, or
without a key, nothing shows**: no section, no tile, no call.

## Setting it up

```sh
composer require omnisense/omnipulse omnipulse/google-reviews      # omnipulse/tripadvisor, omnipulse/trustpilot as wanted
```

Register `Omnipulse\Bridge\Symfony\OmnipulseBundle` (its Twig functions, the cache), configure its
providers (keys in `.env.local` or the vault), then say where the restaurant is on each, by the
provider's name:

```yaml
# config/packages/omnipulse.yaml
omnipulse:
    providers:
        google: { factory: google-reviews, options: { api_key: '%env(GOOGLE_PLACES_KEY)%', language: fr } }

# config/packages/restaurant.yaml
restaurant:
    reviews:
        places:
            google: 'ChIJ...'                     # the place ID: the one thing Google lets you keep
            # tripadvisor: '1234567'              # its location ID
            # trustpilot: '<business unit id>'
        owners:
            google: 'accounts/<a>/locations/<l>'  # with the owner's access (Business Profile): every review, and whether it was answered
        limit: 3                                  # reviews per platform on the page
```

A platform listed in `places` whose provider is not configured is left out, as is a provider of the
family's other kinds (an audience, a funding platform) that bears the name. The lists the bundle's
templates read keep their key `gateway`: the provider's name.

## On the page

The menu's page ends with `@Restaurant/client/_reviews.html.twig`: a section per platform, drawn by
the family's own widget (`omnipulse_reviews_widget()`, through `restaurant_review_widget()` so that the
bundle's templates compile on a site without the family). The widget always shows the origin, the
date and the link of each review, keeps them no longer than the platform allows (nothing for
Google, a day for Trustpilot), and builds **no JSON-LD**: reviews collected by a third party are not
marked up as the restaurant's own `AggregateRating`.

A site dresses it in `templates/bundles/RestaurantBundle/client/_reviews.html.twig` (the section)
and `templates/bundles/OmnipulseBundle/reviews.html.twig` (each platform, its classes
`omnipulse-reviews__*`), keeping what each platform's terms ask (`omnipulse_terms('google')`: the
attribution, the authors, the links; for
Tripadvisor, a page kept out of search engines).

## The tile

The widget type `restaurant_reviews` lists the latest reviews over every platform, newest first,
and how many wait for an answer - where the platform says whether one was given: Google with the
owner's location, Trustpilot with its business user. A site places it on its dashboard:

```php
yield MenuItem::block('restaurant_reviews', 'Avis', 'fa-solid fa-star')->setSize(4);
```

Without a platform configured, the tile renders nothing.

## From omnisense/omnireview

omnisense/omnireview merged into omnisense/omnipulse (2026-11-10), which replaces it: the
`omnireview` configuration is `omnipulse.providers` (`gateways` is `providers`), the Google
factory `google-reviews` in `omnipulse/google-reviews` (was `google` in `omnireview/google`),
`omnireview_*()` are `omnipulse_*()` (`omnireview_widget()` is `omnipulse_reviews_widget()`), and an
override of the widget moves to `templates/bundles/OmnipulseBundle/reviews.html.twig`, its classes
`omnireview__*` to `omnipulse-reviews__*`. The bundle's own Twig functions, templates and
`restaurant.reviews` settings are unchanged.

## What it does not do

Replying and inviting a guest are the family's (`ReplyInterface`, `InviteInterface`): no screen of
the bundle does it yet. Nothing is stored in the restaurant's tables.
