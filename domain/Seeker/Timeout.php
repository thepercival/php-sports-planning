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
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Repository as ScheduleRepository;

class Timeout
{
    use Color;

    private const TIMEOUT_MULTIPLIER = 6;
    protected int $maxTimeoutSeconds = 0;
    protected InputService $inputService;
    protected PlanningOutput $planningOutput;
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
    }

    public function setMaxTimeoutSeconds(int $maxTimeoutSeconds): void
    {
        $this->maxTimeoutSeconds = $maxTimeoutSeconds;
    }

    public function process(Input $input): bool
    {
        $succeeded = $this->processEqualBatchGames($input);
        if ($succeeded) {
            return $succeeded;
        }
        $succeeded = $this->processUnequalBatchGames($input);
        if ($succeeded) {
            return $succeeded;
        }
        return $this->processGamesInARow($input);
    }

    /**
     * @param Input $input
     * @param list<Schedule> $schedules
     * @return bool
     */
    public function processEqualBatchGames(Input $input): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $plannings = $input->getEqualBatchGamesPlannings(PlanningState::TimedOut);
            return $this->processHelper($input, $plannings, $schedules);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    /**
     * @param Input $input
     * @param list<Planning> $plannings
     * @param list<Schedule> $schedules
     * @return bool
     */
    public function processHelper(Input $input, array $plannings, array $schedules): bool
    {
        $easiestPlanning = $this->getFirstTimedoutPlanning($plannings);
        if ($easiestPlanning === null) {
            return false;
        }
        $this->processPlanningHelper($easiestPlanning, $schedules);
        if ($easiestPlanning->getState() === PlanningState::Succeeded) {
            return true;
        }
        if ($easiestPlanning->getState() === PlanningState::TimedOut) {
            $this->updateTimeoutPlannings($easiestPlanning->getTimeoutSeconds(), $plannings);
        } elseif ($easiestPlanning->getState() === PlanningState::Failed) {
            $this->removePlannings($input, $plannings);
        }
        return false;
    }

    /**
     * @param list<Planning> $plannings
     * @return Planning|null
     */
    protected function getFirstTimedoutPlanning(array &$plannings): Planning|null
    {
        $easiestPlanning = array_pop($plannings);
        if ($easiestPlanning !== null && $easiestPlanning->getTimeoutSeconds() <= $this->maxTimeoutSeconds) {
            return $easiestPlanning;
        }
        return null;
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processPlanningHelper(Planning $planning, array $schedules): void
    {
        $this->planningOutput->output(
            $planning,
            false,
            '   ',
            " timeout => " . $planning->getTimeoutSeconds() * self::TIMEOUT_MULTIPLIER
        );
        $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * self::TIMEOUT_MULTIPLIER);
        // $this->planningRepos->save($planning);

        $this->planningOutput->output($planning, false, '   ', " trying .. ");

        $gameCreator = new GameCreator($this->logger);
        $gameCreator->createGames($planning, $schedules);
        // $this->planningRepos->save($planning);

        $gameAssigner = new GameAssigner($this->logger);
        if (!$this->throwOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        $gameAssigner->assignGames($planning);
        $this->planningRepos->save($planning);

//        $planningOutput = new PlanningOutput($this->logger);
//        $planningOutput->outputWithGames($planning, false);
//        $planningOutput->outputWithTotals($planning, false);

        $stateDescription = $planning->getState()->name;
        if ($planning->getState() === PlanningState::TimedOut) {
            $stateDescription .= '(' . $planning->getTimeoutSeconds() . ')';
        }
        $this->logger->info('   ' . '   ' . " => " . $stateDescription);
    }

    /**
     * @param Input $input
     * @param list<Planning> $plannings
     */
    protected function updateTimeoutPlannings(int $nrOfTimeoutSeconds, array $plannings): void
    {
        while (count($plannings) > 0) {
            $planning = array_pop($plannings);
            $planning->setTimeoutSeconds($nrOfTimeoutSeconds);
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
     * @param Input $input
     * @param list<Schedule> $schedules
     * @return bool
     */
    public function processUnequalBatchGames(Input $input): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $plannings = $input->getUnequalBatchGamesPlannings(PlanningState::TimedOut);
            return $this->processHelper($input, $plannings, $schedules);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    /**
     * @param Input $input
     * @return bool
     */
    public function processGamesInARow(Input $input): bool
    {
        try {
            $schedules = $this->scheduleRepos->findByInput($input);

            $bestBatchGamesPlanning = $input->getBestPlanning(PlanningType::BatchGames);

            $plannings = $bestBatchGamesPlanning->getGamesInARowPlannings(PlanningState::TimedOut);
            return $this->processHelper($input, $plannings, $schedules);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
        return false;
    }

    public function processBatchGames(Input $input): bool
    {
        $succeeded = $this->processEqualBatchGames($input);
        if ($succeeded) {
            return $succeeded;
        }
        return $this->processUnequalBatchGames($input);
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

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
