<?php

namespace Base\Restaurant\Tests;

use Base\Restaurant\Entity\Layout;
use Base\Restaurant\Entity\MealService;
use Base\Restaurant\Entity\Menu\Dish;
use Base\Restaurant\Entity\Menu\MenuSection;
use Base\Restaurant\Entity\Menu\SetMenu;
use Base\Restaurant\Entity\Order\Ticket;
use Base\Restaurant\Entity\Product\TakeHome;
use Base\Restaurant\Entity\Reservation;
use Base\Restaurant\Entity\Room;
use Base\Restaurant\Entity\Table;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * omnibase/admin titles a CRUD screen with its entity's name from the
 * "entities" domain (restaurant.meal_service._singular...): without it a
 * French back office read "Meal service", "Reservation", "Take home".
 */
class EntityNamesTest extends TestCase
{
    public function testEveryEntityOfTheAdminIsNamedSingularAndPluralInEachLanguage(): void
    {
        $entities = [Layout::class, MealService::class, Dish::class, MenuSection::class, SetMenu::class,
            Ticket::class, TakeHome::class, Reservation::class, Room::class, Table::class];

        foreach (['de', 'en', 'fr', 'ja'] as $locale) {
            $catalogue = Yaml::parseFile(\dirname(__DIR__).'/translations/entities+intl-icu.'.$locale.'.yaml');
            foreach ($entities as $class) {
                // Base\Restaurant\Entity\Menu\Dish -> restaurant.menu.dish
                $path = array_map(
                    static fn (string $part): string => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $part)),
                    explode('\\', substr($class, \strlen('Base\\'))),
                );
                unset($path[array_search('entity', $path, true)]);
                $node = $catalogue;
                foreach ($path as $key) {
                    self::assertIsArray($node[$key] ?? null, $class.' is not named in '.$locale);
                    $node = $node[$key];
                }
                foreach (['_singular', '_plural'] as $noun) {
                    self::assertNotSame('', trim((string) ($node[$noun] ?? '')), $class.' has no '.$noun.' in '.$locale);
                }
            }
        }
    }
}
