<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Exception;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\GameRound\Together\GamePlace as TogetherGamePlace;
use SportsPlanning\GameRound\Together\Game as TogetherGame;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class AllInOneGame implements CreatorInterface
{
    public function __construct()
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     * @return Schedule
     * @throws Exception
     */
    public function create(Poule $poule, array $sports, AssignedCounter $assignedCounter): Schedule
    {
        $nrOfPlaces = $poule->getPlaces()->count();
        $schedule = new Schedule($nrOfPlaces, $poule->getInput());

        foreach ($sports as $sport) {
            // $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AllInOneGameSportVariant)) {
                throw new \Exception('only allinonegame-sport-variant accepted', E_ERROR);
            }

            $sportSchedule = new SportSchedule($schedule, $sport->getNumber(), $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($poule, $sportVariant);
            $this->createGames($sportSchedule, $gameRound);
        }
        return $schedule;
    }

    protected function generateGameRounds(Poule $poule, AllInOneGameSportVariant $sportVariant): TogetherGameRound
    {
        /** @var TogetherGameRound|null $previous */
        $previous = null;
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $sportVariant->getNrOfGamesPerPlace() ; $gameRoundNumber++) {
            $gameRound = $previous === null ? new TogetherGameRound() : $previous->createNext();

            $gamePlaces = [];
            foreach ($poule->getPlaces() as $place) {
                $gamePlaces[] = new TogetherGamePlace($gameRoundNumber, $place);
            }
            new TogetherGame($gameRound, $gamePlaces);

            $previous = $gameRound;
        }
        if (!isset($gameRound)) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }
        /** @var TogetherGameRound $gameRound */
        return $gameRound->getFirst();
    }


//    /**
//     * @param Poule $poule
//     * @param AllInOneGameSportVariant $sportVariant
//     */
//    protected function generateForSportVariant(Poule $poule, AllInOneGameSportVariant $sportVariant): void
//    {
//        for ($gameRoundNumber = 1; $gameRoundNumber <= $sportVariant->getNrOfGamesPerPlace() ; $gameRoundNumber++) {
//            $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
//            foreach ($poule->getPlaces() as $place) {
//                new TogetherGamePlace($game, $place, $gameRoundNumber);
//            }
//        }
//    }
//
//    protected function getDefaultField(): Field
//    {
//        if ($this->defaultField === null) {
//            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
//        }
//        return $this->defaultField;
//    }

    protected function createGames(SportSchedule $sportSchedule, TogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new Game($sportSchedule);
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $gamePlace = new GamePlace($game, $gameRoundGamePlace->getPlace()->getNumber());
                    $gamePlace->setGameRoundNumber($gamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}