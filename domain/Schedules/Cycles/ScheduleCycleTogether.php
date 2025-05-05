<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;

/**
 * @template-extends ListNode<ScheduleCycleTogether>
 */
class ScheduleCycleTogether extends ListNode
{
    /**
     * @var list<ScheduleGameTogether>
     */
    protected array $games = [];

    public function __construct(
        public readonly ScheduleTogetherSport $sportSchedule, ScheduleCycleTogether|null $previous = null)
    {
        parent::__construct($previous);

    }

    public function createNext(): ScheduleCycleTogether
    {
        $this->next = new ScheduleCycleTogether($this->sportSchedule, $this);
        return $this->next;
    }


    /**
     * @return list<ScheduleGameTogether>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(ScheduleGameTogether $game): void
    {
        $this->games[] = $game;
    }
}
