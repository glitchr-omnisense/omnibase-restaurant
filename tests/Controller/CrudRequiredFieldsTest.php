<?php

namespace Base\Restaurant\Tests\Controller;

use Base\Admin\Test\CrudFieldsTestCase;
use Base\Restaurant\Controller\Admin\Crud\DishCrudController;
use Base\Restaurant\Controller\Admin\Crud\SetMenuCrudController;
use Base\Restaurant\Controller\Admin\Crud\TakeHomeCrudController;

/**
 * The fields marked required on this bundle's back office screens, against
 * what its records need (see CrudFieldsTestCase): only those the record
 * cannot do without, all of them, an optional one able to take an empty
 * value, one field per property, a slug following its title.
 */
final class CrudRequiredFieldsTest extends CrudFieldsTestCase
{
    protected static function sourceDirectory(): string
    {
        return __DIR__.'/../../src';
    }

    protected static function requiredByTheScreen(): array
    {
        return [
            DishCrudController::class => [
                'parent' => "a product is sold in a store: its page, its basket and its orders are the store's",
            ],
            SetMenuCrudController::class => [
                'parent' => "a product is sold in a store: its page, its basket and its orders are the store's",
            ],
            TakeHomeCrudController::class => [
                'parent' => "a product is sold in a store: its page, its basket and its orders are the store's",
            ],
        ];
    }
}
