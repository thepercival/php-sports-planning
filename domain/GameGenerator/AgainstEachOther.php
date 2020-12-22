<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\SportConfig;
use SportsPlanning\Place;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;
use SportsPlanning\Game\Place\AgainstEachOther as AgainstEachOtherGamePlace;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Poule;

class AgainstEachOther implements Helper
{
    public function __construct()
    {}

    /**
     * @param Poule $poule
     * @param array | SportConfig[] $sportConfigs
     * @return array | AgainstEachOtherGame[] | TogetherGame[]
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
     * @return array | AgainstEachOtherHomeAway[]
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
     * @return array | AgainstEachOtherHomeAway[] | TogetherGame[]
     */
    public function generateForSportConfig(array $places, SportConfig $sportConfig): array
    {
        $nrOfHomeawayPlaces = $sportConfig->getNrOfGamePlaces() / 2;
        $maxNrOfHomeGames = (int)ceil( $sportConfig->getNrOfGamesPerPlace(SportConfig::GAMEMODE_AGAINSTEACHOTHER, count($places) ) / 2 );
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

    public function createGame(PlaceCombination $home, PlaceCombination $away, bool $swap): AgainstEachOtherHomeAway
    {
        return new AgainstEachOtherHomeAway($swap ? $away : $home, $swap ? $home : $away );
    }

    protected function gameExists(&$games, PlaceCombination $home, PlaceCombination $away): bool
    {
        $game = new AgainstEachOtherHomeAway($home, $away);
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
     * @param array|AgainstEachOtherHomeAway[] $homeAways
     * @return array|AgainstEachOtherGame[]
     */
    protected function toGames(array $homeAways, Poule $poule, int $gameAmount): array {
        return array_map( function( AgainstEachOtherHomeAway $homeAway ) use($poule, $gameAmount) : AgainstEachOtherGame {
            $game = new AgainstEachOtherGame($poule, $gameAmount);
            foreach( [AgainstEachOtherGame::HOME, AgainstEachOtherGame::AWAY] as $homeAwayValue ) {
                foreach( $homeAway->get($homeAwayValue)->getPlaces() as $place ) {
                    new AgainstEachOtherGamePlace($game, $place, $homeAwayValue );
                }
            }
            return $game;
        }, $homeAways );
    }
}
