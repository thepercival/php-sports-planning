<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<Together>
 */
final class Together extends ListNode
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
    public function toPlaceCombinations(): array
    {
        return array_map(fn(Game $game) => $game->toPlaceCombination(), $this->games);
    }
}
