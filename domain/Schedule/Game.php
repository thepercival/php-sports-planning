<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Identifiable;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Game extends Identifiable
{
    /**
     * @var Collection<int|string, GamePlace>
     */
    protected Collection $places;

    public function __construct(protected SportSchedule $sportSchedule, protected int|null $gameRoundNumber = null)
    {
        if (!$sportSchedule->getGames()->contains($this)) {
            $sportSchedule->getGames()->add($this) ;
        }
        $this->places = new ArrayCollection();
    }

    public function getGameRoundNumber(): int
    {
        if ($this->gameRoundNumber === null) {
            throw new \Exception('schedule-game->gameRoundNumber can not be null', E_ERROR);
        }
        return $this->gameRoundNumber;
    }

    /**
     * @return Collection<int|string, GamePlace>
     */
    public function getGamePlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param AgainstSide $againstSide
     * @return list<int>
     */
    public function getSidePlaceNrs(AgainstSide $againstSide): array
    {
        $poulePlaceNrs = [];
        foreach ($this->getGamePlaces() as $gameRoundGamePlace) {
            if ($gameRoundGamePlace->getAgainstSide() === $againstSide) {
                $poulePlaceNrs[] = $gameRoundGamePlace->getNumber();
            }
        }
        return $poulePlaceNrs;
    }

    public function __toString(): string
    {
        $retVal = $this->gameRoundNumber !== null ? 'gameroundnr ' . $this->gameRoundNumber . ' : ' : '';
        $retVal .= implode(' & ', $this->getGamePlaces()->toArray());
        return $retVal;
    }
}
