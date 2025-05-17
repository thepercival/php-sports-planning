<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\GameMode;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Field;
use SportsPlanning\Game\GameAbstract as GameBase;
use SportsPlanning\Game\TogetherGamePlace as TogetherGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsOne;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsTwo;
use SportsPlanning\Sports\Plannable\PlannableAgainstTwoVsTwo;
use SportsPlanning\Sports\Plannable\PlannableTogetherSport;

class TogetherGame extends GameBase
{
    /**
     * @var Collection<int|string, TogetherGamePlace>
     */
    protected Collection $places;

    public function __construct(Planning $planning, Poule $poule, Field $field)
    {
        parent::__construct($planning, $poule, $field);
        $this->places = new ArrayCollection();
        $this->planning->getTogetherGames()->add($this);
    }

    /**
     * @return Collection<int|string, TogetherGamePlace>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
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
            $this->poulePlaces = new ArrayCollection(
                array_map(function (TogetherGamePlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }, $this->getPlaces()->toArray() )
            );
        }
        return $this->poulePlaces;
    }

    /**
     * @return list<DuoPlaceNr>
     * @throws \Exception
     */
    public function convertToDuoPlaceNrs(): array {
        $duoPlaceNrs = [];
        foreach($this->getPlaces() as $gamePlaceOne) {
            foreach($this->getPlaces() as $gamePlaceTwo) {
                if( $gamePlaceOne >= $gamePlaceTwo ) {
                    continue;
                }
                $duoPlaceNrs[] = new DuoPlaceNr(
                    $gamePlaceOne->getPlace()->getPlaceNr(),
                    $gamePlaceTwo->getPlace()->getPlaceNr());
            }
        }
        return $duoPlaceNrs;
    }

    public function getSport(): PlannableTogetherSport
    {
        $sport = $this->field->getSport();
        if (!($sport instanceof PlannableTogetherSport)) {
            throw new \Exception('the wrong sport is linked to the game', E_ERROR);
        }
        return $sport;
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
