<?php
declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use Psr\Log\LoggerInterface;
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

    public function process(Input $input): void
    {
        try {
            $this->planningOutput->outputInput($input, 'processing input('.((string)$input->getId()).'): ', " ..");
            $schedules = $this->scheduleRepos->findByInput($input);
            $this->processInput($input, $schedules);
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
    protected function processInput(Input $input, array $schedules): void
    {
        $nextBatchGamesPlanningCalculator = new NextBatchGamesPlanningCalculator($input, $this->maxTimeoutSeconds);
        while ($planningToBeProcessed = $nextBatchGamesPlanningCalculator->next()) {
            $this->processPlanning($planningToBeProcessed, $schedules);
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
        while ($planningToBeProcessed = $nextGamesInARowPlanningCalculator->next()) {
            $this->processPlanning($planningToBeProcessed, $schedules);
            $gamesInARowPostProcessor->updateOthers($planningToBeProcessed);
        }
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processPlanning(Planning $planning, array $schedules): void
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
