<?php

namespace Base\Restaurant\Controller\Admin\Crud;

use Base\Enum\Allergen;
use Base\Field\BooleanField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Base\Marketplace\Controller\Admin\Crud\ProductCrudController;
use Base\Restaurant\Entity\Product\TakeHome;
use Base\Restaurant\Enum\Keeping;

/**
 * The take-home shop's dishes - fresh, frozen, or kept on a shelf: the
 * shop's product screen (title, text, price before VAT, stock, availability)
 * with how each is kept and for how long, how it is cooked at home, and
 * whether it travels in a chilled parcel.
 */
class TakeHomeCrudController extends ProductCrudController
{
    public static function getEntityFqcn(): string { return TakeHome::class; }

    public static function getPreferredIcon(): ?string { return 'fa-solid fa-snowflake'; }

    public function configureFields(string $pageName): iterable
    {
        yield from parent::configureFields($pageName);

        $allergens = [];
        foreach (Allergen::cases() as $allergen) {
            $allergens['@enums.'.$allergen->trans()] = $allergen->value;
        }
        yield TextField::new('nativeName', '@restaurant.admin.takehome.native')->setRequired(false)->setColumns(4)->hideOnIndex();
        yield IntegerField::new('portions', '@restaurant.admin.takehome.portions')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('netWeight', '@restaurant.admin.takehome.weight')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('shopPosition', '@restaurant.admin.takehome.position')->setRequired(false)->setColumns(2)->hideOnIndex();
        $keepings = [];
        foreach (Keeping::cases() as $keeping) {
            $keepings['@restaurant.'.$keeping->trans()] = $keeping->value;
        }
        yield SelectField::new('keepingName', '@restaurant.admin.takehome.keeping')->setRequired(false)->setChoices($keepings)->setColumns(2);
        yield IntegerField::new('storageTemperature', '@restaurant.admin.takehome.temperature')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('shelfLife', '@restaurant.admin.takehome.shelf_life')->setRequired(false)->setColumns(3);
        yield BooleanField::new('parcel', '@restaurant.admin.takehome.parcel')->setColumns(3);
        yield IntegerField::new('cookingTime', '@restaurant.admin.takehome.cooking_time')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield TextareaField::new('cookingText', '@restaurant.admin.takehome.cooking')->setRequired(false)->hideOnIndex();
        yield SelectField::new('allergens', '@restaurant.admin.takehome.allergens')->setChoices($allergens)->allowMultipleChoices()->setRequired(false)->setColumns(6)->hideOnIndex();
        yield TextField::new('illustration', '@restaurant.admin.takehome.illustration')->setRequired(false)->setColumns(6)->hideOnIndex();
    }
}
