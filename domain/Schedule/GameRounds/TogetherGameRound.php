<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\GameRounds;

use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<TogetherGameRound>
 */
class TogetherGameRound extends ListNode
{
    /**
     * @var list<GameRoundTogetherGame>
     */
    protected array $games = [];

    public function __construct(TogetherGameRound|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): TogetherGameRound
    {
        $this->next = new TogetherGameRound($this);
        return $this->next;
    }

    /**
     * @return list<GameRoundTogetherGame>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(GameRoundTogetherGame $game): void
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
     * @return list<DuoPlaceNr>
     */
    public function convertToDuoPlaces(): array
    {
        $allDuoPlaceNrs = array_map(function(GameRoundTogetherGame $game): array {
            return $game->convertToDuoPlaceNrs();
        }, $this->games);
        $uniqueDuoPlaceNrs = [];
        foreach( $allDuoPlaceNrs as $duoPlaceNrs) {
            foreach( $duoPlaceNrs as $duoPlaceNr) {
                $uniqueDuoPlaceNrs[$duoPlaceNr->getIndex()] = $duoPlaceNr;
            }
        }
        return array_values($uniqueDuoPlaceNrs);
    }

    /**
     * @return list<int>
     */
    public function convertToPlaceNrs(): array
    {
        $placeNrs = [];
        foreach( $this->games as $game ) {
            $placeNrs = array_merge($placeNrs, $game->convertToPlaceNrs() );
        }
        return $placeNrs;
    }
}
