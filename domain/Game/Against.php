<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2hSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGppSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Field;
use SportsPlanning\Game;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;

final class Against extends Game
{
    /**
     * @psalm-var Collection<int|string, AgainstGamePlace>
     */
    protected Collection $places;

    public function __construct(
        Planning $planning,
        Poule $poule,
        Field $field,
        protected int $gameRoundNumber
    ) {
        parent::__construct($planning, $poule, $field);
        $this->places = new ArrayCollection();
        $this->planning->getAgainstGames()->add($this);
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
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
    public function getSidePlaces(AgainstSide|null $side = null): Collection
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

    public function createVariant(): AgainstH2hSportVariant|AgainstGppSportVariant
    {
        $sportVariant = $this->getSport()->createVariant();
        if (!($sportVariant instanceof AgainstSportVariant)) {
            throw new \Exception('the wrong sport is linked to the game', E_ERROR);
        }
        return $sportVariant;
    }
}
