<?php

namespace Base\Restaurant\Repository;

use Base\Service\ParameterBagInterface;
use Base\Database\Type\Utc;
use Base\Contract\Runtime;
use Base\Restaurant\Entity\Reservation;
use Base\Restaurant\Enum\ReservationStatus;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * Reservations are kept in the restaurant's wall-clock time: days and
 * hours are compared as written ("2026-10-04 20:00"), never through a time zone.
 *
 * @method Reservation|null find($id)
 * @method Reservation|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Reservation> findAll()
 * @method list<Reservation> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 * @method mixed maxOfUpdatedAtBy(array $criteria) the latest change, as the database gives it
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Reservation::class);
    }

    public function findOneByToken(string $token): ?Reservation
    {
        return '' === $token ? null : $this->findOneBy(['token' => $token]);
    }

    public function findOneByExternal(string $source, string $ref): ?Reservation
    {
        return $this->findOneBy(['source' => $source, 'externalRef' => $ref]);
    }

    /** @return list<Reservation> that day's, by time, whatever their status */
    public function onDay(\DateTimeInterface $day): array
    {
        [$from, $to] = self::bounds($day);

        // The hand-written query fetch-joined the tables (addSelect): they load when read now.
        return $this->findBy([
            Group::of(Parser::SEPARATOR_AND,
                Criterion::of('startsAt', $from, Parser::OPTION_GREATER_EQUAL),
                Criterion::of('startsAt', $to, Parser::OPTION_LOWER),
            ),
        ], ['startsAt' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Reservation> that day's still holding their tables (asked, confirmed, seated) */
    public function holdingOn(\DateTimeInterface $day): array
    {
        return array_values(array_filter($this->onDay($day), fn (Reservation $r) => $r->holds()));
    }

    /** @return list<Reservation> asked for and not answered yet, from today on */
    public function toConfirm(\DateTimeInterface $today): array
    {
        return $this->findBy([
            'status' => ReservationStatus::REQUESTED,
            Group::of(Parser::SEPARATOR_AND, Criterion::of('startsAt', self::bounds($today)[0], Parser::OPTION_GREATER_EQUAL)),
        ], ['startsAt' => 'ASC']);
    }

    public function coversOn(\DateTimeInterface $day): int
    {
        return array_sum(array_map(fn (Reservation $r) => $r->getCovers(), $this->holdingOn($day)));
    }

    /** Microseconds of the last change, for the pass's polling. */
    public function revision(): int
    {
        $last = $this->maxOfUpdatedAtBy([]);
        if ($last && !$last instanceof \DateTimeInterface) {
            $last = new \DateTimeImmutable($last, Utc::zone());
        }

        return $last ? (int) $last->format('Uv') : 0;
    }

    /** @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} the day's bounds: its midnights at the place (its zone, base.opening_hours.timezone) */
    public static function bounds(\DateTimeInterface $day): array
    {
        $zone = Runtime::find(ParameterBagInterface::class)?->get('base.opening_hours.timezone');
        $from = new \DateTimeImmutable($day->format('Y-m-d').' 00:00:00', new \DateTimeZone(\is_string($zone) && \in_array($zone, timezone_identifiers_list(), true) ? $zone : 'Europe/Paris'));

        return [$from, $from->modify('+1 day')];
    }
}
