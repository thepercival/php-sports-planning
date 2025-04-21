<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;

class ScheduleTogetherSport extends ScheduleSportAbstract
{
    /**
     * @var list<ScheduleGameTogether>
     */
    protected array $games = [];

    public function __construct(
        ScheduleWithNrOfPlaces $schedule,
        int $number,
        public readonly TogetherSport $sport,
        int $nrOfCycles)
    {
        parent::__construct($schedule, $number, $nrOfCycles);
        $schedule->addSportSchedule($this);
    }

    /**
     * @return ScheduleGameTogether[]
     */
    public function getGames(): array {
        return $this->games;
    }

    public function addGame(ScheduleGameTogether $game): void {
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
