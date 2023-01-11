<?php

namespace SportsPlanning\Schedule\CreatorHelpers;

use SportsHelpers\SportRange;

class AgainstGppDifference
{
    public readonly int $scheduleMargin;
    public readonly int $allowedCumMargin;
    public readonly int $minNrOfAgainstAllowedToAssignedToMinimumCum;
    public readonly int $maxNrOfAgainstAllowedToAssignedToMaximumCum;
    public readonly int $minNrOfWithAllowedToAssignedToMinimumCum;
    public readonly int $maxNrOfWithAllowedToAssignedToMaximumCum;
    public readonly SportRange $allowedAgainstRange;
    public readonly SportRange $allowedWithRange;
    public readonly bool $lastSport;

    public function __construct(
        int $scheduleMargin,
        int $allowedCumMargin,
        int $minNrOfAgainstAllowedToAssignedToMinimumCum,
        int $maxNrOfAgainstAllowedToAssignedToMaximumCum,
        int $minNrOfWithAllowedToAssignedToMinimumCum,
        int $maxNrOfWithAllowedToAssignedToMaximumCum,
        SportRange $allowedAgainstRange,
        SportRange $allowedWithRange,
        bool $lastSport)
    {
        $this->scheduleMargin = $scheduleMargin;
        $this->allowedCumMargin = $allowedCumMargin;
        $this->minNrOfAgainstAllowedToAssignedToMinimumCum = $minNrOfAgainstAllowedToAssignedToMinimumCum;
        $this->maxNrOfAgainstAllowedToAssignedToMaximumCum = $maxNrOfAgainstAllowedToAssignedToMaximumCum;
        $this->minNrOfWithAllowedToAssignedToMinimumCum = $minNrOfWithAllowedToAssignedToMinimumCum;
        $this->maxNrOfWithAllowedToAssignedToMaximumCum = $maxNrOfWithAllowedToAssignedToMaximumCum;
        $this->allowedAgainstRange = $allowedAgainstRange;
        $this->allowedWithRange = $allowedWithRange;
        $this->lastSport = $lastSport;
    }
}