<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;

use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<TogetherGameRound>
 */
class TogetherGameRound extends ListNode
{
    /**
     * @var array<int, AmountNrCounterMap>
     */
    protected array $amountNrCounterMapsPerGameRoundNumber = [];

    /**
     * @var list<TogetherGameRoundGame>
     */
    protected array $games = [];

    public function __construct(private int $nrOfPlaces, TogetherGameRound|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): TogetherGameRound
    {
        $this->next = new TogetherGameRound($this->nrOfPlaces, $this);
        return $this->next;
    }

    /**
     * @return list<TogetherGameRoundGame>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    /**
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     * @return void
     * @throws \Exception
     */
    public function addGame(array $gamePlaces): void
    {
        foreach($gamePlaces as $gamePlace) {
            if( array_key_exists($gamePlace->gameRoundNumber, $this->amountNrCounterMapsPerGameRoundNumber) === false ) {
                $this->amountNrCounterMapsPerGameRoundNumber[$gamePlace->gameRoundNumber] = new AmountNrCounterMap($this->nrOfPlaces);
            }
            if( $this->amountNrCounterMapsPerGameRoundNumber[$gamePlace->gameRoundNumber]->count($gamePlace->placeNr) > 0 ) {
                throw new \Exception('a placeNr can only be used 1 time per gameRound');
            }
        }
        foreach($gamePlaces as $gamePlace) {
            $this->amountNrCounterMapsPerGameRoundNumber[$gamePlace->gameRoundNumber]->incrementPlaceNr($gamePlace->placeNr);
        }
        $this->games[] = new TogetherGameRoundGame($gamePlaces);
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

//    /**
//     * @return list<DuoPlaceNr>
//     */
//    public function convertToUniqueDuoPlaceNrs(): array
//    {
//        $duoPlaceNrsPerGame = array_map(function(TogetherGameRoundGame $game): array {
//            return $game->convertToDuoPlaceNrs();
//        }, $this->games);
//        $uniqueDuoPlaceNrs = [];
//        foreach( $duoPlaceNrsPerGame as $duoPlaceNrs) {
//            foreach( $duoPlaceNrs as $duoPlaceNr) {
//                $uniqueDuoPlaceNrs[$duoPlaceNr->getIndex()] = $duoPlaceNr;
//            }
//        }
//        return array_values($uniqueDuoPlaceNrs);
//    }
//
//    /**
//     * @return list<int>
//     */
//    public function convertToPlaceNrs(): array
//    {
//        $placeNrs = [];
//        foreach( $this->games as $game ) {
//            $placeNrs = array_merge($placeNrs, $game->convertToPlaceNrs() );
//        }
//        return $placeNrs;
//    }
}
