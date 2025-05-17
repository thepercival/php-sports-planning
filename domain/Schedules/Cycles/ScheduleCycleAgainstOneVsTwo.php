<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsTwo;

/**
 * @template-extends ListNode<ScheduleCycleAgainstOneVsTwo>
 */
class ScheduleCycleAgainstOneVsTwo extends ListNode
{
    public readonly ScheduleCyclePartAgainstOneVsTwo $firstPart;

    public function __construct(
        public readonly ScheduleAgainstOneVsTwo $sportSchedule,
        ScheduleCycleAgainstOneVsTwo|null       $previous = null)
    {
        parent::__construct($previous);
        $this->firstPart = new ScheduleCyclePartAgainstOneVsTwo($this);
    }

    public function createNext(): ScheduleCycleAgainstOneVsTwo
    {
        $this->next = new ScheduleCycleAgainstOneVsTwo($this->sportSchedule, $this);
        return $this->next;
    }

//    /**
//     * @return list<OneVsTwoHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
//     */
//    public function getAllHomeAways(): array
//    {
//        $homeAways = [];
//        $cyclePart = $this->getFirst();
//        while ($cyclePart) {
//            foreach ($cyclePart->getHomeAways() as $homeAway) {
//                $homeAways[] = $homeAway;
//            }
//            $cyclePart = $cyclePart->getNext();
//        }
//        return $homeAways;
//    }

    /**
     *  @return list<ScheduleGameAgainstOneVsTwo>
     */
    public function getAllCyclePartGames(): array {
        $games = [];
        $cyclePart = $this->firstPart;
        while($cyclePart !== null) {
            foreach ($cyclePart->getGames() as $game) {
                $games[] = $game;
            }
            $cyclePart = $cyclePart->getNext();
        }
        return $games;
    }
}
