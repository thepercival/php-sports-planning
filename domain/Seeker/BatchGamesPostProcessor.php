<?php

declare(strict_types=1);

namespace SportsPlanning\Seeker;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning\State as PlanningState;

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
        if ($planningProcessed->getState() === PlanningState::Succeeded) {
            $this->makeLessEfficientBatchGamesPlanningsSucceeded($planningProcessed);
            $this->updateSucceededSameBatchGamesPlannings($planningProcessed->getInput());
        } elseif ($planningProcessed->getState() === PlanningState::Failed) {
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
        if ($planning->getState() === PlanningState::LesserNrOfBatchesSucceeded) {
            return;
        }
        $this->planningRepos->resetBatchGamePlanning($planning, PlanningState::LesserNrOfBatchesSucceeded);
        $this->output($planning, ' ' . PlanningState::LesserNrOfBatchesSucceeded->name . ' (gamesinarow removed)');
    }

    protected function updateSucceededSameBatchGamesPlannings(Input $input): void
    {
        $succeededBatchGamesPlannings = $input->getBatchGamesPlannings(PlanningState::Succeeded->value);
        if (count($succeededBatchGamesPlannings) !== 2) {
            return;
        }
        $this->makeLessEfficientBatchGamesPlanningSucceeded(array_pop($succeededBatchGamesPlannings));
    }

    protected function makeLessNrOfBatchesPlanningsFailed(Planning $planning): void
    {
        foreach ($this->getLessNrOfBatchesPlannings($planning) as $lessNrOfBatchesPlanning) {
            if ($lessNrOfBatchesPlanning->getState() === PlanningState::GreaterNrOfBatchesFailed) {
                continue;
            }
            $this->planningRepos->resetBatchGamePlanning(
                $lessNrOfBatchesPlanning,
                PlanningState::GreaterNrOfBatchesFailed
            );
            $this->output(
                $lessNrOfBatchesPlanning,
                ' ' . PlanningState::GreaterNrOfBatchesFailed->name . ' (gamesinarow removed)'
            );
        }
    }

    protected function makeLesserNrOfBatchesPlanningsTimedout(Planning $planningProcessed): void
    {
        foreach ($this->getLessNrOfBatchesPlannings($planningProcessed) as $planningIt) {
            if (!($planningIt->getState() === PlanningState::TimedOut
                || $planningIt->getState() === PlanningState::GreaterNrOfBatchesTimedOut
                || $planningIt->getState() === PlanningState::ToBeProcessed)) {
                continue;
            }
            $planningIt->setState(PlanningState::GreaterNrOfBatchesTimedOut);
            $planningIt->setTimeoutSeconds($planningProcessed->getTimeoutSeconds());
            $this->planningRepos->save($planningIt);
            $this->output($planningIt, ' ' . PlanningState::GreaterNrOfBatchesTimedOut->name);
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
            function (Planning $planningToBeProcessed) use ($planningProcessed): bool {
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
                                function (Planning $planningToBeProcessed) use ($planningProcessed): bool {
                                    if ($planningToBeProcessed === $planningProcessed) {
                                        return false;
                                    }
                                    return $planningToBeProcessed->getMaxNrOfBatchGames(
                                        ) >= $planningProcessed->getMaxNrOfBatchGames()
                                        && $planningToBeProcessed->getMinNrOfBatchGames(
                                        ) >= $planningProcessed->getMinNrOfBatchGames();
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
