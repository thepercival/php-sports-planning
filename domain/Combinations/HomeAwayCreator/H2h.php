<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\HomeAwayCreator;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;

final class H2h extends HomeAwayCreator
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param AgainstH2hWithPoule $againstH2hWithPoule
     * @return list<HomeAway>
     */
    public function createForOneH2H(AgainstH2hWithPoule $againstH2hWithPoule): array
    {
        $poule = $againstH2hWithPoule->getPoule();

        $homeAways = [];

        /** @var list<Place|null> $schedulePlaces */
        $schedulePlaces = array_values($poule->getPlaces()->toArray());

        if (count($poule->getPlaces()) % 2 != 0) {
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

    protected function createHomeAway(Place $home, Place $away): HomeAway
    {
        if ($this->shouldSwap($home, $away)) {
            return new HomeAway(new PlaceCombination([$away]), new PlaceCombination([$home]));
        }
        return new HomeAway(new PlaceCombination([$home]), new PlaceCombination([$away]));
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
