<?php

declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output\Color;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Seeker\NextBatchGamesPlanningCalculator;
use SportsPlanning\Seeker\NextGamesInARowPlanningCalculator;
use SportsPlanning\Schedule\Repository as ScheduleRepository;

class Seeker
{
    protected int $maxTimeoutSeconds = 0;
    protected InputService $inputService;
    protected PlanningOutput $planningOutput;
    protected Seeker\BatchGamesPostProcessor $batchGamesPostProcessor;
    protected bool $throwOnTimeout;

    use Color;

    public function __construct(
        protected LoggerInterface $logger,
        protected InputRepository $inputRepos,
        protected PlanningRepository $planningRepos,
        protected ScheduleRepository $scheduleRepos
    ) {
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->inputService = new InputService();
        $this->batchGamesPostProcessor = new Seeker\BatchGamesPostProcessor($this->logger, $this->planningRepos);
        $this->throwOnTimeout = true;
    }

    public function enableTimedout(int $maxTimeoutSeconds): void
    {
        $this->maxTimeoutSeconds= $maxTimeoutSeconds;
    }

    protected function processTimedout(): bool
    {
        return $this->maxTimeoutSeconds > 0;
    }

    public function processInput(Input $input): void
    {
        try {
            $this->planningOutput->outputInput($input, 'processing input('.((string)$input->getId()).'): ', " ..");
            $schedules = $this->scheduleRepos->findByInput($input);
            $this->processInputHelper($input, $schedules);
            // $this->inputRepos->save($input);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    /**
     * @param Input $input
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processInputHelper(Input $input, array $schedules): void
    {
        $nextBatchGamesPlanningCalculator = new NextBatchGamesPlanningCalculator($input, $this->maxTimeoutSeconds);
        $this->logger->info('       -- ---------- start processing batchGames-plannings ----------');
        while ($planningToBeProcessed = $nextBatchGamesPlanningCalculator->next()) {
            $this->processPlanningHelper($planningToBeProcessed, $schedules);
            $this->batchGamesPostProcessor->updateOthers($planningToBeProcessed);
        }

        $bestBatchGamePlannings = $input->getBatchGamesPlannings(Planning::STATE_SUCCEEDED);
        $bestBatchGamePlanning = reset($bestBatchGamePlannings);
        if ($bestBatchGamePlanning === false) {
            throw new Exception("input(" . (string)$input->getId() . ") should always have a best planning after processing", E_ERROR);
        }

        if (count($bestBatchGamePlanning->getGamesInARowPlannings()) === 0) {
            $this->planningRepos->createGamesInARowPlannings($bestBatchGamePlanning);
        }

        $gamesInARowPostProcessor = new Seeker\GamesInARowPostProcessor(
            $bestBatchGamePlanning,
            $this->logger,
            $this->planningRepos
        );
        $nextGamesInARowPlanningCalculator = new NextGamesInARowPlanningCalculator(
            $bestBatchGamePlanning,
            $this->maxTimeoutSeconds
        );
        $this->logger->info('       -- ---------- start processing gamesInARow-plannings ----------');
        while ($planningToBeProcessed = $nextGamesInARowPlanningCalculator->next()) {
            $this->processPlanningHelper($planningToBeProcessed, $schedules);
            $gamesInARowPostProcessor->updateOthers($planningToBeProcessed);
        }
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    public function processPlanning(Planning $planning, array $schedules): void
    {
        $this->planningRepos->resetPlanning($planning, Planning::STATE_TOBEPROCESSED);
        if ($planning->isBatchGames()) {
            $this->processPlanningHelper($planning, $schedules);
            $this->batchGamesPostProcessor->updateOthers($planning);
            return;
        }
        $gamesInARowPostProcessor = new Seeker\GamesInARowPostProcessor(
            $planning,
            $this->logger,
            $this->planningRepos
        );
        $this->processPlanningHelper($planning, $schedules);
        $gamesInARowPostProcessor->updateOthers($planning);
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processPlanningHelper(Planning $planning, array $schedules): void
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if ($this->processTimedout()) {
            $this->planningOutput->output(
                $planning,
                false,
                '   ',
                " timeout => " . $planning->getTimeoutSeconds() * Planning::TIMEOUT_MULTIPLIER
            );
            $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * Planning::TIMEOUT_MULTIPLIER);
            $this->planningRepos->save($planning);
        }
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

        $stateDescription = $planning->getState() === Planning::STATE_FAILED ? "failed" :
            ($planning->getState() === Planning::STATE_TIMEDOUT ? "timeout(" . $planning->getTimeoutSeconds(
                ) . ")" : "success");

        $this->logger->info('   ' . '   ' . " => " . $stateDescription);
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
