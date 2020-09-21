<?php

namespace SportsPlanning\Planning\Seeker;

use Psr\Log\LoggerInterface;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Game;

class GamesInARowPostProcessor
{
    protected Planning $batchGamePlanning;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var PlanningRepository
     */
    protected $planningRepos;
    /**
     * @var PlanningOutput
     */
    protected $planningOutput;

    public function __construct(Planning $batchGamePlanning, LoggerInterface $logger, PlanningRepository $planningRepos)
    {
        $this->batchGamePlanning = $batchGamePlanning;
        $this->logger = $logger;
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->planningRepos = $planningRepos;
    }

    public function updateOthers(Planning $planningProcessed)
    {
        if ($planningProcessed->getState() === Planning::STATE_SUCCEEDED) {
            $this->makeGreaterNrOfGamesInARowPlanningsSucceeded($planningProcessed);
        } elseif ($planningProcessed->getState() === Planning::STATE_FAILED) {
            $this->makeLesserGamesInARowPlanningsFailed($planningProcessed);
        } else { // Planning::STATE_TIMEDOUT
            $this->makeLesserGamesInARowPlanningsTimedout($planningProcessed);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningsSucceeded(Planning $planning)
    {
        foreach ($this->getGreaterNrOfGamesInARowPlannings($planning) as $greaterNrOfGamesInARowPlanning) {
            if( !($greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $greaterNrOfGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED) ) {
                continue;
            }
            $this->makeGreaterNrOfGamesInARowPlanningSucceeded($greaterNrOfGamesInARowPlanning);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningSucceeded(Planning $planning)
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

    protected function makeLesserGamesInARowPlanningsFailed(Planning $planning)
    {
        foreach( $this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning ) {
            // alle makkelijkeren die
            if( !($lessGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED) ) {
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

    protected function makeLesserGamesInARowPlanningsTimedout(Planning $planning)
    {
        foreach( $this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning ) {
            if( !($lessGamesInARowPlanning->getState() === Planning::STATE_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT
                || $lessGamesInARowPlanning->getState() === Planning::STATE_TOBEPROCESSED) ) {
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
     * @return array|Planning[]
     */
    protected function getGreaterNrOfGamesInARowPlannings(Planning $planningProcessed): array
    {
        return array_filter(
            $this->batchGamePlanning->getGamesInARowPlannings(),
            function (Planning $planningIt) use ($planningProcessed) : bool {
                if( $planningIt === $planningProcessed ) {
                    return false;
                }
                return $planningIt->getMaxNrOfGamesInARow() > $planningProcessed->getMaxNrOfGamesInARow();
            }
        );
    }

    /**
     *
     * @param Planning $planningProcessed
     * @return array|Planning[]
     */
    protected function getLesserGamesInARowPlannings(Planning $planningProcessed): array
    {
        return array_filter(
            $this->batchGamePlanning->getGamesInARowPlannings(),
            function (Planning $planningIt) use ($planningProcessed) : bool {
                if( $planningIt === $planningProcessed ) {
                    return false;
                }
                return $planningIt->getMaxNrOfGamesInARow() < $planningProcessed->getMaxNrOfGamesInARow();
            }
        );
    }
}
