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
        if ($value === State::ToBeProcessed->value) {
            return State::ToBeProcessed;
        }
        if ($value === State::Succeeded->value) {
            return State::Succeeded;
        }
        if ($value === State::LesserNrOfBatchesSucceeded->value) {
            return State::LesserNrOfBatchesSucceeded;
        }
        if ($value === State::LesserNrOfGamesInARowSucceeded->value) {
            return State::LesserNrOfGamesInARowSucceeded;
        }
        if ($value === State::Failed->value) {
            return State::Failed;
        }
        if ($value === State::GreaterNrOfBatchesFailed->value) {
            return State::GreaterNrOfBatchesFailed;
        }
        if ($value === State::GreaterNrOfGamesInARowFailed->value) {
            return State::GreaterNrOfGamesInARowFailed;
        }
        if ($value === State::TimedOut->value) {
            return State::TimedOut;
        }
        if ($value === State::GreaterNrOfBatchesTimedOut->value) {
            return State::GreaterNrOfBatchesTimedOut;
        }
        if ($value === State::GreaterNrOfGamesInARowTimedOut->value) {
            return State::GreaterNrOfGamesInARowTimedOut;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'int';
    }
}
