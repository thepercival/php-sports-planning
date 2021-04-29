<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator\GameMode;

use Exception;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Field;
use SportsPlanning\GameGenerator\AgainstHomeAway;
use SportsPlanning\GameGenerator\AssignedCounterMap;
use SportsPlanning\GameGenerator\HomeAwayList;
use SportsPlanning\GameGenerator\PlaceAssignedCounter;
use SportsPlanning\GameGenerator\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\GameGenerator\GameMode as GameModeGameGenerator;

class Against implements GameModeGameGenerator
{
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning)
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return int
     */
    public function generate(Poule $poule, array $sports): int
    {
        $nrOfH2H = 1;
        while ($sports = $this->filterSports($sports, $nrOfH2H)) {
            foreach ($sports as $sport) {
                $this->defaultField = $sport->getField(1);
                $sportVariant = $sport->createVariant();
                if (!($sportVariant instanceof AgainstSportVariant)) {
                    throw new Exception('only against-sport-variant accepted', E_ERROR);
                }
                if ($sportVariant->isMixed()) {
                    continue;
                }
                $homeAways = $this->generateHomeAways($poule, $sportVariant);
                $this->toGames($poule, $homeAways, $nrOfH2H);
            }
            $nrOfH2H++;
        }
        return Planning::STATE_SUCCEEDED;
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

    /**
     * @param Poule $poule
     * @param AgainstSportVariant $sportVariant
     * @return list<AgainstHomeAway>
     */
    public function generateHomeAways(Poule $poule, AgainstSportVariant $sportVariant): array
    {
        $places = array_values($poule->getPlaces()->toArray());
        $homeCombinations = $this->toPlaceCombinations(
            new CombinationsGenerator($places, $sportVariant->getNrOfHomePlaces())
        );

        $awayCombinationsMap = [];
        foreach ($homeCombinations as $homeCombination) {
            $awayCombinationsMap[$homeCombination->getNumber()] = [];

            $availableAwayPlaces = $this->getOtherPlaces($places, $homeCombination);
            $awayCombinations = $this->toPlaceCombinations(
                new CombinationsGenerator($availableAwayPlaces, $sportVariant->getNrOfAwayPlaces())
            );
            foreach ($awayCombinations as $awayCombination) {
                if ($homeCombination->hasOverlap($awayCombination)) {
                    continue;
                }
                array_push($awayCombinationsMap[$homeCombination->getNumber()], $awayCombination);
            }
        }
        $assignedCounterMap = new AssignedCounterMap($poule, $sportVariant);
        $games = [];
        while ($homeCombination = array_shift($homeCombinations)) {
            $awayCombination = array_shift($awayCombinationsMap[$homeCombination->getNumber()]);
            if ($awayCombination === null) {
                continue;
            }
            $swap = count($awayCombinationsMap[$homeCombination->getNumber()]) % 2 === 0;
            if (!$this->gameExists($games, $homeCombination, $awayCombination)) {
                $game = $assignedCounterMap->createGame($homeCombination, $awayCombination);
                $swap ? array_unshift($games, $game) : array_push($games, $game);
            }
            array_push($homeCombinations, $homeCombination);
        }
        // echo $this->outputHomeAways($games, 'SHUFFLED HOMEAWAYS');
        return $games;

//        $homeAwayList = new HomeAwayList($games);
//        echo $this->outputHomeAways($games, 'GENERATED HOMEAWAYS');
//        $newGames = $homeAwayList->getHomeAways();
//        echo $this->outputHomeAways($newGames, 'SHUFFLED HOMEAWAYS');
//        return $newGames;
    }

    /**
     * @param list<AgainstHomeAway> $homeAwys
     * @param string $header
     * @return string
     */
    protected function outputHomeAways(array $homeAwys, string $header): string
    {
        $output = $header . PHP_EOL;
        foreach ($homeAwys as $game) {
            $home = join(
                ' & ',
                array_map(
                    function ($place): string {
                        return $place->getLocation();
                    },
                    $game->get(AgainstSide::HOME)->getPlaces()
                )
            );
            $away = join(
                ' & ',
                array_map(
                    function ($place): string {
                        return $place->getLocation();
                    },
                    $game->get(AgainstSide::AWAY)->getPlaces()
                )
            );
            $output .= $home . ' vs ' . $away . PHP_EOL;
        }
        return $output;
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
            function (array $placeCombination): PlaceCombination {
                return new PlaceCombination($placeCombination);
            },
            $combinationsTmp
        ));
    }

    public function getSwappedSide(int $side): int
    {
        return $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
    }



    /**
     * @param list<AgainstHomeAway> $games
     * @param PlaceCombination $home
     * @param PlaceCombination $away
     * @return bool
     */
    protected function gameExists(array $games, PlaceCombination $home, PlaceCombination $away): bool
    {
        $game = new AgainstHomeAway($home, $away);
        foreach ($games as $gameIt) {
            if ($gameIt->equals($game)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<Place> $places
     * @param PlaceCombination $placeCombination
     * @return list<Place>
     */
    protected function getOtherPlaces(array $places, PlaceCombination $placeCombination): array
    {
        return array_values(array_filter(
            $places,
            function (Place $placeIt) use ($placeCombination): bool {
                return !$placeCombination->has($placeIt);
            }
        ));
    }

    /**
     * @param Poule $poule
     * @param list<AgainstHomeAway> $homeAways
     * @param int $nrOfH2H
     */
    protected function toGames(Poule $poule, array $homeAways, int $nrOfH2H): void
    {
        if (($nrOfH2H % 2) === 0) {
            $homeAways = array_values(array_reverse($homeAways));
        }
        foreach ($homeAways as $homeAway) {
            $game = new AgainstGame($this->planning, $poule, $this->getDefaultField(), $nrOfH2H);
            foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAwayValue) {
                foreach ($homeAway->get($homeAwayValue)->getPlaces() as $place) {
                    $validHomeAway = (($nrOfH2H % 2) === 1) ? $homeAwayValue : $this->getSwappedSide($homeAwayValue);
                    new AgainstGamePlace($game, $place, $validHomeAway);
                }
            }
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
