<?php

namespace SportsPlanning\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;

final class StateType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_PlanningState';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform):State|null
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

    #[\Override]
    public function getSQLDeclaration($column, AbstractPlatform $platform): string
    {
        return 'varchar(13)';
    }
}
