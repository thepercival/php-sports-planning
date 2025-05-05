<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstTwoVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;

/**
 * @template-extends ListNode<ScheduleCycleAgainstTwoVsTwo>
 */
class ScheduleCycleAgainstTwoVsTwo extends ListNode
{
    public readonly ScheduleCyclePartAgainstTwoVsTwo $firstPart;

    public function __construct(
        public readonly ScheduleAgainstTwoVsTwo $sportSchedule,
        ScheduleCycleAgainstTwoVsTwo|null       $previous = null)
    {
        parent::__construct($previous);
        $this->firstPart = new ScheduleCyclePartAgainstTwoVsTwo($this);
    }

    public function createNext(): ScheduleCycleAgainstTwoVsTwo
    {
        $this->next = new ScheduleCycleAgainstTwoVsTwo($this->sportSchedule, $this);
        return $this->next;
    }

//    /**
//     * @return list<TwoVsTwoHomeAway|TwoVsTwoHomeAway|TwoVsTwoHomeAway>
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
}
