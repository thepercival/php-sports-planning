<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;

final class ScheduleAgainstTwoVsTwo extends ScheduleSportAbstract
{
    public function __construct(ScheduleWithNrOfPlaces $schedule, int $number, public readonly AgainstTwoVsTwo $sport,
                                int $nrOfCycles)
    {
        parent::__construct($schedule, $number,$nrOfCycles);
    }

    public function createSportWithNrOfPlaces(): AgainstTwoVsTwoWithNrOfPlaces
    {
        return new AgainstTwoVsTwoWithNrOfPlaces($this->scheduleWithNrOfPlaces->nrOfPlaces, $this->sport);
    }

    public function createSportWithNrOfCycles(): SportWithNrOfCycles
    {
        return new SportWithNrOfCycles($this->sport, $this->nrOfCycles);
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
