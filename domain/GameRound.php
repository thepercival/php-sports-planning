<?php

declare(strict_types=1);

namespace SportsPlanning;

trait GameRound
{
    /**
     * @var array<int|string,Place>
     */
    protected array $placeMap = [];

    /**
     * @return array<int|string,Place>
     */
    protected function getPlaceMap(): array
    {
        return $this->placeMap;
    }

    public function isParticipating(Place $place): bool
    {
        return array_key_exists($place->getLocation(), $this->placeMap);
    }
}
