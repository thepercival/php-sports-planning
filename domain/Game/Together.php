<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsPlanning\Game as GameBase;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;

class Together extends GameBase
{
    protected int $gameAmountNumber;
    /**
     * @var Collection | TogetherGamePlace[]
     */
    protected $places;

    public function __construct(Poule $poule)
    {
        parent::__construct($poule);
        $this->places = new ArrayCollection();
        $this->poule->getTogetherGames()->add($this);
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
     * @param int|null $gameRoundNumber
     * @return Collection | TogetherGamePlace[]
     */
    public function getPlaces(int $gameRoundNumber = null): Collection
    {
        if ($gameRoundNumber === null) {
            return $this->places;
        }
        return $this->places->filter(
                function (TogetherGamePlace $gamePlace) use ($gameRoundNumber): bool {
                    return $gamePlace->getGameRoundNumber() === $gameRoundNumber;
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

//    /**
//     * @param Place $place
//     * @param bool $homeAway
//     * @return GamePlace
//     */
//    public function addPlace(Place $place, bool $homeAway): GamePlace
//    {
//        return new GamePlace($this, $place, $homeAway);
//    }
//
//    /**
//     * @param Place $place
//     * @param bool|null $homeAway
//     * @return bool
//     */
//    public function isParticipating(Place $place, bool $homeAway = null): bool
//    {
//        $places = $this->getPlaces($homeAway)->map(function ($gamePlace) {
//            return $gamePlace->getPlace();
//        });
//        return $places->contains($place);
//    }
//
//    public function getHomeAway(Place $place): ?bool
//    {
//        if ($this->isParticipating($place, Game::HOME)) {
//            return Game::HOME;
//        }
//        if ($this->isParticipating($place, Game::AWAY)) {
//            return Game::AWAY;
//        }
//        return null;
//    }
//
    /**
     * @return Collection|Place[]
     */
    public function getPoulePlaces(): Collection
    {
        if( $this->poulePlaces === null ) {
            $this->poulePlaces = $this->getPlaces()->map( function (TogetherGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            });
        }
        return $this->poulePlaces;
    }
}
