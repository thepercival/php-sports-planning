<?php

declare(strict_types=1);

namespace SportsPlanning\Seeker;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning\State as PlanningState;

class GamesInARowPostProcessor extends Output
{
    protected PlanningOutput $planningOutput;

    public function __construct(
        protected Planning $batchGamePlanning,
        protected LoggerInterface $logger,
        protected PlanningRepository $planningRepos
    ) {
        parent::__construct($logger);
        $this->planningOutput = new PlanningOutput($this->logger);
    }

    public function updateOthers(Planning $planningProcessed): void
    {
        if ($planningProcessed->getState() === PlanningState::Succeeded) {
            $this->makeGreaterNrOfGamesInARowPlanningsSucceeded($planningProcessed);
        } elseif ($planningProcessed->getState() === PlanningState::Failed) {
            $this->makeLesserGamesInARowPlanningsFailed($planningProcessed);
        } else { // Planning::STATE_TIMEDOUT
            $this->makeLesserGamesInARowPlanningsTimedout($planningProcessed);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningsSucceeded(Planning $planning): void
    {
        foreach ($this->getGreaterNrOfGamesInARowPlannings($planning) as $greaterNrOfGamesInARowPlanning) {
            if (!($greaterNrOfGamesInARowPlanning->getState() === PlanningState::TimedOut
                || $greaterNrOfGamesInARowPlanning->getState() === PlanningState::GreaterNrOfGamesInARowTimedOut
                || $greaterNrOfGamesInARowPlanning->getState() === PlanningState::ToBeProcessed)) {
                continue;
            }
            $this->makeGreaterNrOfGamesInARowPlanningSucceeded($greaterNrOfGamesInARowPlanning);
        }
    }

    protected function makeGreaterNrOfGamesInARowPlanningSucceeded(Planning $planning): void
    {
        $planning->setState(PlanningState::LesserNrOfGamesInARowSucceeded);
        $this->planningRepos->save($planning);
        $this->output($planning, PlanningState::LesserNrOfGamesInARowSucceeded->name);
    }

    protected function makeLesserGamesInARowPlanningsFailed(Planning $planning): void
    {
        foreach ($this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning) {
            // alle makkelijkeren die
            if (!($lessGamesInARowPlanning->getState() === PlanningState::TimedOut
                || $lessGamesInARowPlanning->getState() === PlanningState::GreaterNrOfGamesInARowTimedOut
                || $lessGamesInARowPlanning->getState() === PlanningState::ToBeProcessed)) {
                continue;
            }
            $lessGamesInARowPlanning->setState(PlanningState::GreaterNrOfGamesInARowFailed);
            $this->planningRepos->save($lessGamesInARowPlanning);
            $this->output($lessGamesInARowPlanning, PlanningState::GreaterNrOfGamesInARowFailed->name);
        }
    }

    protected function makeLesserGamesInARowPlanningsTimedout(Planning $planning): void
    {
        foreach ($this->getLesserGamesInARowPlannings($planning) as $lessGamesInARowPlanning) {
            if (!($lessGamesInARowPlanning->getState() === PlanningState::TimedOut
                || $lessGamesInARowPlanning->getState() === PlanningState::GreaterNrOfGamesInARowTimedOut
                || $lessGamesInARowPlanning->getState() === PlanningState::ToBeProcessed)) {
                continue;
            }
            $lessGamesInARowPlanning->setState(PlanningState::GreaterNrOfGamesInARowTimedOut);
            $lessGamesInARowPlanning->setTimeoutSeconds($planning->getTimeoutSeconds());
            $this->planningRepos->save($lessGamesInARowPlanning);
            $suffix = PlanningState::GreaterNrOfGamesInARowTimedOut->name;
            $suffix .= ', timeoutSeconds => ' . $lessGamesInARowPlanning->getTimeoutSeconds();
            $this->output($lessGamesInARowPlanning, $suffix);
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
                                function (Planning $planningIt) use ($planningProcessed): bool {
                                    if ($planningIt === $planningProcessed) {
                                        return false;
                                    }
                                    return $planningIt->getMaxNrOfGamesInARow(
                                        ) > $planningProcessed->getMaxNrOfGamesInARow();
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
        return array_values(
            array_filter(
                $this->batchGamePlanning->getGamesInARowPlannings(),
                function (Planning $planningIt) use ($planningProcessed): bool {
                    if ($planningIt === $planningProcessed) {
                        return false;
                    }
                    return $planningIt->getMaxNrOfGamesInARow() < $planningProcessed->getMaxNrOfGamesInARow();
                }
            )
        );
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
