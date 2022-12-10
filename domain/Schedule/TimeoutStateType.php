<?php

namespace SportsPlanning\Schedule;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class TimeoutStateType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_ScheduleTimeoutState';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === TimeoutState::FirstTryMaxDiff2->value) {
            return TimeoutState::FirstTryMaxDiff2;
        }
        if ($value === TimeoutState::SecondTryMaxDiff2->value) {
            return TimeoutState::SecondTryMaxDiff2;
        }
        if ($value === TimeoutState::FirstTryMinDiff->value) {
            return TimeoutState::FirstTryMinDiff;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(20)';;
    }
}
