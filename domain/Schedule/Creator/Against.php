<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\AllInOneGame as AllInOneGameWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Output\HomeAway;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against\GamesPerPlace as AgainstGppGameRoundCreator;
use SportsPlanning\GameRound\Creator\Against\H2h as AgainstH2hGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Sport;

class Against
{
    public function __construct(protected LoggerInterface $logger, protected int $allowedGppMargin)
    {
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $sports,
        AssignedCounter $assignedCounter,
        int|null $nrOfSecondsBeforeTimeout
    ): void
    {
        $h2hHomeAwayCreator = new H2hHomeAwayCreator();
        $gppHomeAwayCreator = new GppHomeAwayCreator();
        $sortedSports = $this->sortSportsByEquallyAssigned($poule, $sports);
        foreach ($sortedSports as $sport) {
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new \Exception('only against-sport-variant accepted', E_ERROR);
            }
            $sportSchedule = new SportSchedule($schedule, $sport->getNumber(), $sportVariant->toPersistVariant());
            $homeAwayCreator = ($sportVariant instanceof AgainstGpp) ? $gppHomeAwayCreator : $h2hHomeAwayCreator;
            $gameRound = $this->generateGameRounds($poule, $sportVariant, $homeAwayCreator, $assignedCounter, $nrOfSecondsBeforeTimeout);
            $this->createGames($sportSchedule, $gameRound);
        }
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return list<Sport>
     */
    protected function sortSportsByEquallyAssigned(Poule $poule, array $sports): array
    {
        $creator = new VariantCreator();
        $nrOfPlaces = $poule->getPlaces()->count();
        uasort($sports, function (Sport $sportA, Sport $sportB) use ($nrOfPlaces, $creator): int {
            $sportVariantA = $creator->createWithPoule($nrOfPlaces, $sportA->createVariant() );
            $sportVariantB = $creator->createWithPoule($nrOfPlaces, $sportB->createVariant() );
            $allPlacesSameNrOfGamesA = $this->allPlacesSameNrOfGamesAssignable($sportVariantA);
            $allPlacesSameNrOfGamesB = $this->allPlacesSameNrOfGamesAssignable($sportVariantB);
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
        AssignedCounter $assignedCounter,
        int|null $nrOfSecondsBeforeTimeout
    ): AgainstGameRound {
        if ($sportVariant instanceof AgainstGpp && $homeAwayCreator instanceof GppHomeAwayCreator) {
            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger, $this->allowedGppMargin, $nrOfSecondsBeforeTimeout);
            $gameRound = $gameRoundCreator->createGameRound($poule, $sportVariant, $homeAwayCreator, $assignedCounter);
//            $this->logger->info('gameround ' . $gameRound->getNumber());
//            (new HomeAway($this->logger))->outputHomeAways($gameRound->getAllHomeAways() );
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
        $assignedCounter->assignHomeAways($gameRound->getAllHomeAways());
        return $gameRound;
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

    private function allPlacesSameNrOfGamesAssignable(
    AllInOneGameWithPoule|SingleWithPoule|AgainstH2hWithPoule|AgainstGppWithPoule $variantWithPoule): bool
    {
        if( !($variantWithPoule instanceof AgainstGppWithPoule) ) {
            return true;
        }
        return $variantWithPoule->allPlacesSameNrOfGamesAssignable();
    }
}
