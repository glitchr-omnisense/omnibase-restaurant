<?php

namespace Base\Restaurant\Tests\Persistence;

use Base\Contract\Runtime;
use Base\Restaurant\Entity\MealService;
use Base\Restaurant\Entity\Reservation;
use Base\Restaurant\Entity\Table;
use Base\Restaurant\Repository\ReservationRepository;
use Base\Service\ParameterBagInterface;
use Base\Testing\Engines;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;

/**
 * A reservation's hours belong to the place: an instant stored in UTC, entered and shown in the restaurant's zone
 * (base.opening_hours.timezone), never the visitor's. With PHP in Tokyo (the visitor's zone) and the restaurant in
 * Paris, a 20:00 reservation is 19:00 UTC in the database and 20:00 at the restaurant on both engines.
 */
final class ReservationAtThePlaceTest extends TestCase
{
    private static Engines $engines;

    private static string $zone;

    public static function setUpBeforeClass(): void
    {
        self::$zone = date_default_timezone_get();
        self::place();
        self::$engines = Engines::of([Reservation::class, MealService::class, Table::class]);
        // Typed by a visitor in Tokyo: "20:00" is the restaurant's 20:00.
        date_default_timezone_set('Asia/Tokyo');
        self::$engines->seed(new Reservation(new \DateTimeImmutable('2026-03-01 20:00:00'), 2, 'Léa'));
    }

    public static function tearDownAfterClass(): void
    {
        Runtime::set(ParameterBagInterface::class, null);
        Runtime::set(ClockInterface::class, null);
        date_default_timezone_set(self::$zone);
    }

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return Engines::backends();
    }

    protected function setUp(): void
    {
        self::place();
        date_default_timezone_set('Asia/Tokyo');
        Runtime::set(ClockInterface::class, new MockClock('2026-03-01 09:00:00', 'UTC'));
    }

    /** The restaurant is in Paris (base.opening_hours.timezone). */
    private static function place(): void
    {
        Runtime::set(ParameterBagInterface::class, new class implements ParameterBagInterface {
            public function get(string $key = '', ?array $bag = null): array|bool|string|int|float|\UnitEnum|null
            {
                return 'base.opening_hours.timezone' === $key ? 'Europe/Paris' : null;
            }

            public function set(string $path, array|bool|string|int|float|\UnitEnum|null $value, ?array &$bag = null): void
            {
            }

            public function has(string $path, ?array $bag = null): bool
            {
                return 'base.opening_hours.timezone' === $path;
            }

            public function all(): array
            {
                return ['base.opening_hours.timezone' => 'Europe/Paris'];
            }
        });
    }

    public function testStoredAsTheUtcInstant(): void
    {
        self::assertSame('2026-03-01 19:00:00', self::$engines->pdo()->query('SELECT startsAt FROM restaurant_reservation')->fetchColumn(), '20:00 in Paris (winter) is 19:00 UTC');
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testTwentyHoursStaysTwentyHoursAtTheRestaurant(string $backend): void
    {
        $reservations = new ReservationRepository(self::$engines->finders($backend));

        $day = $reservations->onDay(new \DateTimeImmutable('2026-03-01'));
        self::assertCount(1, $day, "the restaurant's day, its own midnights (not Tokyo's)");
        self::assertSame('20:00', $day[0]->getTime());
        self::assertSame('2026-03-01', $day[0]->getDay());
        self::assertSame('Europe/Paris', $day[0]->getStartsAt()->getTimezone()->getName());
        self::assertSame('21:30', $day[0]->getEndsAt()->format('H:i'));
        self::assertTrue($day[0]->overlaps('2026-03-01 19:00', '2026-03-01 20:30'), "compared as the restaurant's wall clock");
        self::assertSame([], $reservations->onDay(new \DateTimeImmutable('2026-03-02')));
    }
}
