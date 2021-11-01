<?php

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Identifiable;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class Game extends Identifiable
{
    /**
     * @phpstan-var ArrayCollection<int|string, GamePlace>|PersistentCollection<int|string, GamePlace>|GamePlace[]
     * @psalm-var ArrayCollection<int|string, GamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;

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
     * @phpstan-return ArrayCollection<int|string, GamePlace>|PersistentCollection<int|string, GamePlace>|GamePlace[]
     * @psalm-return ArrayCollection<int|string, GamePlace>
     */
    public function getGamePlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    /**
     * @param Poule $poule
     * @param int $againstSide
     * @return list<Place>
     * @throws \Exception
     */
    public function getSidePlaces(Poule $poule, int $againstSide): array
    {
        $poulePlaces = [];
        foreach ($this->getGamePlaces() as $gameRoundGamePlace) {
            if ($gameRoundGamePlace->getAgainstSide() === $againstSide) {
                $poulePlaces[] = $poule->getPlace($gameRoundGamePlace->getNumber());
            }
        }
        return array_values($poulePlaces);
    }

    public function __toString(): string
    {
        $retVal = $this->gameRoundNumber !== null ? 'gameroundnr ' . $this->gameRoundNumber . ' : ' : '';
        $retVal .= implode(' & ', $this->getGamePlaces()->toArray());
        return $retVal;
    }
}
