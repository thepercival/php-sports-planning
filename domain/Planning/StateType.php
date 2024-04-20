<?php

namespace SportsPlanning\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class StateType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_PlanningState';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === State::NotProcessed->value) {
            return State::NotProcessed;
        }
        if ($value === State::Succeeded->value) {
            return State::Succeeded;
        }
        if ($value === State::Failed->value) {
            return State::Failed;
        }
        if ($value === State::TimedOut->value) {
            return State::TimedOut;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(13)';
    }
}
