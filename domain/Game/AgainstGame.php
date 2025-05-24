<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Field;
use SportsPlanning\Game\AgainstGamePlace as AgainstGamePlace;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsOne;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsTwo;
use SportsPlanning\Sports\Plannable\PlannableAgainstTwoVsTwo;
use SportsPlanning\Sports\Plannable\PlannableTogetherSport;

class AgainstGame extends GameAbstract
{
    /**
     * @psalm-var Collection<int|string, AgainstGamePlace>
     */
    protected Collection $places;

    public function __construct(
        Planning $planning,
        Poule $poule,
        Field $field,
        public int $cyclePartNr,
        public int $cycleNr
    ) {
        parent::__construct($planning, $poule, $field);
        $this->places = new ArrayCollection();
        $this->planning->getAgainstGames()->add($this);
    }

    /**
     * @return Collection<int|string, AgainstGamePlace>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param AgainstSide|null $side
     * @return Collection<int|string,AgainstGamePlace>
     */
    public function getSidePlaces(AgainstSide $side = null): Collection
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

    public function addPlace(Place $place, AgainstSide $side): AgainstGamePlace
    {
        return new AgainstGamePlace($this, $place, $side);
    }

    public function isParticipating(Place $place, AgainstSide|null $side = null): bool
    {
        $places = new ArrayCollection(
            array_map(function (AgainstGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            }, $this->getSidePlaces($side)->toArray() )
        );
        return $places->contains($place);
    }

    public function getSide(Place $place): AgainstSide
    {
        if ($this->isParticipating($place, AgainstSide::Home)) {
            return AgainstSide::Home;
        }
        if ($this->isParticipating($place, AgainstSide::Away)) {
            return AgainstSide::Away;
        }
        throw new Exception('kan kant niet vinden', E_ERROR);
    }

    /**
     * @return Collection<int|string,Place>
     */
    public function getPoulePlaces(): Collection
    {
        if ($this->poulePlaces === null) {
            $this->poulePlaces = new ArrayCollection(
                array_map(function (AgainstGamePlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }, $this->getPlaces()->toArray() )
            );
        }
        return $this->poulePlaces;
    }

    public function getSport(): PlannableAgainstOneVsOne|PlannableAgainstOneVsTwo|PlannableAgainstTwoVsTwo
    {
        $sport = $this->field->getSport();
        if ($sport instanceof PlannableTogetherSport) {
            throw new \Exception('the wrong sport is linked to the game', E_ERROR);
        }
        return $sport;
    }

    public function createSport(): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo
    {
        return $this->getSport()->sport;
    }

    public function createHomeAway(): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
        $againstSport = $this->createSport();
        $homePlaces = array_values($this->getSidePlaces(AgainstSide::Home)->toArray());
        $homePlaceNrs = array_map(fn(GamePlaceAbstract $homeGamePlace) => $homeGamePlace->getPlace()->getPlaceNr(), $homePlaces);
        $awayPlaces = array_values($this->getSidePlaces(AgainstSide::Away)->toArray());
        $awayPlaceNrs = array_map(fn(GamePlaceAbstract $awayPlace) => $awayPlace->getPlace()->getPlaceNr(), $awayPlaces);
        if( $againstSport instanceof AgainstOneVsOne ) {
            return new OneVsOneHomeAway($homePlaceNrs[0], $awayPlaceNrs[0]);
        } else if( $againstSport instanceof AgainstOneVsTwo ) {
            return new OneVsTwoHomeAway($homePlaceNrs[0], new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]));
        } else { // TwoVsTwoHomeAway
            return new TwoVsTwoHomeAway(
                new DuoPlaceNr($homePlaceNrs[0], $homePlaceNrs[1]),
                new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]));
        }
    }
}
