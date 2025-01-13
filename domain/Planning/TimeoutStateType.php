<?php

namespace SportsPlanning\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;

class TimeoutStateType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_PlanningTimeoutState';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === TimeoutState::Time1xNoSort->value) {
            return TimeoutState::Time1xNoSort;
        }
        if ($value === TimeoutState::Time4xSort->value) {
            return TimeoutState::Time4xSort;
        }
        if ($value === TimeoutState::Time4xNoSort->value) {
            return TimeoutState::Time4xNoSort;
        }
        if ($value === TimeoutState::Time10xSort->value) {
            return TimeoutState::Time10xSort;
        }
        if ($value === TimeoutState::Time10xNoSort->value) {
            return TimeoutState::Time10xNoSort;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(20)';
    }
}
