<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Game;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Poule;

class Against extends Game
{
    protected Poule $poule;
    protected $nrOfHeadtohead;
    /**
     * @var Collection | AgainstGamePlace[]
     */
    protected $places;

    public const HOME = true;
    public const AWAY = false;

    public function __construct(Poule $poule, int $nrOfHeadtohead)
    {
        parent::__construct($poule);
        $this->nrOfHeadtohead = $nrOfHeadtohead;
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

    public function setBatchNr(int $batchNr)
    {
        $this->batchNr = $batchNr;
    }

    /**
     * @return Collection | AgainstGamePlace[]
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param int|null $side
     * @return Collection | AgainstGamePlace[] | TogetherGamePlace[]
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

    /**
     * @param Place $place
     * @param int $side
     * @return AgainstGamePlace
     */
    public function addPlace(Place $place, int $side): AgainstGamePlace
    {
        return new AgainstGamePlace($this, $place, $side);
    }

    /**
     * @param Place $place
     * @param int|null $side
     * @return bool
     */
    public function isParticipating(Place $place, int $side = null): bool
    {
        $places = $this->getSidePlaces($side)->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getSide(Place $place): ?int
    {
        if ($this->isParticipating($place, AgainstSide::HOME)) {
            return AgainstSide::HOME;
        }
        if ($this->isParticipating($place, AgainstSide::AWAY)) {
            return AgainstSide::AWAY;
        }
        return null;
    }

    /**
     * @return Collection|Place[]
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
}
