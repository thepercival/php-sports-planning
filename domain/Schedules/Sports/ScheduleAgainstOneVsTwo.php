<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;

class ScheduleAgainstOneVsTwo extends ScheduleSportAbstract
{
    /**
     * @var list<ScheduleGameAgainstOneVsTwo>
     */
    protected array $games = [];

    public function __construct(ScheduleWithNrOfPlaces $schedule, int $number, public readonly AgainstOneVsTwo $sport,
                                int $nrOfCycles)
    {
        parent::__construct($schedule, $number,$nrOfCycles);
        $schedule->addSportSchedule($this);
    }

    /**
     * @return list<ScheduleGameAgainstOneVsTwo>
     */
    public function getGames(): array {
        return $this->games;
    }

    public function addGame(ScheduleGameAgainstOneVsTwo $game): void {
        $this->games[] = $game;
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
