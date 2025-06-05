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

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(13)';
    }
}
