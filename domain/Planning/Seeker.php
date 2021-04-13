<?php

namespace SportsPlanning\Planning;

use Exception;
use Psr\Log\LoggerInterface;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Planning\Seeker\NextBatchGamesPlanningCalculator;
use SportsPlanning\Planning\Seeker\NextGamesInARowPlanningCalculator;

class Seeker
{
    protected int $maxTimeoutSeconds = 0;
    protected InputService $inputService;
    protected Output $planningOutput;
    protected Seeker\BatchGamesPostProcessor $batchGamesPostProcessor;
    protected bool $throwOnTimeout;

    public function __construct(
        protected LoggerInterface $logger,
        protected InputRepository $inputRepos,
        protected PlanningRepository $planningRepos
    )
    {
        $this->planningOutput = new Output($this->logger);
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
            $this->planningOutput->outputInput($input, 'processing input: ', " ..");
            $this->processInput($input);
            // $this->inputRepos->save($input);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    protected function processInput(Input $input): void
    {
        $nextBatchGamesPlanningCalculator = new NextBatchGamesPlanningCalculator($input, $this->maxTimeoutSeconds);
        while ($planningToBeProcessed = $nextBatchGamesPlanningCalculator->next()) {
            $this->processPlanning($planningToBeProcessed);
            $this->batchGamesPostProcessor->updateOthers($planningToBeProcessed);
        }

        $bestBatchGamePlanning = $input->getBestBatchGamesPlanning();
        if ($bestBatchGamePlanning === null) {
            throw new Exception("input(" . ($input->getId() ?? '?') . ") should always have a best planning after processing", E_ERROR);
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
            $this->processPlanning($planningToBeProcessed);
            $gamesInARowPostProcessor->updateOthers($planningToBeProcessed);
        }
    }

    protected function processPlanning(Planning $planning): void
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
        if (!$this->throwOnTimeout) {
            $gameCreator->disableThrowOnTimeout();
        }
        $newState = $gameCreator->createGames($planning);

        $planning->setState($newState);
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
