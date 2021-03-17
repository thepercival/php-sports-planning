<?php
declare(strict_types=1);

namespace SportsPlanning\Resource\GameCounter;

use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Resource\GameCounter;

class Place extends GameCounter
{
    public function __construct(protected PlanningPlace $place, int $nrOfGames = 0)
    {
        parent::__construct($place, $nrOfGames);
    }

    public function getIndex(): string
    {
        return $this->place->getLocation();
    }

    public function getPlace(): PlanningPlace
    {
        return $this->place;
    }
}