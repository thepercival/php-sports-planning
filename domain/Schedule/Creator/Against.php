<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\Output\HomeAway;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against\GamesPerPlace as AgainstGppGameRoundCreator;
use SportsPlanning\GameRound\Creator\Against\H2h as AgainstH2hGameRoundCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Sport;

class Against
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     * @throws Exception
     */
    public function createSportSchedules(Schedule $schedule, Poule $poule, array $sports, AssignedCounter $assignedCounter): void
    {
        $h2hHomeAwayCreator = new H2hHomeAwayCreator($poule);
        $gppHomeAwayCreator = new GppHomeAwayCreator($poule);
        $sortedSports = $this->sortSportsByEquallyAssigned($poule, $sports);
        foreach ($sortedSports as $sport) {
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new Exception('only against-sport-variant accepted', E_ERROR);
            }
            $sportSchedule = new SportSchedule($schedule, $sport->getNumber(), $sportVariant->toPersistVariant());
            $homeAwayCreator = ($sportVariant instanceof AgainstGpp) ? $gppHomeAwayCreator : $h2hHomeAwayCreator;
            $gameRound = $this->generateGameRounds($poule, $sportVariant, $homeAwayCreator, $assignedCounter);
            $this->createGames($sportSchedule, $gameRound);
        }
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
            if (!($sportVariantA instanceof AgainstGpp) || !($sportVariantB instanceof AgainstGpp)) {
                return 0;
            }
            $allPlacesSameNrOfGamesA = $sportVariantA->allPlacesPlaySameNrOfGames($poule->getPlaces()->count());
            $allPlacesSameNrOfGamesB = $sportVariantB->allPlacesPlaySameNrOfGames($poule->getPlaces()->count());
            if (($allPlacesSameNrOfGamesA && $allPlacesSameNrOfGamesB)
                || (!$allPlacesSameNrOfGamesA && !$allPlacesSameNrOfGamesB)) {
                return 0;
            }
            return $allPlacesSameNrOfGamesA ? -1 : 1;
        });
        return array_values($sports);
    }

    protected function generateGameRounds(
        Poule $poule,
        AgainstH2h|AgainstGpp $sportVariant,
        H2hHomeAwayCreator|GppHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter
    ): AgainstGameRound {
        if ($sportVariant instanceof AgainstGpp && $homeAwayCreator instanceof GppHomeAwayCreator) {
            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound($poule, $sportVariant, $homeAwayCreator, $assignedCounter);
//            $this->logger->info('gameround ' . $gameRound->getNumber());
//            (new HomeAway($this->logger))->outputHomeAways( $this->gameRoundsToHomeAways($gameRound) );
//            $ss = 12;
        } else {
            if ($sportVariant instanceof AgainstH2h && $homeAwayCreator instanceof H2hHomeAwayCreator) {
                $gameRoundCreator = new AgainstH2hGameRoundCreator($this->logger);
                $gameRound = $gameRoundCreator->createGameRound(
                    $poule,
                    $sportVariant,
                    $homeAwayCreator,
                    $assignedCounter
                );
            } else {
                throw new \Exception('unkown homeawaycreator', E_ERROR);
            }
        }
        $this->assignHomeAways($assignedCounter, $gameRound);
        return $gameRound;
    }

    protected function assignHomeAways(AssignedCounter $assignedCounter, AgainstGameRound $gameRound): void
    {
        $assignedCounter->assignAgainstHomeAways($this->gameRoundsToHomeAways($gameRound));
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

    protected function createGames(SportSchedule $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new Game($sportSchedule, $gameRound->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
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
