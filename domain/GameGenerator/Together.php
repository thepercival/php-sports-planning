<?php

namespace SportsPlanning\GameGenerator;

use SportsHelpers\SportConfig;
use SportsPlanning\Place;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Poule;

class Together implements Helper
{
    /**
     * @var TogetherCounter|null
     */
    private $togetherCounter;
    /**
     * @var Poule
     */
    private $poule;

    public function __construct(){

    }

    /**
     * @param Poule $poule
     * @param array | SportConfig[] $sportConfigs
     * @return array | TogetherGame[] | AgainstGame[]
     */
    public function generate(Poule $poule, array $sportConfigs): array
    {
        $this->poule = $poule;
        $places = $this->poule->getPlaces()->toArray();
        if( $this->togetherCounter === null ) {
            $this->togetherCounter = new TogetherCounter($poule);
        }

        $games = [];
        foreach( $sportConfigs as $sportConfig ) {
            $games = array_merge( $games, $this->generateForSportConfig($places, $sportConfig) );
        }
        return $games;
    }

    /**
     * @param array | Place[] $places
     * @param SportConfig $sportConfig
     * @return array | TogetherGame[]
     */
    protected function generateForSportConfig(array $places, SportConfig $sportConfig): array
    {
        $gameAmount = $sportConfig->getGameAmount();
        $nrOfGamePlaces = $sportConfig->getNrOfGamePlaces();
        if( $nrOfGamePlaces === SportConfig::ALLPLACES_ARE_GAMEPLACES ) {
            $nrOfGamePlaces = count($places);
        }

        /**
         * @param int $gameRoundNumber
         * @param array|GameRoundPlace[] $gameRoundPlaces
         * @return TogetherGame|null
         */
        $createGame = function(int &$gameRoundNumber, array &$gameRoundPlaces) use($places, $nrOfGamePlaces, $gameAmount): ?TogetherGame{
            $nextGameRoundPlaces = null;
            if( count($gameRoundPlaces) < $nrOfGamePlaces ) {
                if( ++$gameRoundNumber <= $gameAmount ) {
                    $nextGameRoundPlaces = $this->createGameRoundPlaces( $gameRoundNumber, $places );
                }
            }
            if( count($gameRoundPlaces) === 0 && $gameRoundNumber > $gameAmount ) {
                return null;
            }
            return $this->selectPlaces( $nrOfGamePlaces, $gameRoundPlaces, $nextGameRoundPlaces);
        };

        $games = [];
        $gameRoundNumber = 1;
        $gameRoundNumberPlaces = $this->createGameRoundPlaces( 1, $places );
        while( $game = $createGame( $gameRoundNumber, $gameRoundNumberPlaces ) ) {
            $games[] = $game;
        }
        return $games;
    }

    /**
     * @param int $gameRoundNumber
     * @param array|Place[] $places
     * @return array|GameRoundPlace[]
     */
    protected function createGameRoundPlaces(int $gameRoundNumber, array $places ): array {
        $gameRoundPlaces = [];
        foreach( $places as $place ) {
            $gameRoundPlaces[] = new GameRoundPlace( $gameRoundNumber, $place);
        }
        return $gameRoundPlaces;
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

    /**
     * @param int $nrOfGamePlaces
     * @param array|GameRoundPlace[] $gameRoundPlaces
     * @param array|GameRoundPlace[]|null $nextGameRoundPlaces
     * @return TogetherGame
     */
    protected function selectPlaces( int $nrOfGamePlaces, array &$gameRoundPlaces, array $nextGameRoundPlaces = null): TogetherGame {
//        $current = array_filter( $gameRoundPlaces, function(GameRoundPlace $gameRoundPlace) use ( $gameRoundNumber ): bool {
//            return $gameRoundPlace->getGameRoundNumber() === $gameRoundNumber;
//        });
        $game = null;
        if( $nextGameRoundPlaces !== null ) {
            $nextGameRoundPlacesSameLocation = $this->removeSameLocation($nextGameRoundPlaces, $gameRoundPlaces);
            $game = $this->togetherCounter->createGame( $gameRoundPlaces, $nextGameRoundPlaces, $nrOfGamePlaces );
            $gameRoundPlaces = array_merge( $gameRoundPlaces, $nextGameRoundPlaces, $nextGameRoundPlacesSameLocation);
        } else {
            $game = $this->togetherCounter->createGame( [], $gameRoundPlaces, $nrOfGamePlaces );
        }

        foreach( $game->getPlaces() as $gamePlace ) {
            array_splice($gameRoundPlaces, array_search($gamePlace, $gameRoundPlaces, true ), 1 );
        }
        return $game;
    }

    /**
     * @param array|GameRoundPlace[] $nextGameRoundPlaces
     * @param array|GameRoundPlace[] $gameRoundPlaces
     * @return array|GameRoundPlace[]
     */
    protected function removeSameLocation(array &$nextGameRoundPlaces, array $gameRoundPlaces): array {
        $nrOfPlaces = count($nextGameRoundPlaces);
        $removed = [];
        for( $idx = 0 ; $idx < $nrOfPlaces ; $idx++ ) {
            $nextGameRoundPlace = array_shift( $nextGameRoundPlaces );
            if( $this->hasSameLocation($nextGameRoundPlace, $gameRoundPlaces) ) {
                $removed[] = $nextGameRoundPlace;
            } else {
                array_push( $nextGameRoundPlaces, $nextGameRoundPlace );
            }
        }
        return $removed;
    }

    /**
     * @param GameRoundPlace $nextGameRoundPlace
     * @param array|GameRoundPlace[] $gameRoundPlaces
     * @return bool
     */
    protected function hasSameLocation(GameRoundPlace $nextGameRoundPlace, array $gameRoundPlaces): bool {
        foreach( $gameRoundPlaces as $gameRoundPlace ) {
            if ($gameRoundPlace->getPlace()->getLocation() === $nextGameRoundPlace->getPlace()->getLocation()) {
                return true;
            }
        }
        return false;
    }
}
