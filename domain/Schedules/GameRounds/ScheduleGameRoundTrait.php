<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;


trait ScheduleGameRoundTrait
{
    /**
     * @var array<int,int>
     */
    protected array $placeNrMap = [];

    /**
     * @return array<int|string,int>
     */
    protected function getPlaceNrMap(): array
    {
        return $this->placeNrMap;
    }

    public function isParticipating(int $placeNr): bool
    {
        return array_key_exists($placeNr, $this->placeNrMap);
    }
}
