<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Field;
use SportsPlanning\Game as GameBase;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;

class Together extends GameBase
{
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherGamePlace>|PersistentCollection<int|string, TogetherGamePlace>
     * @psalm-var ArrayCollection<int|string, TogetherGamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;

    public function __construct(Planning $planning, Poule $poule, Field $field)
    {
        parent::__construct($planning, $poule, $field);
        $this->places = new ArrayCollection();
        $this->planning->getTogetherGames()->add($this);
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    public function setBatchNr(int $batchNr): void
    {
        $this->batchNr = $batchNr;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, TogetherGamePlace>|PersistentCollection<int|string, TogetherGamePlace>
     * @psalm-return ArrayCollection<int|string, TogetherGamePlace>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    /**
     * @param int $gameRoundNumber
     * @return Collection<int|string, TogetherGamePlace>
     */
    public function getPlacesForRoundNumber(int $gameRoundNumber): Collection
    {
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

    public function isParticipating(Place $place): bool
    {
        foreach ($this->getPlaces() as $gamePlace) {
            if ($gamePlace->getPlace() === $place) {
                return true;
            }
        }
        return false;
    }
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
     * @return Collection<int|string,Place>
     */
    public function getPoulePlaces(): Collection
    {
        if ($this->poulePlaces === null) {
            $this->poulePlaces = $this->getPlaces()->map(function (TogetherGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            });
        }
        return $this->poulePlaces;
    }

    public function createVariant(): SingleSportVariant|AllInOneGameSportVariant
    {
        if ($this->getSport()->getGameMode() === GameMode::SINGLE) {
            return new SingleSportVariant($this->getSport()->getNrOfGamePlaces(), $this->getSport()->getNrOfGamesPerPlace());
        }
        return new AllInOneGameSportVariant($this->getSport()->getNrOfGamePlaces());

    }
}
