<?php

namespace Base\Restaurant\Entity\Product;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Enum\Allergen;
use Base\Marketplace\Entity\Product;
use Base\Restaurant\Enum\Keeping;
use Base\Restaurant\Repository\TakeHomeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A dish to cook at home, sold by the restaurant's take-home shop - fresh,
 * frozen, or kept on a shelf: an omnibase/marketplace product (title and
 * text, price before VAT and its VAT - 5.5 % in France for food to take away
 * that is not eaten at once -, stock, availability) with what such a dish
 * says besides: how it is kept (Enum\Keeping) and at what temperature, how
 * many days it keeps once made (its use-by date), its weight and portions,
 * how to cook it, its allergens, and whether it may travel in a chilled
 * parcel (Service\ColdChain).
 */
#[ORM\Entity(repositoryClass: TakeHomeRepository::class)]
#[DiscriminatorEntry(value: 'restaurant_take_home')]
class TakeHome extends Product
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-snowflake'];
    }

    /** "餃子" */
    #[ORM\Column(length: 80, nullable: true)]
    protected ?string $nativeName = null;

    /** A drawing shown while there is no photo: a path under the site's public assets */
    #[ORM\Column(length: 160, nullable: true)]
    protected ?string $illustration = null;

    /** @var list<string> Base\Enum\Allergen values */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $allergens = [];

    /** How it is kept: on a shelf, in the fridge (fresh), in the freezer (Enum\Keeping) */
    #[ORM\Column(length: 10, nullable: true)]
    protected ?string $keeping = Keeping::FRESH->value;

    /** The highest temperature it is kept at, in °C ("à conserver entre 0 et 4 °C"); null: its keeping's */
    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $storageTemperature = null;

    /** Days it keeps once made: its use-by date is the day it is made plus these */
    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $shelfLife = 3;

    /** Grams */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $netWeight = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $portions = 1;

    /** Minutes at the stove */
    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $cookingTime = null;

    /** @var array<string, list<string>> the steps by language: {fr: ["Faire chauffer…", "…"], en: […]} */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $cookingSteps = [];

    /** May travel in a chilled parcel (a broth in a jar does, a soft-boiled egg does not) */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $parcel = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $shopPosition = 0;

    public function getNativeName(): ?string { return $this->nativeName; }
    public function setNativeName(?string $name): self { $this->nativeName = trim((string) $name) ?: null; return $this; }
    public function getIllustration(): ?string { return $this->illustration; }
    public function setIllustration(?string $illustration): self { $this->illustration = $illustration ?: null; return $this; }

    /** @return list<string> */
    public function getAllergens(): array { return array_map(fn (Allergen $a) => $a->value, Allergen::fromList($this->allergens ?? [])); }
    /** @param iterable<string|Allergen> $allergens */
    public function setAllergens(iterable $allergens): self { $this->allergens = array_map(fn (Allergen $a) => $a->value, Allergen::fromList($allergens)); return $this; }
    /** @return list<Allergen> */
    public function getAllergenCases(): array { return Allergen::fromList($this->allergens ?? []); }

    public function getKeeping(): Keeping { return Keeping::tryFrom((string) $this->keeping) ?? Keeping::FRESH; }
    public function setKeeping(Keeping|string|null $keeping): self
    {
        $this->keeping = ($keeping instanceof Keeping ? $keeping : (Keeping::tryFrom((string) $keeping) ?? Keeping::FRESH))->value;

        return $this;
    }
    public function getKeepingName(): string { return $this->getKeeping()->value; }
    public function setKeepingName(?string $keeping): self { return $this->setKeeping($keeping); }
    public function isFrozen(): bool { return Keeping::FROZEN === $this->getKeeping(); }
    /** Kept cold - fresh or frozen: it leaves in an insulated bag, travels in a chilled parcel only. */
    public function isChilled(): bool { return $this->getKeeping()->isCold(); }
    public function getStorageTemperature(): int { return $this->storageTemperature ?? $this->getKeeping()->temperature(); }
    public function setStorageTemperature(?int $celsius): self { $this->storageTemperature = $celsius; return $this; }
    public function getShelfLife(): int { return max(0, $this->shelfLife ?? 0); }
    public function setShelfLife(?int $days): self { $this->shelfLife = max(0, (int) $days); return $this; }
    public function getNetWeight(): ?int { return $this->netWeight; }
    public function setNetWeight(?int $grams): self { $this->netWeight = $grams > 0 ? $grams : null; return $this; }
    public function getPortions(): int { return max(1, $this->portions ?? 1); }
    public function setPortions(?int $portions): self { $this->portions = max(1, (int) $portions); return $this; }
    public function getCookingTime(): ?int { return $this->cookingTime; }
    public function setCookingTime(?int $minutes): self { $this->cookingTime = $minutes > 0 ? $minutes : null; return $this; }

    /** @return list<string> the steps in this language, else in the first one written */
    public function getCookingSteps(?string $locale = null): array
    {
        $steps = $this->cookingSteps ?? [];
        // Written as a plain list: one language.
        if ($steps && array_is_list($steps)) {
            return array_values(array_filter(array_map('strval', $steps)));
        }
        $lang = $locale ? substr($locale, 0, 2) : null;

        return array_values(array_filter(array_map('strval', ($lang ? ($steps[$lang] ?? null) : null) ?? ($steps ? reset($steps) : []))));
    }

    /** @param array<string, list<string>>|list<string> $steps by language, or a plain list */
    public function setCookingSteps(?array $steps): self { $this->cookingSteps = $steps ?: []; return $this; }

    /** The back office's field: the steps of the first language, one per line. */
    public function getCookingText(): string { return implode("\n", $this->getCookingSteps()); }

    public function setCookingText(?string $text): self
    {
        $steps = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text) ?: [])));
        $all = $this->cookingSteps ?? [];
        if ($all && !array_is_list($all)) {
            $all[array_key_first($all)] = $steps;
            $this->cookingSteps = $all;
        } else {
            $this->cookingSteps = $steps;
        }

        return $this;
    }

    public function isParcel(): bool { return $this->parcel ?? true; }
    public function getParcel(): bool { return $this->isParcel(); }
    public function setParcel(?bool $parcel): self { $this->parcel = (bool) $parcel; return $this; }
    public function getShopPosition(): int { return $this->shopPosition ?? 0; }
    public function setShopPosition(?int $position): self { $this->shopPosition = (int) $position; return $this; }

    /** Its use-by date, made on this day. */
    public function useBy(\DateTimeImmutable $madeOn): \DateTimeImmutable
    {
        return $madeOn->setTime(0, 0)->modify(sprintf('+%d days', $this->getShelfLife()));
    }

    /** Grams, for the parcel: its net weight (the marketplace's attribute otherwise). */
    public function getWeight(): ?int
    {
        return $this->netWeight ?? parent::getWeight();
    }

    public function isAvailable(): bool
    {
        return $this->isForSell() && (null === $this->getStock() || $this->getStock() > 0);
    }
}
