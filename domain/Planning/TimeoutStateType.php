<?php

namespace SportsPlanning\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

final class TimeoutStateType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_PlanningTimeoutState';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform):TimeoutState|null
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

    #[\Override]
    /**
     * @psalm-param array<string,mixed> $colum
     */
    public function getSQLDeclaration($column, AbstractPlatform $platform): string
    {
        return 'varchar(20)';
    }
}
