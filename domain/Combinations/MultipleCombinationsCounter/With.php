<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\MultipleCombinationsCounter;

use SportsPlanning\Combinations\MultipleCombinationsCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use Stringable;

class With extends MultipleCombinationsCounter implements Stringable
{
    /**
     * @param Place $place
     * @param list<PlaceCombination> $placeCombinations
     */
    public function __construct(protected Place $place, array $placeCombinations)
    {
        parent::__construct($placeCombinations);
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function addCombinations(array $placeCombinations): void
    {
        foreach ($placeCombinations as $placeCombination) {
            $this->addCombination($placeCombination);
        }
    }

//    protected function initAgainstCounters(int $nrOfPlaces, int $nrOfVersusPlaces): void
//    {
//        $poulePlaces = $this->poule->getPlaceList();
//        $placesIt = new CombinationIt($poulePlaces, $nrOfPlaces);
//        $versusPlacesIt = new CombinationIt($poulePlaces, $nrOfVersusPlaces);
//
//        while ($placesIt->valid()) {
//            /** @var list<Place> $places */
//            $places = $placesIt->current();
//            $placeCombination = new PlaceCombination($places);
//
//            $versusPlaceCombinations = array_map(function (array $places): PlaceCombination {
//                return new PlaceCombination($places);
//            }, $versusPlacesIt->toArray());
//            $versusPlaceCombinations = array_filter($versusPlaceCombinations, function (PlaceCombination $versusPlaceCombination) use ($placeCombination): bool {
//                return !$versusPlaceCombination->hasOverlap($placeCombination);
//            });
//            $this->againstCounters[$placeCombination->getNumber()] = new MultipleCombinationsCounter($placeCombination, $versusPlaceCombinations);
//            $placesIt->next();
//        }
//    }
//
//    public function getPlaceCombination(AgainstGame $game, int $side): PlaceCombination
//    {
//        $poulePlaces = $game->getSidePlaces($side)->map(function (AgainstGamePlace $gamePlace): Place {
//            return $gamePlace->getPlace();
//        });
//        return new PlaceCombination(array_values($poulePlaces->toArray()));
//    }
//


//
//    public function addGame(AgainstGame $game): void
//    {
//        if ($game->getSport() !== $this->sport) {
//            return;
//        }
//        $homePlaceCombination = $this->getPlaceCombination($game, Side::HOME);
//        $awayPlaceCombination = $this->getPlaceCombination($game, Side::AWAY);
//        if (isset($this->versusCounters[$homePlaceCombination->getNumber()])) {
//            $this->againstCounters[$homePlaceCombination->getNumber()]->addVersus($awayPlaceCombination);
//        }
//        if (isset($this->versusCounters[$awayPlaceCombination->getNumber()])) {
//            $this->againstCounters[$awayPlaceCombination->getNumber()]->addVersus($homePlaceCombination);
//        }
//    }

    public function __toString(): string
    {
        $header = ' all with-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
        return $header . parent::__toString();
    }
}
