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
     * @param array<SportAndConfig> $sportAndConfigs
     * @return array<AgainstGame>
     */
    public function generate(Poule $poule, array $sportAndConfigs): array
    {
        /** @var array<AgainstGame> $games */
        $games = [];
        $gameAmount = 1;
        while ($sportAndConfigs = $this->getSportAndConfigs($sportAndConfigs, $gameAmount)) {
            $homeAways = $this->generateHelper($poule->getPlaces()->toArray(), $sportAndConfigs);
            $gamesIt = $this->toGames($poule, $homeAways, $gameAmount);
            $games = array_merge($games, $gamesIt);
            $gameAmount++;
        }
        return $games;
    }

    /**
     * @param array<SportAndConfig> $sportAndConfigs
     * @param int $gameAmount
     * @return array<SportAndConfig>
     */
    protected function getSportAndConfigs(array $sportAndConfigs, int $gameAmount): array
    {
        return array_filter($sportAndConfigs, function (SportAndConfig $sportAndConfig) use ($gameAmount): bool {
            return $sportAndConfig->getSport()->getGameMode() === GameMode::AGAINST
                && $sportAndConfig->getConfig()->getGameAmount() >= $gameAmount;
        });
    }

    /**
     * @param array<Place> $places
     * @param array<SportAndConfig> $sportAndConfigs
     * @return array<AgainstHomeAway>
     */
    protected function generateHelper(array $places, array $sportAndConfigs): array
    {
        $games = [];
        foreach ($sportAndConfigs as $sportAndConfig) {
            $games = array_merge($games, $this->generateForSportAndConfig($places, $sportAndConfig));
        }
        return $games;
    }

    /**
     * @param array<Place> $places
     * @param SportAndConfig $sportAndConfig
     * @return array<AgainstHomeAway>
     */
    public function generateForSportAndConfig(array $places, SportAndConfig $sportAndConfig): array
    {
        $nrOfHomeAwayPlaces = (int)($sportAndConfig->getSport()->getNrOfGamePlaces() / 2);
        $maxNrOfHomeGames = (int)ceil($sportAndConfig->getConfig()->getNrOfGamesPerPlace(count($places)) / 2);
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
                if ($this->gameExists($sportAndConfig->getSport(), $games, $homeCombination, $awayCombination)) {
                    continue;
                }
                $games[] = $this->createGame($sportAndConfig->getSport(), $homeCombination, $awayCombination, ++$nrOfHomeGames > $maxNrOfHomeGames);
            }
        }
        return $games;
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return array<PlaceCombination>
     */
    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
    {
        return array_map(
            function (array $placeCombination): PlaceCombination {
                return new PlaceCombination($placeCombination);
            },
            $combinations->toArray()
        );
    }

    public function createGame(Sport $sport, PlaceCombination $home, PlaceCombination $away, bool $swap): AgainstHomeAway
    {
        return new AgainstHomeAway($sport, $swap ? $away : $home, $swap ? $home : $away);
    }

    /**
     * @param Sport $sport
     * @param array<AgainstHomeAway> $games
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
     * @param array<Place> $places
     * @param PlaceCombination $placeCombination
     * @return array<Place>
     */
    protected function getOtherPlaces(array $places, PlaceCombination $placeCombination): array
    {
        return array_filter(
            $places,
            function (Place $placeIt) use ($placeCombination): bool {
                return !$placeCombination->has($placeIt);
            }
        );
    }

    /**
     * @param Poule $poule
     * @param array<AgainstHomeAway> $homeAways
     * @param int $gameAmount
     * @return array<AgainstGame>
     */
    protected function toGames(Poule $poule, array $homeAways, int $gameAmount): array
    {
        return array_map(function (AgainstHomeAway $homeAway) use ($poule, $gameAmount) : AgainstGame {
            $game = new AgainstGame($poule, $gameAmount, $homeAway->getSport());
            foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAwayValue) {
                foreach ($homeAway->get($homeAwayValue)->getPlaces() as $place) {
                    new AgainstGamePlace($game, $place, $homeAwayValue);
                }
            }
            return $game;
        }, $homeAways);
    }
}
