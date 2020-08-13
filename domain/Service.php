<?php

namespace SportsPlanning;

use SportsHelpers\Range;

class Service
{
    public function __construct()
    {
    }

    public function createGames(Planning $planning)
    {
        $gameGenerator = new GameGenerator($planning->getInput());
        $gameGenerator->create($planning);
        $games = $planning->getGames(Game::ORDER_BY_GAMENUMBER);

        $resourceService = new Resource\Service($planning);

        $state = $resourceService->assign($games);
        if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEOUT) {
            foreach ($planning->getPoules() as $poule) {
                $poule->getGames()->clear();
            }
        }
        return $state;
    }

    public function getMinIsMaxPlannings(Input $input): array
    {
        return array_filter($this->getOrderedPlannings($input), function (Planning $planning): bool {
            return $planning->minIsMaxNrOfBatchGames();
        });
    }

    public function getPlannings(Input $input, Range $range): array
    {
        return array_filter($this->getOrderedPlannings($input), function (Planning $planning) use ($range): bool {
            return $planning->getMinNrOfBatchGames() === $range->min && $planning->getMaxNrOfBatchGames() === $range->max;
        });
    }

    public function getMinIsMax(Input $input, int $states): ?Planning
    {
        $maxNrInARow = $input->getMaxNrOfGamesInARow();
        $minIsMaxPlannings = array_filter($this->getMinIsMaxPlannings($input), function (Planning $planning) use ($states, $maxNrInARow): bool {
            return ($planning->getState() & $states) === $planning->getState() && $planning->getMaxNrOfGamesInARow() === $maxNrInARow;
        });
        if (count($minIsMaxPlannings) === 0) {
            return null;
        }
        return reset($minIsMaxPlannings);
    }

    public function createNextMinIsMaxPlanning(Input $input): Planning
    {
        $lastPlanning = $this->getMinIsMax($input, Planning::STATE_FAILED + Planning::STATE_TIMEOUT);
        $nrOfBatchGames = $lastPlanning !== null ? ($lastPlanning->getMaxNrOfBatchGames() - 1) : $input->getMaxNrOfBatchGames();
        if( $nrOfBatchGames === 0 ) {
            $nrOfBatchGames++;
        }
        return new Planning($input, new Range($nrOfBatchGames, $nrOfBatchGames), $input->getMaxNrOfGamesInARow());
    }

    public function getPlusOnePlanning(Planning $minIsMaxPlanning): ?Planning
    {
        $plusOnePlannings = array_filter($this->getOrderedPlannings($minIsMaxPlanning->getInput()), function (Planning $planning) use ($minIsMaxPlanning): bool {
            return $planning->getMinNrOfBatchGames() === $minIsMaxPlanning->getMaxNrOfBatchGames()
                && $planning->getMaxNrOfBatchGames() === ($minIsMaxPlanning->getMaxNrOfBatchGames() + 1);
        });
        $plusOnePlanning = end($plusOnePlannings);
        if ($plusOnePlanning === false) {
            return null;
        }
        return $plusOnePlanning;
    }

    public function createPlusOnePlanning(Planning $minIsMaxPlanning): Planning
    {
        return new Planning(
            $minIsMaxPlanning->getInput(),
            new Range($minIsMaxPlanning->getMaxNrOfBatchGames(), $minIsMaxPlanning->getMaxNrOfBatchGames() + 1),
            $minIsMaxPlanning->getInput()->getMaxNrOfGamesInARow()
        );
    }

    public function createNextInARowPlanning(Planning $planning): ?Planning
    {
        $plannings = $this->getPlannings($planning->getInput(), $planning->getNrOfBatchGames());

        $lastTriedPlanning = array_pop($plannings);
        $previousTriedPlanning = array_pop($plannings);
        if ($this->nextInARowDone($lastTriedPlanning, $previousTriedPlanning)) {
            return null;
        }
        return new Planning(
            $planning->getInput(),
            new Range($planning->getMinNrOfBatchGames(), $planning->getMaxNrOfBatchGames()),
            $this->getNextInARowDone($lastTriedPlanning, $previousTriedPlanning)
        );
    }

    public function createNextNInARow(Planning $planning): Planning
    {
        return new Planning(
            $planning->getInput(),
            new Range($planning->getMaxNrOfBatchGames(), $planning->getMaxNrOfBatchGames()),
            $planning->getMaxNrOfGamesInARow() - 1
        );
    }

    protected function nextInARowDone(Planning $lastTriedPlanning, Planning $previousTriedPlanning = null): bool
    {
        if ($lastTriedPlanning->getMaxNrOfGamesInARow() === 1) {
            return true;
        }

        $lastTriedFailed = ($lastTriedPlanning->getState() === Planning::STATE_FAILED || $lastTriedPlanning->getState() === Planning::STATE_TIMEOUT);
        $previousTriedFailed = $previousTriedPlanning === null || ($previousTriedPlanning->getState() === Planning::STATE_FAILED || $previousTriedPlanning->getState() === Planning::STATE_TIMEOUT);

        if ($lastTriedFailed && $previousTriedFailed) {
            return true;
        }

        if ($lastTriedFailed && !$previousTriedFailed && (($previousTriedPlanning->getMaxNrOfGamesInARow() - $lastTriedPlanning->getMaxNrOfGamesInARow()) === 1)) {
            return true;
        }

        return false;
    }

    protected function getNextInARowDone(Planning $lastTriedPlanning, Planning $previousTriedPlanning = null): int
    {
        if ($lastTriedPlanning->getState() === Planning::STATE_SUCCESS || $previousTriedPlanning === null) {
            return (int) ceil($lastTriedPlanning->getMaxNrOfGamesInARow() / 2);
        }
        return (int) ceil(($previousTriedPlanning->getMaxNrOfGamesInARow() + $lastTriedPlanning->getMaxNrOfGamesInARow()) / 2);
    }

    public function getBestPlanning(Input $input): ?Planning
    {
        $plannings = array_reverse($this->getOrderedPlannings($input));
        foreach ($plannings as $planning) {
            if ($planning->getState() === Planning::STATE_SUCCESS) {
                return $planning;
            }
        }
        return null;
    }

    public function getOrderedPlannings(Input $input): array
    {
        $plannings = $input->getPlannings()->toArray();
        uasort($plannings, function (Planning $first, Planning $second) {
            if ($first->getMaxNrOfBatchGames() === $second->getMaxNrOfBatchGames()) {
                if ($first->getMinNrOfBatchGames() === $second->getMinNrOfBatchGames()) {
                    return $first->getMaxNrOfGamesInARow() > $second->getMaxNrOfGamesInARow() ? -1 : 1;
                }
                return $first->getMinNrOfBatchGames() < $second->getMinNrOfBatchGames() ? -1 : 1;
            }
            return $first->getMaxNrOfBatchGames() < $second->getMaxNrOfBatchGames() ? -1 : 1;
        });
        return $plannings;
    }
}
