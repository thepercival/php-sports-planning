<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @param bool|null $homeAway
     * @return Collection | AgainstGamePlace[] | TogetherGamePlace[]
     */
    public function getPlaces(bool $homeAway = null): Collection
    {
        if ($homeAway === null) {
            return $this->places;
        }
        return $this->places->filter(
                function (AgainstGamePlace $gamePlace) use ($homeAway): bool {
                    return $gamePlace->getHomeAway() === $homeAway;
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
     * @param bool $homeAway
     * @return AgainstGamePlace
     */
    public function addPlace(Place $place, bool $homeAway): AgainstGamePlace
    {
        return new AgainstGamePlace($this, $place, $homeAway);
    }

    /**
     * @param Place $place
     * @param bool|null $homeAway
     * @return bool
     */
    public function isParticipating(Place $place, bool $homeAway = null): bool
    {
        $places = $this->getPlaces($homeAway)->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getHomeAway(Place $place): ?bool
    {
        if ($this->isParticipating($place, AgainstGame::HOME)) {
            return AgainstGame::HOME;
        }
        if ($this->isParticipating($place, AgainstGame::AWAY)) {
            return AgainstGame::AWAY;
        }
        return null;
    }

    /**
     * @return Collection|Place[]
     */
    public function getPoulePlaces(): Collection
    {
        if( $this->poulePlaces === null ) {
            $this->poulePlaces = $this->getPlaces()->map( function (AgainstGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            });
        }
        return $this->poulePlaces;
    }
}
