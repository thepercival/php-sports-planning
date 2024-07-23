<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\GameRound\Together\GamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<Together>
 */
class Together extends ListNode
{
    use GameRound;

    /**
     * @var list<Game>
     */
    protected array $games = [];

    public function __construct(Together|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): Together
    {
        $this->next = new Together($this);
        return $this->next;
    }

    /**
     * @return list<Game>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(Game $game): void
    {
        $this->games[] = $game;
    }
//
//    public function remove(PlaceCombination $placeCombination): void
//    {
//        $index = array_search($placeCombination, $this->placeCombinations, true);
//        if ($index !== false) {
//            array_splice($this->placeCombinations, $index, 1);
//        }
//        foreach ($placeCombination->getPlaces() as $place) {
//            unset($this->placeMap[$place->getLocation()]);
//        }
//    }

    /**
     * @return list<PlaceCombination>
     */
    public function toPlaceCombinationsOfTwo(): array
    {
        $allPlaceCombinationsOfTwo = array_map(function(Game $game): array {
            return $game->toPlaceCombinationsOfTwo();
        }, $this->games);
        $uniquePlaceCombinationsOfTwo = [];
        foreach( $allPlaceCombinationsOfTwo as $placeCombinationsOfTwo) {
            foreach( $placeCombinationsOfTwo as $placeCombination) {
                $uniquePlaceCombinationsOfTwo[$placeCombination->getIndex()] = $placeCombination;
            }
        }
        return array_values($uniquePlaceCombinationsOfTwo);
    }

    /**
     * @return list<Place>
     */
    public function toPlaces(): array
    {
        $places = [];
        foreach( $this->games as $game ) {
            $places = array_merge($places, $game->toPlaces() );
        }
        return $places;
    }
}
