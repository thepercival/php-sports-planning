<?php

namespace SportsPlanning\Planning\Seeker;

use Psr\Log\LoggerInterface;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;

class GamesInARowPostProcessor
{
    protected PlanningOutput $planningOutput;

    public function __construct(
        protected Planning $batchGamePlanning,
        protected LoggerInterface $logger,
        protected PlanningRepository $planningRepos
    )
    {
        $this->planningOutput = new PlanningOutput($this->logger);
    }

    public function updateOthers(Planning $planningProcessed): void
    {
        if ($planningProcessed->getState() === Planning::STATE_SUCCEEDED) {
            $this->makeGreaterNrOfGamesInARowPlanningsSucceeded($planningProcessed);
        } elseif ($planningProcessed->getState() === Planning::STATE_FAILED) {
            $this->makeLesserGamesInARowPlanningsFailed($planningProcessed);
        } else { // Planning::STATE_TIMEDOUT
            $this->makeLesserGamesInARowPlanningsTimedout($planningProcessed);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningsSucceeded(Planning $planning): void
    {
        foreach ($this->getGreaterNrOfGamesInARowPlannings($planning) as $greaterNrOfGamesInARowPlanning) {
            if (!($greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED)) {
                continue;
            }
            $this->makeGreaterNrOfGamesInARowPlanningSucceeded($greaterNrOfGamesInARowPlanning);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningSucceeded(Planning $planning): void
    {
        $planning->setState(Planning::STATE_LESSER_NROFGAMESINROW_SUCCEEDED);
        $this->planningRepos->save($planning);
        $this->planningOutput->output(
            $planning,
            false,
            '   ',
            " state => LESSER_NROFGAMESINROW_SUCCEEDED"
        );
    }

    protected function makeLesserGamesInARowPlanningsFailed(Planning $planning): void
    {
        foreach ($this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning) {
            // alle makkelijkeren die
            if (!($lessGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED)) {
                continue;
            }
            $lessGamesInARowPlanning->setState(Planning::STATE_GREATER_NROFGAMESINROW_FAILED);
            $this->planningRepos->save($lessGamesInARowPlanning);
            $this->planningOutput->output(
                $lessGamesInARowPlanning,
                false,
                '   ',
                " state => GREATER_NROFGAMESINROW_FAILED"
            );
        }
    }

    protected function makeLesserGamesInARowPlanningsTimedout(Planning $planning): void
    {
        foreach ($this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning) {
            if (!($lessGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED)) {
                continue;
            }
            $lessGamesInARowPlanning->setState(Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT);
            $lessGamesInARowPlanning->setTimeoutSeconds($planning->getTimeoutSeconds());
            $this->planningRepos->save($lessGamesInARowPlanning);
            $this->planningOutput->output(
                $lessGamesInARowPlanning,
                false,
                '   ',
                " state => GREATER_GAMESINAROW_TIMEDOUT, timeoutSeconds => " . $lessGamesInARowPlanning->getTimeoutSeconds()
            );
        }
    }

    /**
     *
     * @param Planning $planningProcessed
     * @return list<Planning>
     */
    protected function getGreaterNrOfGamesInARowPlannings(Planning $planningProcessed): array
    {
        return array_values(array_filter(
            $this->batchGamePlanning->getGamesInARowPlannings(),
            function (Planning $planningIt) use ($planningProcessed) : bool {
                if ($planningIt === $planningProcessed) {
                    return false;
                }
                return $planningIt->getMaxNrOfGamesInARow() > $planningProcessed->getMaxNrOfGamesInARow();
            }
        ));
    }

    /**
     *
     * @param Planning $planningProcessed
     * @return list<Planning>
     */
    protected function getLesserGamesInARowPlannings(Planning $planningProcessed): array
    {
        return array_values(array_filter(
            $this->batchGamePlanning->getGamesInARowPlannings(),
            function (Planning $planningIt) use ($planningProcessed) : bool {
                if ($planningIt === $planningProcessed) {
                    return false;
                }
                return $planningIt->getMaxNrOfGamesInARow() < $planningProcessed->getMaxNrOfGamesInARow();
            }
        ));
    }
}
