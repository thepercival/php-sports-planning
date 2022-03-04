<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\HomeAwayCreator;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class H2h extends HomeAwayCreator
{
    public function __construct(protected Poule $poule/*, protected AgainstH2h $sportVariant*/)
    {
        parent::__construct(/*$poule, $sportVariant*/);
    }

    /**
     * @return list<AgainstHomeAway>
     */
    public function createForOneH2H(): array
    {
//        $nrOfPlaces = $this->poule->getPlaces()->count();
//        $this->nrOfGamesPerPlace = $this->sportVariant->getNrOfGamesPerPlaceOneH2H($nrOfPlaces);
//        $this->minNrOfHomeGamesPerPlace = (int)floor($this->nrOfGamesPerPlace / 2);

        $homeAways = [];

        /** @var list<Place|null> $schedulePlaces */
        $schedulePlaces = array_values($this->poule->getPlaces()->toArray());

        if (count($this->poule->getPlaces()) % 2 != 0) {
            array_push($schedulePlaces, null);
        }
        $away = array_splice($schedulePlaces, (int)(count($schedulePlaces) / 2));
        $home = $schedulePlaces;
        for ($gameRoundNr = 0; $gameRoundNr < count($home) + count($away) - 1; $gameRoundNr++) {
            for ($gameNr = 0; $gameNr < count($home); $gameNr++) {
                /** @var Place|null $homePlace */
                $homePlace = $home[$gameNr];
                /** @var Place|null $awayPlace */
                $awayPlace = $away[$gameNr];
                if ($homePlace === null || $awayPlace === null) {
                    continue;
                }
                $homeAways[] = $this->createHomeAway($homePlace, $awayPlace);
            }
            if (count($home) + count($away) - 1 > 2) {
                $removedSecond = array_splice($home, 1, 1);
                array_unshift($away, array_shift($removedSecond));
                array_push($home, array_pop($away));
            }
        }

        return $this->swap($homeAways);
    }

    protected function createHomeAway(Place $home, Place $away): AgainstHomeAway
    {
        if ($this->shouldSwap($home, $away)) {
            return new AgainstHomeAway(new PlaceCombination([$away]), new PlaceCombination([$home]));
        }
        return new AgainstHomeAway(new PlaceCombination([$home]), new PlaceCombination([$away]));
    }

    protected function shouldSwap(Place $home, Place $away): bool
    {
        $even = (($home->getNumber() + $away->getNumber()) % 2) === 0;
        if ($even && $home->getNumber() < $away->getNumber()) {
            return true;
        }
        if (!$even && $home->getNumber() > $away->getNumber()) {
            return true;
        }
        return false;
    }
}
