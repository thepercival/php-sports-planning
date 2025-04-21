<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;

class ScheduleAgainstOneVsOne extends ScheduleSportAbstract
{
    /**
     * @var list<ScheduleGameAgainstOneVsOne>
     */
    protected array $games = [];

    public function __construct(
        ScheduleWithNrOfPlaces $schedule,
        int $number,
        public readonly AgainstOneVsOne $sport,
        int $nrOfCycles
    )
    {
        parent::__construct($schedule, $number,$nrOfCycles);
        $schedule->addSportSchedule($this);
    }

    /**
     * @return list<ScheduleGameAgainstOneVsOne>
     */
    public function getGames(): array {
        return $this->games;
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
