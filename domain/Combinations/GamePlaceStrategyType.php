<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class GamePlaceStrategyType extends EnumDbType
{
    // const NAME = 'enum_GameMode'; // modify to match your type name

    static public function getNameHelper(): string
    {
        return 'enum_GamePlaceStrategy';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === GamePlaceStrategy::EquallyAssigned->value) {
            return GamePlaceStrategy::EquallyAssigned;
        }
        if ($value === GamePlaceStrategy::RandomlyAssigned->value) {
            return GamePlaceStrategy::RandomlyAssigned;
        }
        return null;
    }
}