<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Sports;

use oldsportshelpers\old\WithNrOfPlaces\SportWithNrOfPlaces;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfPlaces\SportWithNrOfPlacesInterface;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

final class ScheduleTogetherSport extends ScheduleSportAbstract
{
    public function __construct(
        ScheduleWithNrOfPlaces $scheduleWithNrOfPlaces,
        int $number,
        public readonly TogetherSport $sport,
        int $nrOfCycles)
    {
        parent::__construct($scheduleWithNrOfPlaces, $number, $nrOfCycles);
    }

    public function createSportWithNrOfPlaces(): TogetherSportWithNrOfPlaces
    {
        return new TogetherSportWithNrOfPlaces($this->scheduleWithNrOfPlaces->nrOfPlaces, $this->sport);
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
