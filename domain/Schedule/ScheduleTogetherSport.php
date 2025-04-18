<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedule;

class ScheduleTogetherSport extends ScheduleSport
{
    /**
     * @var Collection<int|string, ScheduleGame>
     */
    protected Collection $games;

    public function __construct(Schedule $schedule, int $number, public readonly TogetherSport $sport)
    {
        if (!$schedule->getSportSchedules()->contains($this)) {
            $schedule->getSportSchedules()->add($this);
        }
        parent::__construct($schedule, $number);
    }

    // ArrayCollection $gameRoundGames (home: [1,2], away: [3,4], single: [1,2,3,4,5])

//    public function __toString(): string
//    {
//        $jsonClass = new \stdClass();
//        $jsonClass->number = $this->number;
//        $jsonClass->sportVariant = $this->createVariant();
//
//
//        $retVal = json_encode($jsonClass);
//        return $retVal === false ? '?' : $retVal;
//    }
}
