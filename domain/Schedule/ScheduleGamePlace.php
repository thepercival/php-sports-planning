<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Identifiable;

class ScheduleGamePlace extends Identifiable
{
    protected int|null $gameRoundNumber = null;
    protected AgainstSide|null $againstSide = null;

    public function __construct(protected ScheduleGame $game, protected int $placeNr)
    {
        if (!$game->getGamePlaces()->contains($this)) {
            $game->getGamePlaces()->add($this) ;
        }
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function getGameRoundNumber(): int
    {
        if ($this->gameRoundNumber === null) {
            throw new \Exception('schedule-gameplace->gameRoundNumber can not be null', E_ERROR);
        }
        return $this->gameRoundNumber;
    }

    public function setGameRoundNumber(int $gameRoundNumber): void
    {
        $this->gameRoundNumber = $gameRoundNumber;
    }

    public function getAgainstSide(): AgainstSide
    {
        if ($this->againstSide === null) {
            throw new \Exception('gameround-gameplace->againstside can not be null', E_ERROR);
        }
        return $this->againstSide;
    }

    public function setAgainstSide(AgainstSide $againstSide): void
    {
        $this->againstSide = $againstSide;
    }
}
