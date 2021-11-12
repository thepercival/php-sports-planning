<?php
declare(strict_types=1);

namespace SportsPlanning\Seeker;

use Psr\Log\LoggerInterface;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsHelpers\Output;
use SportsPlanning\Planning\Output as PlanningOutput;

class BatchGamesPostProcessor extends Output
{
    protected PlanningOutput $planningOutput;

    public function __construct(LoggerInterface $logger, protected PlanningRepository $planningRepos)
    {
        parent::__construct($logger);
        $this->planningOutput = new PlanningOutput($this->logger);
    }

    public function updateOthers(Planning $planningProcessed): void
    {
        if ($planningProcessed->getState() === Planning::STATE_SUCCEEDED) {
            $this->makeLessEfficientBatchGamesPlanningsSucceeded($planningProcessed);
            $this->updateSucceededSameBatchGamesPlannings($planningProcessed->getInput());
        } elseif ($planningProcessed->getState() === Planning::STATE_FAILED) {
            $this->makeLessNrOfBatchesPlanningsFailed($planningProcessed);
        } else { // Planning::STATE_TIMEDOUT
            $this->makeLesserNrOfBatchesPlanningsTimedout($planningProcessed);
        }
    }

    protected function makeLessEfficientBatchGamesPlanningsSucceeded(Planning $planning): void
    {
        foreach ($this->getGreaterNrOfBatchesPlannings($planning) as $moreNrOfBatchesPlanning) {
            $this->makeLessEfficientBatchGamesPlanningSucceeded($moreNrOfBatchesPlanning);
        }
    }

    protected function makeLessEfficientBatchGamesPlanningSucceeded(Planning $planning): void
    {
        if ($planning->getState() === Planning::STATE_LESSER_NROFBATCHES_SUCCEEDED) {
            return;
        }
        $this->planningRepos->resetBatchGamePlanning($planning, Planning::STATE_LESSER_NROFBATCHES_SUCCEEDED);
        $this->output($planning,' LESSER_NROFBATCHES_SUCCEEDED (gamesinarow removed)');
    }

    protected function updateSucceededSameBatchGamesPlannings(Input $input): void
    {
        $succeededBatchGamesPlannings = $input->getBatchGamesPlannings(Planning::STATE_SUCCEEDED);
        if (count($succeededBatchGamesPlannings) !== 2) {
            return;
        }
        $this->makeLessEfficientBatchGamesPlanningSucceeded(array_pop($succeededBatchGamesPlannings));
    }

    protected function makeLessNrOfBatchesPlanningsFailed(Planning $planning): void
    {
        foreach ($this->getLessNrOfBatchesPlannings($planning) as $lessNrOfBatchesPlanning) {
            if ($lessNrOfBatchesPlanning->getState() === Planning::STATE_GREATER_NROFBATCHES_FAILED) {
                continue;
            }
            $this->planningRepos->resetBatchGamePlanning($lessNrOfBatchesPlanning, Planning::STATE_GREATER_NROFBATCHES_FAILED);
            $this->output($lessNrOfBatchesPlanning,' GREATER_NROFBATCHES_FAILED (gamesinarow removed)');
        }
    }

    protected function makeLesserNrOfBatchesPlanningsTimedout(Planning $planningProcessed): void
    {
        foreach ($this->getLessNrOfBatchesPlannings($planningProcessed) as $planningIt) {
            if (!($planningIt->getState() === Planning::STATE_TIMEDOUT
                || $planningIt->getState() === Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT
                || $planningIt->getState() === Planning::STATE_TOBEPROCESSED)) {
                continue;
            }
            $planningIt->setState(Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT);
            $planningIt->setTimeoutSeconds($planningProcessed->getTimeoutSeconds());
            $this->planningRepos->save($planningIt);
            $this->output($planningIt,' GREATER_NROFBATCHES_TIMEDOUT');
        }
    }

    /**
     * alles waarbij max<= success->min of waarbij min=success->min en max< success->max  eruit
     *
     * @param Planning $planningProcessed
     * @return list<Planning>
     */
    protected function getGreaterNrOfBatchesPlannings(Planning $planningProcessed): array
    {
        return array_values(array_filter(
            $planningProcessed->getInput()->getBatchGamesPlannings(),
            function (Planning $planningToBeProcessed) use ($planningProcessed) : bool {
                if ($planningToBeProcessed === $planningProcessed) {
                    return false;
                }
                return $planningToBeProcessed->getMinNrOfBatchGames() <= $planningProcessed->getMinNrOfBatchGames()
                        && $planningToBeProcessed->getMaxNrOfBatchGames() <= $planningProcessed->getMaxNrOfBatchGames();
            }
        ));
    }

    /**
     *  alles waarbij max > failed->min of waarbij max = failed->max en min> failed->min eruit
     *
     * @param Planning $planningProcessed
     * @return list<Planning>
     */
    protected function getLessNrOfBatchesPlannings(Planning $planningProcessed): array
    {
        return array_values(array_filter(
            $planningProcessed->getInput()->getBatchGamesPlannings(),
            function (Planning $planningToBeProcessed) use ($planningProcessed) : bool {
                if ($planningToBeProcessed === $planningProcessed) {
                    return false;
                }
                return $planningToBeProcessed->getMaxNrOfBatchGames() >= $planningProcessed->getMaxNrOfBatchGames()
                        && $planningToBeProcessed->getMinNrOfBatchGames() >= $planningProcessed->getMinNrOfBatchGames();
            }
        ));
    }

    protected function output(Planning $planning, string $suffix): void
    {
        $this->planningOutput->output(
            $planning,
            false,
            '   ',
            ' state => ' . $suffix,
            $this->useColors() ? Output::COLOR_GRAY : -1
        );
    }
}
