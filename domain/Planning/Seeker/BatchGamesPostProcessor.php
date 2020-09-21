<?php

namespace SportsPlanning\Planning\Seeker;

use Psr\Log\LoggerInterface;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Planning\Output as PlanningOutput;

class BatchGamesPostProcessor
{
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

    public function __construct(LoggerInterface $logger, PlanningRepository $planningRepos)
    {
        $this->logger = $logger;
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->planningRepos = $planningRepos;
    }

    public function updateOthers(Planning $planningProcessed)
    {
        if ($planningProcessed->getState() === Planning::STATE_SUCCEEDED) {
            $this->makeLessEfficientBatchGamesPlanningsSucceeded($planningProcessed );
            $this->updateSucceededSameBatchGamesPlannings($planningProcessed->getInput() );
        } elseif ($planningProcessed->getState() === Planning::STATE_FAILED) {
            $this->makeLessNrOfBatchesPlanningsFailed($planningProcessed );
        } else { // Planning::STATE_TIMEDOUT
            $this->makeLesserNrOfBatchesPlanningsTimedout($planningProcessed );
        }
    }

    protected function makeLessEfficientBatchGamesPlanningsSucceeded(Planning $planning)
    {
        foreach( $this->getGreaterNrOfBatchesPlannings($planning) as $moreNrOfBatchesPlanning ) {
            $this->makeLessEfficientBatchGamesPlanningSucceeded($moreNrOfBatchesPlanning);
        }
    }

    protected function makeLessEfficientBatchGamesPlanningSucceeded(Planning $planning)
    {
        $this->planningRepos->resetBatchGamePlanning($planning, Planning::STATE_LESSER_NROFBATCHES_SUCCEEDED);
        $this->planningOutput->output(
            $planning,
            false,
            '   ',
            " state => LESSER_NROFBATCHES_SUCCEEDED (gamesinarow removed)"
        );

    }

    protected function updateSucceededSameBatchGamesPlannings(Input $input)
    {
        $succeededBatchGamesPlannings = $input->getBatchGamesPlannings( Planning::STATE_SUCCEEDED);
        if( count( $succeededBatchGamesPlannings ) !== 2 ) {
            return;
        }
        $this->makeLessEfficientBatchGamesPlanningSucceeded(array_pop($succeededBatchGamesPlannings));
    }

    protected function makeLessNrOfBatchesPlanningsFailed(Planning $planning)
    {
        foreach( $this->getLessNrOfBatchesPlannings($planning) as $lessNrOfBatchesPlanning ) {
            // als buiten statussen dan niets dan, als buiten statussen en succes dan alleen wedstrijden verwijderen

            $this->planningRepos->resetBatchGamePlanning($lessNrOfBatchesPlanning, Planning::STATE_GREATER_NROFBATCHES_FAILED);
            $this->planningOutput->output(
                $lessNrOfBatchesPlanning,
                false,
                '   ',
                " state => GREATER_NROFBATCHES_FAILED (gamesinarow removed)"
            );
        }
    }

    protected function makeLesserNrOfBatchesPlanningsTimedout(Planning $planningProcessed)
    {
        foreach( $this->getLessNrOfBatchesPlannings($planningProcessed) as $planningIt ) {
            if( !($planningIt->getState() === Planning::STATE_TIMEDOUT
                || $planningIt->getState() === Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT
                || $planningIt->getState() === Planning::STATE_TOBEPROCESSED) ) {
                continue;
            }
            $planningIt->setState(Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT);
            $planningIt->setTimeoutSeconds($planningProcessed->getTimeoutSeconds());
            $this->planningRepos->save($planningIt);
            $this->planningOutput->output(
                $planningIt,
                false,
                '   ',
                " state => GREATER_NROFBATCHES_TIMEDOUT"
            );
        }
    }

    /**
     * alles waarbij max<= success->min of waarbij min=success->min en max< success->max  eruit
     *
     * @param Planning $planningProcessed
     * @return array|Planning[]
     */
    protected function getGreaterNrOfBatchesPlannings(Planning $planningProcessed): array
    {
        return array_filter(
            $planningProcessed->getInput()->getBatchGamesPlannings(),
            function (Planning $planningToBeProcessed) use ($planningProcessed) : bool {
                if( $planningToBeProcessed === $planningProcessed ) {
                    return false;
                }
                return ($planningToBeProcessed->getMaxNrOfBatchGames() <= $planningProcessed->getMinNrOfBatchGames()
                    ||
                    ($planningToBeProcessed->getMinNrOfBatchGames() === $planningProcessed->getMinNrOfBatchGames()
                        && $planningToBeProcessed->getMaxNrOfBatchGames() < $planningProcessed->getMaxNrOfBatchGames()
                    ));
            }
        );
    }

    /**
     *  alles waarbij max > failed->min of waarbij max = failed->max en min> failed->min eruit
     *
     * @param Planning $planningProcessed
     * @return array|Planning[]
     */
    protected function getLessNrOfBatchesPlannings(Planning $planningProcessed): array
    {
        return array_filter(
            $planningProcessed->getInput()->getBatchGamesPlannings(),
            function (Planning $planningToBeProcessed) use ($planningProcessed) : bool {
                if( $planningToBeProcessed === $planningProcessed ) {
                    return false;
                }
                return ($planningToBeProcessed->getMaxNrOfBatchGames() > $planningProcessed->getMinNrOfBatchGames()
                    ||
                    ($planningToBeProcessed->getMaxNrOfBatchGames() === $planningProcessed->getMaxNrOfBatchGames()
                        && $planningToBeProcessed->getMinNrOfBatchGames() > $planningProcessed->getMinNrOfBatchGames()
                    ));
            }
        );
    }
}
