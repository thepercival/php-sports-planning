<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\SportConfig;
use SportsPlanning\Place;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Poule;

class Against implements Helper
{
    public function __construct()
    {}

    /**
     * @param Poule $poule
     * @param array | SportConfig[] $sportConfigs
     * @return array | AgainstGame[] | TogetherGame[]
     */
    public function generate(Poule $poule, array $sportConfigs): array
    {
        $games = [];
        $gameAmount = 1;
        while ( $filteredSportConfigs = $this->getSportConfigs($sportConfigs, $gameAmount)) {
            $homeAways = $this->generateHelper($poule->getPlaces()->toArray(), $filteredSportConfigs );
            $gamesIt = $this->toGames($homeAways, $poule, $gameAmount );
            $games = array_merge( $games, $gamesIt );
            $gameAmount++;
        }
        return $games;
    }

    /**
     * @param array | SportConfig[] $sportConfigs
     * @param int $gameAmount
     * @return array | SportConfig[]
     */
    protected function getSportConfigs(array $sportConfigs, int $gameAmount): array {
        return array_filter($sportConfigs, function( SportConfig $sportConfig) use($gameAmount): bool {
            return $sportConfig->getGameAmount() >= $gameAmount;
        });
    }

    /**
     * @param array | Place[] $places
     * @param array | SportConfig[] $sportConfigs
     * @return array | AgainstHomeAway[]
     */
    protected function generateHelper(array $places, array $sportConfigs): array
    {
        $games = [];
        foreach ($sportConfigs as $sportConfig) {
            $games = array_merge($games, $this->generateForSportConfig($places, $sportConfig));
        }
        return $games;
    }

    /**
     * @param array | Place[] $places
     * @param SportConfig $sportConfig
     * @return array | AgainstHomeAway[] | TogetherGame[]
     */
    public function generateForSportConfig(array $places, SportConfig $sportConfig): array
    {
        $nrOfHomeawayPlaces = $sportConfig->getNrOfGamePlaces() / 2;
        $maxNrOfHomeGames = (int)ceil( $sportConfig->getNrOfGamesPerPlace(SportConfig::GAMEMODE_AGAINST, count($places) ) / 2 );
        $homeCombinations = $this->toPlaceCombinations(
            new CombinationsGenerator($places, $nrOfHomeawayPlaces)
        );
        // $this->homeCounterService->initSport( $sportConfig, $homeCombinations);
        $games = [];

        foreach ($homeCombinations as $homeCombination) {
            $availableAwayPlaces = $this->getOtherPlaces($places, $homeCombination);
            $awayCombinations = $this->toPlaceCombinations(
                new CombinationsGenerator($availableAwayPlaces, $nrOfHomeawayPlaces)
            );
            $nrOfHomeGames = 0;
            foreach ($awayCombinations as $awayCombination) {
                if ($homeCombination->hasOverlap($awayCombination)) {
                    continue;
                }
                if ($this->gameExists($games, $homeCombination, $awayCombination)) {
                    continue;
                }
                $games[] = $this->createGame($homeCombination, $awayCombination, ++$nrOfHomeGames > $maxNrOfHomeGames);
            }
        }
        return $games;
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return array|PlaceCombination[]
     */
    protected function toPlaceCombinations( CombinationsGenerator $combinations ): array {
        return array_map(
            function (array $placeCombination): PlaceCombination {
                return new PlaceCombination($placeCombination);
            },
            $combinations->toArray()
        );
    }

    public function createGame(PlaceCombination $home, PlaceCombination $away, bool $swap): AgainstHomeAway
    {
        return new AgainstHomeAway($swap ? $away : $home, $swap ? $home : $away );
    }

    protected function gameExists(&$games, PlaceCombination $home, PlaceCombination $away): bool
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
     * @param array|Place[] $places
     * @param PlaceCombination $placeCombination
     * @return array|Place[]
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
     * @param array|AgainstHomeAway[] $homeAways
     * @return array|AgainstGame[]
     */
    protected function toGames(array $homeAways, Poule $poule, int $gameAmount): array {
        return array_map( function( AgainstHomeAway $homeAway ) use($poule, $gameAmount) : AgainstGame {
            $game = new AgainstGame($poule, $gameAmount);
            foreach( [AgainstGame::HOME, AgainstGame::AWAY] as $homeAwayValue ) {
                foreach( $homeAway->get($homeAwayValue)->getPlaces() as $place ) {
                    new AgainstGamePlace($game, $place, $homeAwayValue );
                }
            }
            return $game;
        }, $homeAways );
    }
}
