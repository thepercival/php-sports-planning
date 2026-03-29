<?php

declare(strict_types=1);

namespace old\GameRounds;

use SportsPlanning\Place;

trait GameRoundTrait
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
        return array_key_exists((string)$place, $this->placeMap);
    }
}
