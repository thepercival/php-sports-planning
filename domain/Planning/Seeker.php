<?php

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsPlanning\Game;
use SportsPlanning\GameGenerator;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\GCDService as InputGCDService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Planning\Seeker\NextBatchGamesPlanningCalculator;
use SportsPlanning\Planning\Seeker\NextGamesInARowPlanningCalculator;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Resource\Service as ResourceService;

class Seeker
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var InputRepository
     */
    protected $inputRepos;
    /**
     * @var PlanningRepository
     */
    protected $planningRepos;
    /**
     * @var int|null
     */
    protected $maxTimeoutSeconds;
    protected InputService $inputService;
    protected InputGCDService $inputGCDService;
    protected Output $planningOutput;
    protected Seeker\GCDProcessor $gcdProcessor;
    protected Seeker\BatchGamesPostProcessor $batchGamesPostProcessor;

    public function __construct(LoggerInterface $logger, InputRepository $inputRepos, PlanningRepository $planningRepos)
    {
        $this->logger = $logger;
        $this->planningOutput = new Output($this->logger);
        $this->inputService = new InputService();
        $this->inputGCDService = new InputGCDService();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
        $this->maxTimeoutSeconds = 0;
        $this->gcdProcessor = new Seeker\GCDProcessor($this->logger, $this->inputRepos, $this->planningRepos, $this);
        $this->batchGamesPostProcessor = new Seeker\BatchGamesPostProcessor($this->logger, $this->planningRepos);
    }

    public function enableTimedout( int $maxTimeoutSeconds)
    {
        $this->maxTimeoutSeconds= $maxTimeoutSeconds;
    }

    protected function processTimedout(): bool
    {
        return $this->maxTimeoutSeconds > 0;
    }

    public function process(Input $input)
    {
        try {
            $this->planningOutput->outputInput($input, 'processing input: ', " ..");

            if ($this->inputGCDService->hasGCD($input)) {
                $this->gcdProcessor->process($input);
                return;
            }
            $this->processInput($input);
            // $this->inputRepos->save($input);
        } catch (\Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    protected function processInput(Input $input)
    {
        $nextBatchGamesPlanningCalculator = new NextBatchGamesPlanningCalculator($input, $this->maxTimeoutSeconds );
        while ($planningToBeProcessed = $nextBatchGamesPlanningCalculator->next()) {
            $this->processPlanning($planningToBeProcessed);
            $this->batchGamesPostProcessor->updateOthers($planningToBeProcessed);
        }

        $bestBatchGamePlanning = $input->getBestBatchGamesPlanning();
        if ($bestBatchGamePlanning === null) {
            throw new \Exception("input(" . $input->getId() . ") should always have a best planning after processing");
        }

        if( count($bestBatchGamePlanning->getGamesInARowPlannings()) === 0 ) {
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

    protected function processPlanning(Planning $planning)
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if ($this->processTimedout() ) {
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

        $gameCreator = new GameCreator( $this->logger );
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
}
