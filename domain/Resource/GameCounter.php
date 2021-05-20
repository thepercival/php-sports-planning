<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Resource as PlanningResource;

class GameCounter implements \Stringable
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
        return $this->resource->getUniqueIndex();
    }

    public function increase(): void
    {
        $this->nrOfGames++;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }

    public function __toString(): string
    {
        return $this->getIndex() . ':' . $this->getNrOfGames();
    }
}