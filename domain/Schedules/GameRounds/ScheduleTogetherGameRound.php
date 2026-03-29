<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;

use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<ScheduleTogetherGameRound>
 */
final class ScheduleTogetherGameRound extends ListNode
{
    use ScheduleGameRoundTrait;

    /**
     * @var list<ScheduleTogetherGameRoundGame>
     */
    protected array $games = [];

    public function __construct(ScheduleTogetherGameRound|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): ScheduleTogetherGameRound
    {
        $this->next = new ScheduleTogetherGameRound($this);
        return $this->next;
    }

    /**
     * @return list<ScheduleTogetherGameRoundGame>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(ScheduleTogetherGameRoundGame $game): void
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
        return array_map(fn(ScheduleTogetherGameRoundGame $game) => $game->toPlaceNrCombination(), $this->games);
    }
}
