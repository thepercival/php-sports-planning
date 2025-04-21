<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainst;

/**
 * @template-extends ListNode<ScheduleCycleAgainst>
 */
class ScheduleCycleAgainst extends ListNode
{
    public readonly ScheduleCyclePartAgainst $firstPart;

    public function __construct(public readonly int $nrOfPlaces, ScheduleCycleAgainst|null $previous = null)
    {
        parent::__construct($previous);
        $this->firstPart = new ScheduleCyclePartAgainst($this);
    }

    public function createNext(): ScheduleCycleAgainst
    {
        $this->next = new ScheduleCycleAgainst($this->nrOfPlaces, $this);
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
