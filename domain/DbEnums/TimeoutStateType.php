<?php

namespace SportsPlanning\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;
use SportsPlanning\Planning\TimeoutState;

final class TimeoutStateType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_PlanningTimeoutState';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): TimeoutState|null
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

    /**
     * @param array<string, mixed> $column
     * @param AbstractPlatform $platform
     * @return string
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(20)';
    }
}
