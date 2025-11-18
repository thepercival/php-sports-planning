<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Resource as PlanningResource;
use SportsHelpers\Counter;

/**
 * @template-extends Counter<PlanningResource>
 */
class GameCounter extends Counter implements \Stringable
{
    public function __construct(PlanningResource $resource, int $nrOfGames = 0)
    {
        parent::__construct($resource, $nrOfGames);
    }

    public function getResource(): PlanningResource
    {
        return $this->countedObject;
    }

    public function getIndex(): string
    {
        return $this->countedObject->getUniqueIndex();
    }

    public function getNrOfGames(): int
    {
        return $this->count();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getIndex() . ':' . $this->getNrOfGames();
    }
}
