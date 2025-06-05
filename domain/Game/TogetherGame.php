<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Field;
use SportsPlanning\Game\GameAbstract;
use SportsPlanning\Game\TogetherGamePlace as TogetherGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;

final class TogetherGame extends GameAbstract
{
    /**
     * @var list<TogetherGamePlace>
     */
    protected array $gamePlaces = [];

    public function __construct(Poule $poule, Field $field)
    {
        parent::__construct($poule, $field);
        $this->poule->addGame($this);
    }

    /**
     * @return list<TogetherGamePlace>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    public function addGamePlace(TogetherGamePlace $gamePlace): void
    {
        $this->gamePlaces[] = $gamePlace;
    }

//    /**
//     * @param int $gameRoundNumber
//     * @return Collection<int|string, TogetherGamePlace>
//     */
//    public function getPlacesForRoundNumber(int $gameRoundNumber): Collection
//    {
//        return $this->places->filter(
//            function (TogetherGamePlace $gamePlace) use ($gameRoundNumber): bool {
//                return $gamePlace->getGameRoundNumber() === $gameRoundNumber;
//            }
//        );
//    }

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

    public function isParticipating(int $placeNr): bool
    {
        foreach ($this->gamePlaces as $gamePlace) {
            if ($gamePlace->placeNr === $placeNr) {
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
     * @return list<int>
     */
    public function getPlaceNrs(): array
    {
        return array_map(function(TogetherGamePlace $gamePlace): int {
            return $gamePlace->placeNr;
        }, $this->getGamePlaces() );
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        return array_map(function(TogetherGamePlace $gamePlace): Place {
            return $this->poule->getPlace($gamePlace->placeNr);
        }, $this->getGamePlaces() );
    }

    /**
     * @return list<DuoPlaceNr>
     * @throws \Exception
     */
    public function convertToDuoPlaceNrs(): array {
        $duoPlaceNrs = [];
        foreach($this->getPlaceNrs() as $placeNrOne) {
            foreach($this->getPlaceNrs() as $placeNrTwo) {
                if( $placeNrOne >= $placeNrTwo ) {
                    continue;
                }
                $duoPlaceNrs[] = new DuoPlaceNr($placeNrOne, $placeNrTwo);
            }
        }
        return $duoPlaceNrs;
    }

//    public function createVariant(): Single|AllInOneGame
//    {
//        if ($this->getSport()->getGameMode() === GameMode::Single) {
//            return new Single(
//                $this->getSport()->getNrOfGamePlaces(),
//                $this->getSport()->getNrOfGamesPerPlace()
//            );
//        }
//        return new AllInOneGame($this->getSport()->getNrOfGamePlaces());
//    }
}
