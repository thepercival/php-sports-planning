<?php

declare(strict_types=1);

namespace SportsPlanning\Seeker;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output\Color;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Planning\TimeoutConfig;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Repository as ScheduleRepository;

class Timeout
{
    use Color;

    protected InputService $inputService;
    protected PlanningOutput $planningOutput;
    protected TimeoutConfig $timeoutConfig;
    protected bool $throwOnTimeout;

    public function __construct(
        protected LoggerInterface $logger,
        protected InputRepository $inputRepos,
        protected PlanningRepository $planningRepos,
        protected ScheduleRepository $scheduleRepos
    ) {
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->inputService = new InputService();
        $this->throwOnTimeout = true;
        $this->timeoutConfig = new TimeoutConfig();
    }

    public function process(Input $input, TimeoutState $timeoutState): bool
    {
        $succeeded = $this->processEqualBatchGames($input, $timeoutState);
        if ($succeeded) {
            return $succeeded;
        }
        $succeeded = $this->processUnequalBatchGames($input, $timeoutState);
        if ($succeeded) {
            return $succeeded;
        }
        return $this->processGamesInARow($input, $timeoutState);
    }

    public function processBatchGames(Input $input, TimeoutState $timeoutState): bool
    {
        $succeeded = $this->processEqualBatchGames($input, $timeoutState);
        if ($succeeded) {
            return $succeeded;
        }
        return $this->processUnequalBatchGames($input, $timeoutState);
    }

    public function processEqualBatchGames(Input $input, TimeoutState $timeoutState): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $plannings = $input->getEqualBatchGamesPlannings(PlanningState::TimedOut);
            $statePlannings = $this->getPlanningsWithState($plannings, $timeoutState);
            return $this->processHelper($input, $statePlannings, $schedules);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    public function processUnequalBatchGames(Input $input, TimeoutState $timeoutState): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $plannings = $input->getUnequalBatchGamesPlannings(PlanningState::TimedOut);
            $statePlannings = $this->getPlanningsWithState($plannings, $timeoutState);

            foreach ($statePlannings as $planning) {
                $this->processPlanningHelper($planning, $schedules);
                if ($planning->getState() === PlanningState::Succeeded) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    /**
     * @param Input $input
     * @return bool
     */
    public function processGamesInARow(Input $input, TimeoutState $timeoutState): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $bestBatchGamesPlanning = $input->getBestPlanning(PlanningType::BatchGames);

            $plannings = $bestBatchGamesPlanning->getGamesInARowPlannings(PlanningState::TimedOut);
            $statePlannings = $this->getPlanningsWithState($plannings, $timeoutState);
            return $this->processHelper($input, $statePlannings, $schedules);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    /**
     * @param list<Planning> $plannings
     * @param TimeoutState $timeoutState
     * @return list<Planning>
     */
    private function getPlanningsWithState(array $plannings, TimeoutState $timeoutState): array
    {
        return array_values(
            array_filter($plannings, function (Planning $planning) use ($timeoutState): bool {
                return $planning->getTimeoutState() === $timeoutState;
            })
        );
    }

    /**
     * @param Input $input
     * @param list<Planning> $plannings
     * @param list<Schedule> $schedules
     * @return bool
     */
    public function processHelper(Input $input, array $plannings, array $schedules): bool
    {
        $easiestPlanning = array_pop($plannings);
        if ($easiestPlanning === null) {
            return false;
        }
        $this->processPlanningHelper($easiestPlanning, $schedules);
        if ($easiestPlanning->getState() === PlanningState::Succeeded) {
            return true;
        }
        if ($easiestPlanning->getState() === PlanningState::TimedOut) {
            $this->updateTimeoutPlannings($easiestPlanning, $plannings);
        } elseif ($easiestPlanning->getState() === PlanningState::Failed) {
            $this->removePlannings($input, $plannings);
        }
        return false;
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processPlanningHelper(Planning $planning, array $schedules): void
    {
        $nextTimeoutState = $this->timeoutConfig->nextTimeoutState($planning);
        $this->planningOutput->output(
            $planning,
            false,
            '   trying ',
            ' for timeoutState "' . $nextTimeoutState->value . '"'
        );

        $gameCreator = new GameCreator($this->logger);
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->logger);
        if (!$this->throwOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        $gameAssigner->assignGames($planning);
        $this->planningRepos->save($planning);

//        $planningOutput = new PlanningOutput($this->logger);
//        $planningOutput->outputWithGames($planning, false);
//        $planningOutput->outputWithTotals($planning, false);

        $this->logger->info('   ' . '   ' . " => " . $planning->getStateDescription());
    }

    /**
     * @param Planning $easiestPlanning
     * @param list<Planning> $plannings
     */
    protected function updateTimeoutPlannings(Planning $easiestPlanning, array $plannings): void
    {
        while (count($plannings) > 0) {
            $planning = array_pop($plannings);
            $planning->setTimeoutState($easiestPlanning->getTimeoutState());
            $this->planningRepos->save($planning);
        }
    }

    /**
     * @param Input $input
     * @param list<Planning> $plannings
     */
    protected function removePlannings(Input $input, array $plannings): void
    {
        while (count($plannings) > 0) {
            $planning = array_pop($plannings);
            $input->getPlannings()->removeElement($planning);
            $this->planningRepos->remove($planning);
        }
    }

    /**
     * @param Planning $planning
     * @throws Exception
     */
    public function processPlanning(Planning $planning): void
    {
        $schedules = $this->scheduleRepos->findByInput($planning->getInput());
        $this->processPlanningHelper($planning, $schedules);
    }

    protected function getPlanningStateDescription(Planning $planning): string
    {
        if ($planning->getState() !== PlanningState::TimedOut) {
            return $planning->getState()->name;
        }
        $timeoutState = $planning->getTimeoutState();
        if ($timeoutState !== null) {
            return 'timeout-' . $timeoutState->value;
        }
        return '?';
    }
}
