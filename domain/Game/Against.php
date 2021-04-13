<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Field;
use SportsPlanning\Place;
use SportsPlanning\Game;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Poule;

class Against extends Game
{
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGamePlace>|PersistentCollection<int|string, AgainstGamePlace>
     * @psalm-var ArrayCollection<int|string, AgainstGamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;

    public function __construct(Poule $poule, protected int $nrOfHeadtohead, Field $field)
    {
        parent::__construct($poule, $field);
        $this->places = new ArrayCollection();
        $this->poule->getAgainstGames()->add($this);
    }

    public function getNrOfHeadtohead(): int
    {
        return $this->nrOfHeadtohead;
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    /**
     * @return void
     */
    public function setBatchNr(int $batchNr)
    {
        $this->batchNr = $batchNr;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, AgainstGamePlace>|PersistentCollection<int|string, AgainstGamePlace>
     * @psalm-return ArrayCollection<int|string, AgainstGamePlace>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    /**
     * @param int|null $side
     * @return ArrayCollection<int|string,AgainstGamePlace>
     */
    public function getSidePlaces(int $side = null): ArrayCollection
    {
        if ($side === null) {
            return $this->getPlaces();
        }
        return $this->places->filter(
            function (AgainstGamePlace $gamePlace) use ($side): bool {
                return $gamePlace->getSide() === $side;
            }
        );
    }

//    /**
//     * @param ArrayCollection | GamePlace[] $places
//     */
//    public function setPlaces(ArrayCollection $places)
//    {
//        $this->places = $places;
//    }

    public function addPlace(Place $place, int $side): AgainstGamePlace
    {
        return new AgainstGamePlace($this, $place, $side);
    }

    public function isParticipating(Place $place, int|null $side = null): bool
    {
        $places = $this->getSidePlaces($side)->map(function ($gamePlace): Place {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getSide(Place $place): int
    {
        if ($this->isParticipating($place, AgainstSide::HOME)) {
            return AgainstSide::HOME;
        }
        if ($this->isParticipating($place, AgainstSide::AWAY)) {
            return AgainstSide::AWAY;
        }
        throw new Exception('kan kant niet vinden', E_ERROR);
    }

    /**
     * @return ArrayCollection<int|string,Place>
     */
    public function getPoulePlaces(): ArrayCollection
    {
        if ($this->poulePlaces === null) {
            $this->poulePlaces = $this->getPlaces()->map(function (AgainstGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            });
        }
        return $this->poulePlaces;
    }
}
