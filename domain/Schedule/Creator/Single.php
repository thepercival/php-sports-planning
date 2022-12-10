<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Exception;
use Psr\Log\LoggerInterface;
use SportsPlanning\GameRound\Creator\Single as SingleGameRoundCreator;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\Combinations\PlaceCombination;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Place;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class Single
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     * @param Schedule\TimeoutState|null $timeoutState
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $sports,
        AssignedCounter $assignedCounter,
        Schedule\TimeoutState|null $timeoutState): void
    {
        foreach ($sports as $sport) {
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof SingleSportVariant)) {
                throw new \Exception('only single-sport-variant accepted', E_ERROR);
            }
            $sportSchedule = new SportSchedule($schedule, $sport->getNumber(), $sportVariant->toPersistVariant());
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
