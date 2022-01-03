<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\HomeAwayCreator;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class OneVersusOne extends HomeAwayCreator
{
    public function __construct(
        Poule $poule,
        AgainstSportVariant $sportVariant
    ) {
        parent::__construct($poule, $sportVariant);
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
                $homeAways[] = new AgainstHomeAway(
                    new PlaceCombination([$homePlace]),
                    new PlaceCombination([$awayPlace]),
                );
            }
            if (count($home) + count($away) - 1 > 2) {
                $removedSecond = array_splice($home, 1, 1);
                array_unshift($away, array_shift($removedSecond));
                array_push($home, array_pop($away));
            }
        }

//        /** @var \Iterator<string, list<Place>> $homeIt */
//        $homeIt = new CombinationIt($this->poule->getPlaceList(), $this->sportVariant->getNrOfHomePlaces());
//        while ($homeIt->valid()) {
//            $homePlaceCombination = new PlaceCombination($homeIt->current());
//            $awayPlaces = array_diff($this->poule->getPlaceList(), $homeIt->current());
//            /** @var \Iterator<string, list<Place>> $awayIt */
//            $awayIt = new CombinationIt($awayPlaces, $this->sportVariant->getNrOfAwayPlaces());
//            while ($awayIt->valid()) {
//                $awayPlaceCombination = new PlaceCombination($awayIt->current());
//                if ($this->sportVariant->getNrOfHomePlaces() !== $this->sportVariant->getNrOfAwayPlaces()
//                    || $homePlaceCombination->getNumber() < $awayPlaceCombination->getNumber()) {
//                    $homeAway = $this->createHomeAway($homePlaceCombination, $awayPlaceCombination);
//                    array_push($homeAways, $homeAway);
//                }
//                $awayIt->next();
//            }
//            $homeIt->next();
//        }
        return $this->createForOneH2HHelper($homeAways);
    }
}
