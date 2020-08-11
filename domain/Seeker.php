<?php

namespace SportsPlanning;

use Psr\Log\LoggerInterface;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Repository as PlanningRepository;
use SportsPlanning\Output;

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
     * @var InputService
     */
    protected $inputService;
    /**
     * @var Service
     */
    protected $planningService;
    /**
     * @var Output
     */
    protected $planningOutput;

    public function __construct(LoggerInterface $logger, InputRepository $inputRepos, PlanningRepository $planningRepos)
    {
        $this->logger = $logger;
        $this->planningOutput = new Output($this->logger);
        $this->inputService = new InputService();
        $this->planningService = new Service();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function process(Input $input)
    {
        try {
            $this->planningOutput->outputInput($input, 'processing input: ', " ..");

            if ($this->inputService->hasGCD($input)) {
                $this->logger->info('   gcd found ..');
                $gcdInput = $this->inputService->getGCDInput($input);
                $gcdDbInput = $this->inputRepos->getFromInput($gcdInput);
                if ($gcdDbInput === null) {
                    $this->logger->info('   gcd not found in db, now creating ..');
                    $gcdDbInput = $this->inputRepos->save($gcdInput);
                }
                $this->process($gcdDbInput);
                return $this->processByGCD($input, $gcdDbInput);
            }
            $this->processHelper($input);
        } catch (\Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    protected function processByGCD(Input $input, Input $gcdInput)
    {
        $gcdPlanning = $this->planningService->getBestPlanning($gcdInput);
        $planning = $this->createPlanningFromGcd($input, $gcdPlanning);

        $this->planningRepos->save($planning);

        $input->setState(Input::STATE_ALL_PLANNINGS_TRIED);
        $this->inputRepos->save($input);
        $this->logger->info('   update state => STATE_ALL_PLANNINGS_TRIED');
    }

    // to seperate class
    public function createPlanningFromGcd(Input $input, Planning $gcdPlanning): Planning
    {
        $gcd = $this->inputService->getGCD($input);

        $planning = new Planning($input, $gcdPlanning->getNrOfBatchGames(), $gcdPlanning->getMaxNrOfGamesInARow());

        // 5, 4 => (2) => 5, 5, 4, 4

        // 2, 2 => (2) => 2, 2, 2, 2

        // 4, 3, 3 => (3) => 4, 4, 4, 3, 3, 3, 3, 3, 3, 3

        // 2, 2 => (5) => 2, 2, 2, 2, 2, 2, 2, 2, 2, 2
//        6,4,2 => 6,6,4,4,2,2

        $getNewPouleNr = function (int $gcdIteration, int $gcdPouleNr) use ($gcd): int {
            return ((($gcdPouleNr - 1) * $gcd) + $gcdIteration);
        };

        $gcdInput = $gcdPlanning->getInput();

        foreach ($gcdPlanning->getGames() as $gcdGame) {
            for ($iteration = 0; $iteration < $gcd; $iteration++) {
                $newPouleNr = $getNewPouleNr($iteration + 1, $gcdGame->getPoule()->getNumber());
                $poule = $planning->getPoule($newPouleNr);
                $game = new Game($poule, $gcdGame->getRoundNr(), $gcdGame->getSubNr(), $gcdGame->getNrOfHeadtohead());
                $game->setBatchNr($gcdGame->getBatchNr());

                if ($gcdGame->getReferee() !== null) {
                    $refereeNr = ((($gcd - $iteration) * $gcdInput->getNrOfReferees()) + 1) - $gcdGame->getReferee(
                        )->getNumber();
                    $game->setReferee($planning->getReferee($refereeNr));
                }
                // @TODO use also startindex as with poulenr when doing multiple sports
                $fieldNr = ($iteration * $gcdInput->getNrOfFields()) + $gcdGame->getField()->getNumber();
                $game->setField($planning->getField($fieldNr));

                foreach ($gcdGame->getPlaces() as $gcdGamePlace) {
                    $place = $poule->getPlace($gcdGamePlace->getPlace()->getNumber());
                    $gamePlace = new Game\Place($game, $place, $gcdGamePlace->getHomeaway());
                }
            }
        }

        // $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " timeout => " . $planning->getTimeoutSeconds() * Planning::TIMEOUT_MULTIPLIER  );
        $planning->setState($gcdPlanning->getState());
        $planning->setTimeoutSeconds(-1);
        return $planning;
    }

    public function processTimeout(Planning $planning)
    {
        try {
            $this->processPlanning($planning, true);
            if ($planning->getState() !== Planning::STATE_SUCCESS || $planning->getMaxNrOfGamesInARow() === 1) {
                return;
            }
            $nextPlanning = $planning->getInput()->getPlanning($planning->getNrOfBatchGames(), $planning->getMaxNrOfGamesInARow() - 1);
            if ($nextPlanning !== null) {
                return;
            }
            $nextPlanning = $this->planningService->createNextNInARow($planning);
            $nextPlanning->setState(Planning::STATE_TIMEOUT);
            $this->planningRepos->save($nextPlanning);
        } catch (\Exception $e) {
            $this->logger->error('   ' . '   ' .  " => " . $e->getMessage());
        }
    }

    protected function processHelper(Input $input)
    {
        if ($input->getState() === Input::STATE_CREATED) {
            $input->setState(Input::STATE_TRYING_PLANNINGS);
            $this->inputRepos->save($input);
            $this->logger->info('   update state => STATE_TRYING_PLANNINGS');
        }

        $minIsMaxPlanning = $this->planningService->getMinIsMax($input, Planning::STATE_SUCCESS);
        if ($minIsMaxPlanning === null) {
            $minIsMaxPlanning = $this->planningService->createNextMinIsMaxPlanning($input);
            $this->processPlanning($minIsMaxPlanning, false);
            return $this->processHelper($input);
        }

        $planningMaxPlusOne = null;
        if ($minIsMaxPlanning->getMaxNrOfBatchGames() < $minIsMaxPlanning->getInput()->getMaxNrOfBatchGames()) {
            $planningMaxPlusOne = $this->planningService->getPlusOnePlanning($minIsMaxPlanning);
            if ($planningMaxPlusOne === null) {
                $planningMaxPlusOne = $this->planningService->createPlusOnePlanning($minIsMaxPlanning);
                $this->processPlanning($planningMaxPlusOne, false);
                return $this->processHelper($input);
            }
        }

        /** $minIsMaxPlanning bestaat altijd, dit bepaalt eindsucces */
        if (
                ($planningMaxPlusOne === null && ($minIsMaxPlanning->getState() === Planning::STATE_SUCCESS))
            ||
                ($planningMaxPlusOne !== null && ($planningMaxPlusOne->getState() === Planning::STATE_SUCCESS))
            ||
                ($planningMaxPlusOne !== null && ($planningMaxPlusOne->getState() !== Planning::STATE_SUCCESS) && ($minIsMaxPlanning->getState() === Planning::STATE_SUCCESS))
        ) {
            $planning = ($planningMaxPlusOne !== null && $planningMaxPlusOne->getState() === Planning::STATE_SUCCESS) ? $planningMaxPlusOne : $minIsMaxPlanning;

            $planningNextInARow =  $this->planningService->createNextInARowPlanning($planning);
            if ($planningNextInARow !== null) {
                $this->processPlanning($planningNextInARow, false);
                return $this->processHelper($input);
            }
        }

        $input->setState(
            $input->selfRefereeEnabled(
            ) ? Input::STATE_UPDATING_BESTPLANNING_SELFREFEE : Input::STATE_ALL_PLANNINGS_TRIED
        );
        $this->inputRepos->save($input);
        $info = $input->selfRefereeEnabled() ? 'STATE_UPDATING_BESTPLANNING_SELFREFEE' : 'STATE_ALL_PLANNINGS_TRIED';
        $this->logger->info('   update state => ' . $info);
    }

    protected function processPlanning(Planning $planning, bool $timeout)
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if ($timeout) {
            $this->planningOutput->output($planning, $timeout, '   ', " timeout => " . $planning->getTimeoutSeconds() * Planning::TIMEOUT_MULTIPLIER);
            $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * Planning::TIMEOUT_MULTIPLIER);
            $this->planningRepos->save($planning);
        }
        $this->planningOutput->output($planning, $timeout, '   ', " trying .. ");

        $planningService = new Service();
        $newState = $planningService->createGames($planning);
        $planning->setState($newState);
        $this->planningRepos->save($planning);
        if ($planning->getMaxNrOfBatchGames() === 1 && $planning->getState() !== Planning::STATE_SUCCESS
        && $planning->getMaxNrOfGamesInARow() === $planning->getInput()->getMaxNrOfGamesInARow()) {
            throw new \Exception('this planning shoud always be successful', E_ERROR);
        }

        $stateDescription = $planning->getState() === Planning::STATE_FAILED ? "failed" :
            ($planning->getState() === Planning::STATE_TIMEOUT ? "timeout(".$planning->getTimeoutSeconds().")" : "success");

        $this->logger->info('   ' . '   ' .  " => " . $stateDescription);
    }
}
