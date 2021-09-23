<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Field;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\GameRound\AgainstCreator as AgainstGameRoundCreator;
use SportsPlanning\GameRound\AgainstGameRound;
use SportsPlanning\GameGenerator\Helper;
use SportsPlanning\GameRound\GameRoundCreator;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;

class Against implements Helper
{
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     */
    public function generate(Poule $poule, array $sports, AssignedCounter $assignedCounter): void
    {
        $maxNrOfGamesPerPlace = 0;
        $sortedSports = $this->sortSportsByEquallyAssigned($poule, $sports);
        foreach ($sortedSports as $sport) {
            $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new Exception('only against-sport-variant accepted', E_ERROR);
            }
            $maxNrOfGamesPerPlace += $sportVariant->getTotalNrOfGamesPerPlace($poule->getPlaces()->count());
            $this->generateGames($poule, $sportVariant, $assignedCounter, $maxNrOfGamesPerPlace);
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

    protected function generateGames(
        Poule $poule,
        AgainstSportVariant $sportVariant,
        AssignedCounter $assignedCounter,
        int $maxNrOfGamesPerPlace
    ): void {
        $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $maxNrOfGamesPerPlace);
        $this->assignHomeAways($assignedCounter, $gameRound);
        $this->gameRoundsToGames($poule, $gameRound);
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

    protected function getGameRound(
        Poule $poule,
        AgainstSportVariant $sportVariant,
        AssignedCounter $assignedCounter,
        int $nrOfGamesPerPlace
    ): AgainstGameRound {
        // (new HomeAwayOutput($this->logger))->outputHomeAways($homeAways);
        $gameRoundCreator = new AgainstGameRoundCreator($sportVariant, $this->logger);
        return $gameRoundCreator->createGameRound($poule, $assignedCounter, $nrOfGamesPerPlace);
    }


    /**
     * @param Poule $poule
     * @param AgainstGameRound $gameRound
     * @throws Exception
     */
    protected function gameRoundsToGames(Poule $poule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new AgainstGame($this->planning, $poule, $this->getDefaultField(), $gameRound->getNumber());
                foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                    foreach ($homeAway->get($side)->getPlaces() as $place) {
                        new AgainstGamePlace($game, $place, $side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }

    protected function getDefaultField(): Field
    {
        if ($this->defaultField === null) {
            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
        }
        return $this->defaultField;
    }
}
