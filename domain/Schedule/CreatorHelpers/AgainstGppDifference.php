<?php

namespace SportsPlanning\Schedule\CreatorHelpers;

class AgainstGppDifference
{
    public readonly int $scheduleMargin;
    public readonly int $allowedCumMargin;
    public readonly int $minNrOfAgainstAllowedToAssignedToMinimumCum;
    public readonly int $minNrOfWithAllowedToAssignedToMinimumCum;
    public readonly int $allowedAgainstAmount;
    public readonly int $allowedWithAmount;
    public readonly bool $lastSport;

    public function __construct(
        int $scheduleMargin,
        int $allowedCumMargin,
        int $minNrOfAgainstAllowedToAssignedToMinimumCum,
        int $minNrOfWithAllowedToAssignedToMinimumCum,
        int $allowedAgainstAmount,
        int $allowedWithAmount,
        bool $lastSport)
    {
        $this->scheduleMargin = $scheduleMargin;
        $this->allowedCumMargin = $allowedCumMargin;
        $this->minNrOfAgainstAllowedToAssignedToMinimumCum = $minNrOfAgainstAllowedToAssignedToMinimumCum;
        $this->minNrOfWithAllowedToAssignedToMinimumCum = $minNrOfWithAllowedToAssignedToMinimumCum;
        $this->allowedAgainstAmount = $allowedAgainstAmount;
        $this->allowedWithAmount = $allowedWithAmount;
        $this->lastSport = $lastSport;
    }
}