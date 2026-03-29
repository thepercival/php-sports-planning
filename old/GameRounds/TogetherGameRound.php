<?php

declare(strict_types=1);

namespace old\GameRounds;

use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<TogetherGameRound>
 */
final class TogetherGameRound extends ListNode
{
    use GameRoundTrait;

    /**
     * @var list<TogetherGameRoundGame>
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
     * @return list<TogetherGameRoundGame>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(TogetherGameRoundGame $game): void
    {
        $this->games[] = $game;
    }
//
//    public function remove(PlaceNrCombination $placeCombination): void
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
     * @return list<PlaceNrCombination>
     */
    public function toPlaceNrCombinations(): array
    {
        return array_map(fn(TogetherGameRoundGame $game) => $game->toPlaceNrCombination(), $this->games);
    }
}
