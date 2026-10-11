<?php

namespace Base\Restaurant\Enum;

/**
 * How a take-home dish is kept until it is cooked: on a shelf, in the
 * fridge, or in the freezer. It says the temperature it is kept at by
 * default and how it leaves the restaurant (a chilled or frozen dish goes
 * in an insulated bag, and travels only in a chilled parcel).
 */
enum Keeping: string
{
    case AMBIENT = 'ambient';
    case FRESH = 'fresh';
    case FROZEN = 'frozen';

    /** The highest temperature it is kept at, in °C, when the dish says nothing else */
    public function temperature(): int
    {
        return match ($this) {
            self::AMBIENT => 20,
            self::FRESH => 4,
            self::FROZEN => -18,
        };
    }

    /** Kept cold: it leaves in an insulated bag, travels in a chilled parcel only */
    public function isCold(): bool
    {
        return self::AMBIENT !== $this;
    }

    public function trans(): string
    {
        return 'keeping.'.$this->value;
    }
}
