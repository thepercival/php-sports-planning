<?php

namespace SportsPlanning\Planning;

use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningPouleStructure;

class TimeoutConfig
{
    public const int MINIMUM_TIMEOUTSECONDS = 5;
    public const int MAXIMUM_TIMEOUTSECONDS = 15;

    public function getTimeoutSeconds(PlanningConfiguration $configuration, TimeoutState|null $timeoutState): int
    {
        $timeoutSeconds = $this->getDefaultTimeoutSeconds($configuration);
        if ($timeoutState === null || $timeoutState === TimeoutState::Time1xNoSort) {
            return $timeoutSeconds;
        }
        if ($timeoutState === TimeoutState::Time4xSort || $timeoutState === TimeoutState::Time4xNoSort) {
            return $timeoutSeconds * 4;
        }
        return $timeoutSeconds * 10;
    }

    public function getDefaultTimeoutSeconds(PlanningConfiguration $configuration): int
    {
        $planningPouleStructure = $configuration->createPlanningPouleStructure();
        $totalNrOfGames = $planningPouleStructure->calculateNrOfGames();
        $nrOfGamesPerSecond = 10;
        $nrOfSeconds = (int)ceil($totalNrOfGames / $nrOfGamesPerSecond);
        if ($nrOfSeconds < self::MINIMUM_TIMEOUTSECONDS) {
            $nrOfSeconds = self::MINIMUM_TIMEOUTSECONDS;
        }
        if ($nrOfSeconds > self::MAXIMUM_TIMEOUTSECONDS) {
            $nrOfSeconds = self::MAXIMUM_TIMEOUTSECONDS;
        }
        return $nrOfSeconds;
    }

    public function nextTimeoutState(Planning|null $planning): TimeoutState
    {
        if ($planning === null) {
            return TimeoutState::Time1xNoSort;
        }
        $timeoutState = $planning->getTimeoutState();
        if ($timeoutState === null) {
            return TimeoutState::Time1xNoSort;
        }
        if ($timeoutState === TimeoutState::Time1xNoSort) {
            return TimeoutState::Time4xSort;
        }
        if ($timeoutState === TimeoutState::Time4xSort) {
            return TimeoutState::Time4xNoSort;
        }
        if ($timeoutState === TimeoutState::Time4xNoSort) {
            return TimeoutState::Time10xSort;
        }
        if ($timeoutState === TimeoutState::Time10xSort) {
            return TimeoutState::Time10xNoSort;
        }
        throw new \Exception('last timeoutstate already reached', E_ERROR);
    }

    public function useSort(TimeoutState $timeoutState): bool
    {
        return $timeoutState === TimeoutState::Time4xSort || $timeoutState === TimeoutState::Time10xSort;
    }
}
