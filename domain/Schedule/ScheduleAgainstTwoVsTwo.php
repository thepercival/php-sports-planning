<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Schedule;

class ScheduleAgainstTwoVsTwo extends ScheduleSport
{
    public function __construct(Schedule $schedule, int $number, public readonly AgainstTwoVsTwo $sport)
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
