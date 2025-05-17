<?php

namespace SportsPlanning\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;

class StateType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_PlanningState';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === PlanningState::NotProcessed->value) {
            return PlanningState::NotProcessed;
        }
        if ($value === PlanningState::Succeeded->value) {
            return PlanningState::Succeeded;
        }
        if ($value === PlanningState::Failed->value) {
            return PlanningState::Failed;
        }
        if ($value === PlanningState::TimedOut->value) {
            return PlanningState::TimedOut;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(13)';
    }
}
