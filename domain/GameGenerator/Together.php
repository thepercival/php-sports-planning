<?php

namespace SportsPlanning\GameGenerator;

use SportsHelpers\GameMode;
use SportsPlanning\Place;
use SportsPlanning\Game\Together as TogetherGame;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Together
{
    private TogetherCounter $togetherCounter;

    public function __construct()
    {
        $this->togetherCounter = new TogetherCounter();
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return list<TogetherGame>
     */
    public function generate(Poule $poule, array $sports): array
    {
        $this->togetherCounter->addPlaces($poule);
        $games = [];
        foreach ($sports as $sport) {
            if ($sport->getGameMode() !== GameMode::TOGETHER) {
                continue;
            }
            $games = array_merge($games, $this->generateForSport($poule, $sport));
        }
        return array_values($games);
    }

    /**
     * @param Poule $poule
     * @param Sport $sport
     * @return list<TogetherGame>
     */
    protected function generateForSport(Poule $poule, Sport $sport): array
    {
        $places = array_values($poule->getPlaces()->toArray());
        $gameAmount = $sport->getGameAmount();
        $nrOfGamePlaces = $sport->getNrOfGamePlaces();
        if ($sport->allPlacesAreGamePlaces()) {
            $nrOfGamePlaces = count($places);
        }

        $createGame = function (int &$gameRoundNumber, array &$gameRoundPlaces) use ($poule, $sport, $places, $nrOfGamePlaces, $gameAmount): TogetherGame|null {
            $nextGameRoundPlaces = null;
            if (count($gameRoundPlaces) < $nrOfGamePlaces) {
                if (++$gameRoundNumber <= $gameAmount) {
                    $nextGameRoundPlaces = $this->createGameRoundPlaces($gameRoundNumber, $places);
                }
            }
            if (count($gameRoundPlaces) === 0 && $gameRoundNumber > $gameAmount) {
                return null;
            }
            /** @var list<GameRoundPlace> $gameRoundPlaces */
            return $this->selectPlaces($poule, $sport, $nrOfGamePlaces, $gameRoundPlaces, $nextGameRoundPlaces);
        };

        $games = [];
        $gameRoundNumber = 1;
        $gameRoundNumberPlaces = $this->createGameRoundPlaces(1, $places);
        while ($game = $createGame($gameRoundNumber, $gameRoundNumberPlaces)) {
            $games[] = $game;
        }
        return $games;
    }

    /**
     * @param int $gameRoundNumber
     * @param list<Place> $places
     * @return list<GameRoundPlace>
     */
    protected function createGameRoundPlaces(int $gameRoundNumber, array $places): array
    {
        $gameRoundPlaces = [];
        foreach ($places as $place) {
            $gameRoundPlaces[] = new GameRoundPlace($gameRoundNumber, $place);
        }
        return $gameRoundPlaces;
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

    /**
     * @param Poule $poule
     * @param Sport $sport
     * @param int $nrOfGamePlaces
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @param list<GameRoundPlace>|null $nextGameRoundPlaces
     * @return TogetherGame
     */
    protected function selectPlaces(Poule $poule, Sport $sport, int $nrOfGamePlaces, array &$gameRoundPlaces, array|null $nextGameRoundPlaces = null): TogetherGame
    {
//        $current = array_filter( $gameRoundPlaces, function(GameRoundPlace $gameRoundPlace) use ( $gameRoundNumber ): bool {
//            return $gameRoundPlace->getGameRoundNumber() === $gameRoundNumber;
//        });
        $game = null;
        if ($nextGameRoundPlaces !== null) {
            $nextGameRoundPlacesSameLocation = $this->removeSameLocation($nextGameRoundPlaces, $gameRoundPlaces);
            $game = $this->togetherCounter->createGame($poule, $sport, $gameRoundPlaces, $nextGameRoundPlaces, $nrOfGamePlaces);
            $gameRoundPlaces = array_merge($gameRoundPlaces, $nextGameRoundPlaces, $nextGameRoundPlacesSameLocation);
        } else {
            $game = $this->togetherCounter->createGame($poule, $sport, [], $gameRoundPlaces, $nrOfGamePlaces);
        }

        foreach ($game->getPlaces() as $gamePlace) {
            $idx = array_search($gamePlace, $gameRoundPlaces, true);
            if ($idx !== false) {
                array_splice($gameRoundPlaces, $idx, 1);
            }
        }
        return $game;
    }

    /**
     * @param list<GameRoundPlace> $nextGameRoundPlaces
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @return list<GameRoundPlace>
     */
    protected function removeSameLocation(array &$nextGameRoundPlaces, array $gameRoundPlaces): array
    {
        $nrOfPlaces = count($nextGameRoundPlaces);
        $removed = [];
        for ($idx = 0 ; $idx < $nrOfPlaces ; $idx++) {
            $nextGameRoundPlace = array_shift($nextGameRoundPlaces);
            if ($this->hasSameLocation($nextGameRoundPlace, $gameRoundPlaces)) {
                $removed[] = $nextGameRoundPlace;
            } else {
                array_push($nextGameRoundPlaces, $nextGameRoundPlace);
            }
        }
        return $removed;
    }

    /**
     * @param GameRoundPlace $nextGameRoundPlace
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @return bool
     */
    protected function hasSameLocation(GameRoundPlace $nextGameRoundPlace, array $gameRoundPlaces): bool
    {
        foreach ($gameRoundPlaces as $gameRoundPlace) {
            if ($gameRoundPlace->getPlace()->getLocation() === $nextGameRoundPlace->getPlace()->getLocation()) {
                return true;
            }
        }
        return false;
    }
}
