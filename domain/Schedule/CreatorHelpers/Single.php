<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\CreatorHelpers;

use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound\Creator\Single as SingleGameRoundCreator;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Single
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param array<int, SingleSportVariant> $sportVariants
     * @param AssignedCounter $assignedCounter
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $sportVariants,
        AssignedCounter $assignedCounter): void
    {
        foreach ($sportVariants as $sportNr => $sportVariant) {
            $sportSchedule = new SportSchedule($schedule, $sportNr, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($poule, $sportVariant, $assignedCounter);
            $this->createGames($sportSchedule, $gameRound);
        }
    }

    protected function generateGameRounds(
        Poule $poule,
        SingleSportVariant $sportVariant,
        AssignedCounter $assignedCounter
    ): TogetherGameRound {
        $gameRoundCreator = new SingleGameRoundCreator($this->logger);
        $gameRound = $gameRoundCreator->createGameRound($poule, $sportVariant, $assignedCounter);

        // $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $totalNrOfGamesPerPlace);
        $this->assignPlaceCombinations($assignedCounter, $gameRound);
        return $gameRound;
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return list<PlaceCombination>
     */
    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
    {
        /** @var array<int, list<Place>> $combinationsTmp */
        $combinationsTmp = $combinations->toArray();
        return array_values(array_map(
            function (array $places): PlaceCombination {
                return new PlaceCombination($places);
            },
            $combinationsTmp
        ));
    }

    protected function assignPlaceCombinations(AssignedCounter $assignedCounter, TogetherGameRound $gameRound): void
    {
        $assignedCounter->assignTogether($gameRound->toPlaceCombinations());
    }

//    /**
//     * @param TogetherGameRound $gameRound
//     * @return list<PlaceCombination>
//     */
//    protected function gameRoundsToPlaceCombinations(TogetherGameRound $gameRound): array
//    {
//        $placeCombinations = $gameRound->getPlaceCombinations();
//        while ($gameRound = $gameRound->getNext()) {
//            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
//                array_push($placeCombinations, $placeCombination);
//            }
//        }
//        return $placeCombinations;
//    }

//    /**
//     * @param Poule $poule
//     * @return array<string|int, PlaceCounter>
//     */
//    protected function getPlaceCounterMap(Poule $poule): array
//    {
//        $placeCounterMap = [];
//        foreach ($poule->getPlaces() as $place) {
//            $placeCounterMap[$place->getNumber()] = new PlaceCounter($place);
//        }
//        return $placeCounterMap;
//    }

//    protected function getDefaultField(): Field
//    {
//        if ($this->defaultField === null) {
//            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
//        }
//        return $this->defaultField;
//    }

//    /**
//     * @param Poule $poule
//     * @param SingleGameRound $gameRound
//     * @throws Exception
//     */
//    protected function gameRoundsToGames(Poule $poule, SingleGameRound $gameRound): void
//    {
//        $placeCounterMap = $this->getPlaceCounterMap($poule);
//        while ($gameRound !== null) {
//            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
//                $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
//                foreach ($placeCombination->getPlaces() as $place) {
//                    $placeCounter = $placeCounterMap[$place->getNumber()];
//                    new TogetherGamePlace($game, $place, $placeCounter->increment());
//                }
//            }
//            $gameRound = $gameRound->getNext();
//        }
//    }

    protected function createGames(SportSchedule $sportSchedule, TogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new Game($sportSchedule);
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $gamePlace = new GamePlace($game, $gameRoundGamePlace->getPlace()->getNumber());
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
