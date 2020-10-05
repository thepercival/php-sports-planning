<?php

namespace SportsPlanning\Planning\Seeker;

use Psr\Log\LoggerInterface;
use SportsPlanning\Input\GCDService as InputGCDService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Seeker as PlanningSeeker;
use SportsPlanning\Input;
use SportsPlanning\Game;
use SportsPlanning\Planning\Output as PlanningOutput;

class GCDProcessor
{
    protected LoggerInterface $logger;
    protected InputRepository $inputRepos;
    protected PlanningRepository $planningRepos;
    protected InputGCDService $inputGCDService;
    protected PlanningOutput $planningOutput;
    protected PlanningSeeker $seeker;

    public function __construct(LoggerInterface $logger, InputRepository $inputRepos, PlanningRepository $planningRepos, PlanningSeeker $seeker )
    {
        $this->logger = $logger;
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->inputGCDService = new InputGCDService();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
        $this->seeker = $seeker;
    }

    public function process(Input $input)
    {
        $this->logger->info('   processing as gcd..');
        $gcdInput = $this->inputGCDService->getGCDInput($input);
        $gcdDbInput = $this->inputRepos->getFromInput($gcdInput);
        if ($gcdDbInput === null) {
            $gcdDbInput = $this->inputRepos->save($gcdInput);
            $this->inputRepos->createBatchGamesPlannings($gcdDbInput);
            $this->seeker->process($gcdDbInput);
        }
        $this->inputRepos->removePlannings($input);
        $this->processByGCD($input, $gcdDbInput);
    }

    protected function processByGCD(Input $input, Input $gcdInput)
    {
        $gcdPlanning = $gcdInput->getBestPlanning();
        $this->createPlanningFromGcd($input, $gcdPlanning);
        $this->inputRepos->save($input);
    }

    protected function createPlanningFromGcd(Input $input, Planning $gcdPlanning)
    {
        $gcd = $this->inputGCDService->getGCD($input);

        $planning = new Planning($input, $gcdPlanning->getNrOfBatchGames(), 0 );

        // 5, 4     => (2) => 5, 5, 4, 4
        // 2, 2     => (2) => 2, 2, 2, 2
        // 4, 3, 3  => (3) => 4, 4, 4, 3, 3, 3, 3, 3, 3, 3
        // 2, 2     => (5) => 2, 2, 2, 2, 2, 2, 2, 2, 2, 2
        // 6,4,2    => 6,6,4,4,2,2

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
        $this->planningRepos->save($planning);
    }
}
