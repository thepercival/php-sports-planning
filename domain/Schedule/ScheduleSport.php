<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstSport;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedule;

abstract class ScheduleSport
{
    /**
     * @var Collection<int|string, ScheduleGame>
     */
    protected Collection $games;

    public function __construct(protected Schedule $schedule, protected int $number)
    {
        $this->games = new ArrayCollection();
    }

    public function getSchedule(): Schedule {
        return $this->schedule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return Collection<int|string, ScheduleGame>
     */
    public function getGames(): Collection
    {
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
