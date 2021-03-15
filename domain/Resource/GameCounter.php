<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Resource as PlanningResource;

class GameCounter
{
    protected int $nrOfGames;

    public function __construct(protected PlanningResource $resource, int $nrOfGames = 0)
    {
        $this->nrOfGames = $nrOfGames;
    }

    public function getResource(): PlanningResource
    {
        return $this->resource;
    }

    public function getIndex(): string
    {
        return (string)$this->resource->getNumber();
    }

    public function increase(): void
    {
        $this->nrOfGames++;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}