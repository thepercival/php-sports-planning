<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Field;
use SportsPlanning\Place;
use SportsPlanning\Game;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Poule;
use SportsPlanning\Planning;

class Against extends Game
{
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGamePlace>|PersistentCollection<int|string, AgainstGamePlace>
     * @psalm-var ArrayCollection<int|string, AgainstGamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;

    public function __construct(
        Planning $planning,
        Poule $poule,
        Field $field,
        protected int $h2hNumber
    ) {
        parent::__construct($planning, $poule, $field);
        $this->places = new ArrayCollection();
        $this->planning->getAgainstGames()->add($this);
    }

    public function getH2hNumber(): int
    {
        return $this->h2hNumber;
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
     * @return Collection<int|string,AgainstGamePlace>
     */
    public function getSidePlaces(int $side = null): Collection
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
     * @return Collection<int|string,Place>
     */
    public function getPoulePlaces(): Collection
    {
        if ($this->poulePlaces === null) {
            $this->poulePlaces = $this->getPlaces()->map(function (AgainstGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            });
        }
        return $this->poulePlaces;
    }

    public function createVariant(): AgainstSportVariant
    {
        return new AgainstSportVariant(
            $this->getSport()->getNrOfHomePlaces(),
            $this->getSport()->getNrOfAwayPlaces(),
            $this->getSport()->getNrOfH2H(),
            $this->getSport()->getNrOfGamePlaces()
        );
    }
}
