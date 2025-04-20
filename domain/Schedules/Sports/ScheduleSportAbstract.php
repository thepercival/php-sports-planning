<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;

abstract class ScheduleSportAbstract
{
    public function __construct(
        public readonly ScheduleWithNrOfPlaces $schedule,
        public readonly int $number)
    {
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
