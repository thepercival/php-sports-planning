<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class HomeAwayCreator
{
    public function __construct(protected AgainstSportVariant $sportVariant)
    {
    }

    /**
     * @param Poule $poule
     * @return list<AgainstHomeAway>
     */
    public function createForOneH2H(Poule $poule): array
    {
        $homeAways = [];
        // $nrOfHomeAwaysOneH2H = $this->sportVariant->getNrOfGamesOneH2H($poule->getPlaces()->count());

        /** @var \Iterator<string, list<Place>> $homeIt */
        $homeIt = new CombinationIt($poule->getPlaceList(), $this->sportVariant->getNrOfHomePlaces());
        while ($homeIt->valid()) {
            $homePlaceCombination = new PlaceCombination($homeIt->current());
            $awayPlaces = array_diff( $poule->getPlaceList(), $homeIt->current());
            /** @var \Iterator<string, list<Place>> $awayIt */
            $awayIt = new CombinationIt($awayPlaces, $this->sportVariant->getNrOfAwayPlaces());
            while ($awayIt->valid()) {
                $awayPlaceCombination = new PlaceCombination($awayIt->current());
                if( $homePlaceCombination->getNumber() < $awayPlaceCombination->getNumber() ) {
                    $homeAway = new AgainstHomeAway($homePlaceCombination, $awayPlaceCombination);
                    array_push($homeAways, $homeAway);
                }
                $awayIt->next();
            }
            $homeIt->next();
        }
        return $homeAways;
    }

//    protected function getValidNrOfHomeAways(int $nrOfHomeAways): int
//    {
//        $maxNrOfHomeAways = $this->sportVariant->getNrOfGamesOneSerie($this->poule->getPlaces()->count());
//        if ($nrOfHomeAways > $maxNrOfHomeAways) {
//            return $maxNrOfHomeAways;
//        }
//        return $nrOfHomeAways;
//    }

//    /**
//     * @param AgainstPartial|null $partial
//     * @param int $maxNrOfHomeAways
//     * @return list<AgainstHomeAway>
//     */
//    protected function getHomeAwaysFromPartial(AgainstPartial|null $partial, int $maxNrOfHomeAways): array
//    {
//        if ($partial === null) {
//            return [];
//        }
//        $homeAways = $partial->getHomeAways();
//        /** @var array<int, AgainstHomeAway> $splicedHomeAway */
//        $splicedHomeAway = array_splice($homeAways, 0, $maxNrOfHomeAways);
//        return array_values($splicedHomeAway);
//    }

    /**
     * @param list<Place> $places
     * @return list<int>
     */
    protected function getPlaceNrs(array $places): array
    {
        return array_values(array_map(function (Place $place): int {
            return $place->getNumber();
        }, $places));
    }

    /**
     * @param list<Place> $placeNrs
     * @return list<list<Place>>
     */
    protected function getUniqueCombinations(array $placeNrs): array
    {
        if( count($placeNrs) === 2 || count($placeNrs) === 3)  {
            return [$placeNrs];
        }
        return [
            $placeNrs,
            [$placeNrs[0], $placeNrs[2], $placeNrs[1], $placeNrs[3]],
            [$placeNrs[0], $placeNrs[3], $placeNrs[1], $placeNrs[2]]
        ];
    }

    protected function convertPlaceCombinationToHomeAway(
        PlaceCombination $placeCombination
    ): AgainstHomeAway {
        $home = [];
        $away = [];
        foreach ($placeCombination->getPlaces() as $place) {
            if (count($home) < $this->sportVariant->getNrOfHomePlaces()) {
                array_push($home, $place);
            } else {
                array_push($away, $place);
            }
        }
        return new AgainstHomeAway(new PlaceCombination($home), new PlaceCombination($away));
    }
}
