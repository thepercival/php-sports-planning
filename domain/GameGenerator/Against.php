<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\GameMode;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Place;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Against
{
    public function __construct()
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return list<AgainstGame>
     */
    public function generate(Poule $poule, array $sports): array
    {
        /** @var list<AgainstGame> $games */
        $games = [];
        $gameAmount = 1;
        while ($sports = $this->getSports($sports, $gameAmount)) {
            $places = array_values($poule->getPlaces()->toArray());
            $homeAways = $this->generateHelper($places, $sports);
            $gamesIt = $this->toGames($poule, $homeAways, $gameAmount);
            $games = array_values(array_merge($games, $gamesIt));
            $gameAmount++;
        }
        return $games;
    }

    /**
     * @param list<Sport> $sports
     * @param int $gameAmount
     * @return list<Sport>
     */
    protected function getSports(array $sports, int $gameAmount): array
    {
        $sports = array_filter($sports, function (Sport $sport) use ($gameAmount): bool {
            return $sport->getGameMode() === GameMode::AGAINST
                && $sport->getGameAmount() >= $gameAmount;
        });
        return array_values($sports);
    }

    /**
     * @param list<Place> $places
     * @param list<Sport> $sports
     * @return list<AgainstHomeAway>
     */
    protected function generateHelper(array $places, array $sports): array
    {
        $games = [];
        foreach ($sports as $sport) {
            $games = array_values(
                array_merge($games, $this->generateForSport($places, $sport))
            );
        }
        return $games;
    }

    /**
     * @param list<Place> $places
     * @param Sport $sport
     * @return list<AgainstHomeAway>
     */
    public function generateForSport(array $places, Sport $sport): array
    {
        $nrOfHomeAwayPlaces = (int)($sport->getNrOfGamePlaces() / 2);
        $maxNrOfHomeGames = (int)ceil($sport->getNrOfGamesPerPlace(count($places)) / 2);
        $homeCombinations = $this->toPlaceCombinations(
            new CombinationsGenerator($places, $nrOfHomeAwayPlaces)
        );
        // $this->homeCounterService->initSport( $sportConfig, $homeCombinations);
        $games = [];

        foreach ($homeCombinations as $homeCombination) {
            $availableAwayPlaces = $this->getOtherPlaces($places, $homeCombination);
            $awayCombinations = $this->toPlaceCombinations(
                new CombinationsGenerator($availableAwayPlaces, $nrOfHomeAwayPlaces)
            );
            $nrOfHomeGames = 0;
            foreach ($awayCombinations as $awayCombination) {
                if ($homeCombination->hasOverlap($awayCombination)) {
                    continue;
                }
                if ($this->gameExists($sport, $games, $homeCombination, $awayCombination)) {
                    continue;
                }
                $games[] = $this->createGame($sport, $homeCombination, $awayCombination, ++$nrOfHomeGames > $maxNrOfHomeGames);
            }
        }
        return $games;
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

    public function createGame(Sport $sport, PlaceCombination $home, PlaceCombination $away, bool $swap): AgainstHomeAway
    {
        return new AgainstHomeAway($sport, $swap ? $away : $home, $swap ? $home : $away);
    }

    /**
     * @param Sport $sport
     * @param list<AgainstHomeAway> $games
     * @param PlaceCombination $home
     * @param PlaceCombination $away
     * @return bool
     */
    protected function gameExists(Sport $sport, array $games, PlaceCombination $home, PlaceCombination $away): bool
    {
        $game = new AgainstHomeAway($sport, $home, $away);
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
     * @param int $gameAmount
     * @return list<AgainstGame>
     */
    protected function toGames(Poule $poule, array $homeAways, int $gameAmount): array
    {
        return array_map(function (AgainstHomeAway $homeAway) use ($poule, $gameAmount) : AgainstGame {
            $game = new AgainstGame($poule, $gameAmount, $homeAway->getSport()->getField(1));
            foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAwayValue) {
                foreach ($homeAway->get($homeAwayValue)->getPlaces() as $place) {
                    new AgainstGamePlace($game, $place, $homeAwayValue);
                }
            }
            return $game;
        }, $homeAways);
    }
}
