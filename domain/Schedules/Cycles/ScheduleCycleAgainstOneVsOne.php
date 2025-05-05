<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

/**
 * @template-extends ListNode<ScheduleCycleAgainstOneVsOne>
 */
class ScheduleCycleAgainstOneVsOne extends ListNode
{
    public readonly ScheduleCyclePartAgainstOneVsOne $firstPart;

    public function __construct(
        public readonly ScheduleAgainstOneVsOne $sportSchedule,
        ScheduleCycleAgainstOneVsOne|null $previous = null)
    {
        parent::__construct($previous);
        $this->firstPart = new ScheduleCyclePartAgainstOneVsOne($this);
    }

    public function createNext(): ScheduleCycleAgainstOneVsOne
    {
        $this->next = new ScheduleCycleAgainstOneVsOne($this->sportSchedule, $this);
        return $this->next;
    }

//    /**
//     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
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
