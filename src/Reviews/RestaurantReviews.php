<?php

namespace Base\Restaurant\Reviews;

use Omnipulse\Bridge\Twig\OmnipulseExtension;
use Omnipulse\Model\Place;
use Omnipulse\Model\Review;
use Omnipulse\Registry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * The restaurant on the review platforms (omnisense/omnipulse's reviews):
 * where it is on each (restaurant.reviews.places, by the name of its
 * provider in omnipulse.providers), its latest reviews, those it has not
 * answered. Everything is asked through the family's Twig functions
 * (OmnipulseExtension): kept no longer than each platform allows, nothing
 * when a platform does not answer. A platform whose provider is not
 * configured - no key - is left out, as is a provider of another of the
 * family's kinds (an audience, a funding one) named the same: nothing shows.
 *
 * The lists keep their key "gateway" (the provider's name), which the
 * bundle's templates and the sites' overrides read.
 *
 * Registered only when omnisense/omnipulse is installed; it implements none
 * of its interfaces.
 */
class RestaurantReviews
{
    /**
     * @param array<string, string> $places provider => the restaurant's ID there
     * @param array<string, string> $owners provider => the owner's location (Google Business Profile)
     */
    public function __construct(
        private readonly ?Registry $registry = null,
        private readonly ?OmnipulseExtension $omnipulse = null,
        #[Autowire('%restaurant.reviews.places%')] private readonly array $places = [],
        #[Autowire('%restaurant.reviews.owners%')] private readonly array $owners = [],
        #[Autowire('%restaurant.reviews.limit%')] private readonly int $limit = 3,
    ) {
    }

    /** @return list<array{gateway: string, place: Place}> the platforms the restaurant shows, their provider configured */
    public function places(): array
    {
        if (null === $this->registry || null === $this->omnipulse) {
            return [];
        }
        $shown = [];
        foreach ($this->places as $provider => $id) {
            if ('' !== (string) $id && $this->registry->has($provider) && \in_array('reviews', $this->registry->get($provider)->capabilities()->families(), true)) {
                $shown[] = ['gateway' => $provider, 'place' => new Place((string) $id, null, $this->owners[$provider] ?? null)];
            }
        }

        return $shown;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    /** The family's widget for one platform: its rating and latest reviews, their origin, date and link. */
    public function widget(Environment $twig, string $provider, Place $place, ?int $limit = null): string
    {
        return $this->omnipulse?->widget($twig, $provider, $place, $limit ?? $this->limit) ?? '';
    }

    /**
     * The latest reviews over every platform, newest first.
     *
     * @return list<array{gateway: string, review: Review, answerable: bool}>
     */
    public function latest(int $limit = 5): array
    {
        $all = [];
        foreach ($this->places() as ['gateway' => $provider, 'place' => $place]) {
            if (!$this->registry->get($provider)->capabilities()->reviews) {
                continue;
            }
            $answerable = $this->registry->get($provider)->capabilities()->reply && null !== $place->owner;
            foreach ($this->omnipulse->reviews($provider, $place, max($limit, 5)) as $review) {
                $all[] = ['gateway' => $provider, 'review' => $review, 'answerable' => $answerable];
            }
        }
        usort($all, static fn (array $a, array $b) => ($b['review']->publishedAt?->getTimestamp() ?? 0) <=> ($a['review']->publishedAt?->getTimestamp() ?? 0));

        return \array_slice($all, 0, $limit);
    }

    /**
     * The reviews not answered yet, where the platform says whether one was:
     * the owner's side (Google Business Profile, Trustpilot's business user).
     *
     * @return list<array{gateway: string, review: Review, answerable: bool}>
     */
    public function unanswered(int $limit = 20): array
    {
        return \array_slice(array_values(array_filter($this->latest(50), static fn (array $r) => $r['answerable'] && null === $r['review']->reply)), 0, $limit);
    }
}
