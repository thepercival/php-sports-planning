<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\GameRound\Creator\Against as AgainstGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;

class Against implements CreatorInterface
{
    public function __construct(protected LoggerInterface $logger)
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

        $maxNrOfGamesPerPlace = 0;
        $sortedSports = $this->sortSportsByEquallyAssigned($poule, $sports);
        foreach ($sortedSports as $sport) {
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new Exception('only against-sport-variant accepted', E_ERROR);
            }
            $sportSchedule = new SportSchedule($schedule, $sport->getNumber(), $sportVariant->toPersistVariant());
            $maxNrOfGamesPerPlace += $sportVariant->getTotalNrOfGamesPerPlace($nrOfPlaces);
            $gameRound = $this->generateGameRounds($poule, $sportVariant, $assignedCounter, $maxNrOfGamesPerPlace);
            $this->createGames($sportSchedule, $gameRound);
        }
        return $schedule;
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return list<Sport>
     */
    protected function sortSportsByEquallyAssigned(Poule $poule, array $sports): array
    {
        uasort($sports, function (Sport $sportA, Sport $sportB) use ($poule): int {
            $sportVariantA = $sportA->createVariant();
            $sportVariantB = $sportB->createVariant();
            if (!($sportVariantA instanceof AgainstSportVariant) || !($sportVariantB instanceof AgainstSportVariant)) {
                return 0;
            }
            $equallyAssignA = $sportVariantA->mustBeEquallyAssigned($poule->getPlaces()->count());
            $equallyAssignB = $sportVariantB->mustBeEquallyAssigned($poule->getPlaces()->count());
            if (($equallyAssignA && $equallyAssignB) || (!$equallyAssignA && !$equallyAssignB)) {
                return 0;
            }
            return $equallyAssignA ? -1 : 1;
        });
        return array_values($sports);
    }

    protected function generateGameRounds(
        Poule $poule,
        AgainstSportVariant $sportVariant,
        AssignedCounter $assignedCounter,
        int $maxNrOfGamesPerPlace
    ): AgainstGameRound {
        $gameRoundCreator = new AgainstGameRoundCreator($sportVariant, $poule->getInput()->getGamePlaceStrategy(), $this->logger);
        $gameRound = $gameRoundCreator->createGameRound($poule, $assignedCounter, $maxNrOfGamesPerPlace);
        $this->assignHomeAways($assignedCounter, $gameRound);
        return $gameRound;
    }

    protected function assignHomeAways(AssignedCounter $assignedCounter, AgainstGameRound $gameRound): void
    {
        $assignedCounter->assignHomeAways($this->gameRoundsToHomeAways($gameRound));
    }

    /**
     * @param AgainstGameRound $gameRound
     * @return list<AgainstHomeAway>
     */
    protected function gameRoundsToHomeAways(AgainstGameRound $gameRound): array
    {
        $homeAways = $gameRound->getHomeAways();
        while ($gameRound = $gameRound->getNext()) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                array_push($homeAways, $homeAway);
            }
        }
        return $homeAways;
    }

    /**
     * @param list<Sport> $sports
     * @param int $nrOfH2H
     * @return list<Sport>
     */
    protected function filterSports(array $sports, int $nrOfH2H): array
    {
        return array_values(array_filter($sports, function (Sport $sport) use ($nrOfH2H): bool {
            return $sport->getNrOfH2H() >= $nrOfH2H;
        }));
    }

//
//    /**
//     * @param Poule $poule
//     * @param AgainstGameRound $gameRound
//     * @throws Exception
//     */
//    protected function gameRoundsToGames(Poule $poule, AgainstGameRound $gameRound): void
//    {
//        while ($gameRound !== null) {
//            foreach ($gameRound->getHomeAways() as $homeAway) {
//                $game = new AgainstGame($this->planning, $poule, $this->getDefaultField(), $gameRound->getNumber());
//                foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
//                    foreach ($homeAway->get($side)->getPlaces() as $place) {
//                        new AgainstGamePlace($game, $place, $side);
//                    }
//                }
//            }
//            $gameRound = $gameRound->getNext();
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

    protected function createGames(SportSchedule $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new Game($sportSchedule, $gameRound->getNumber());
                foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                    foreach ($homeAway->get($side)->getPlaces() as $place) {
                        $gamePlace = new GamePlace($game, $place->getNumber());
                        $gamePlace->setAgainstSide($side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
