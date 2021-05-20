<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Sport\Variant\Against as AgainstSportVaritant;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class AgainstPartial
{
    /**
     * @var list<AgainstHomeAway>
     */
    protected array $homeAways = [];

    /**
     * @param Poule $poule
     * @param AgainstSportVaritant $sportVariant
     * @param list<int> $startHomeAwayPlaceNrs
     * @param int $maxNrOfIncrements
     */
    public function __construct(
        Poule $poule,
        AgainstSportVaritant $sportVariant,
        array $startHomeAwayPlaceNrs,
        int $maxNrOfIncrements)
    {
        $startHomeAwayPlaces = $this->convertToPlaces($poule, $startHomeAwayPlaceNrs);
        $placeCombinationIterator = new PlaceCombinationIterator($poule, $startHomeAwayPlaces, $maxNrOfIncrements);
        // $placeCombinationIterator = $this->getPlaceCombinationIterator($poule, $sportVariant, $startHomeAwayPlaces);
        // echo "NEW PARTIAL START: " . $placeCombinationIterator->current() . PHP_EOL;
        while ($placeCombinationIterator->valid()) {
            $placeCombination = $placeCombinationIterator->current();
            // echo "S " . $placeCombination . PHP_EOL;
            $homeAway = $this->convertPlaceCombinationToHomeAway($sportVariant, $placeCombination);
            array_push($this->homeAways, $homeAway);
            $placeCombinationIterator->next();
        }
    }

    /**
     * @param Poule $poule
     * @param list<int> $startHomeAwayPlaceNrs
     * @return list<Place>
     * @throws \Exception
     */
    protected function convertToPlaces(Poule $poule, array $startHomeAwayPlaceNrs): array
    {
        return array_map(function (int $placeNr) use ($poule): Place {
            return $poule->getPlace($placeNr);
        }, $startHomeAwayPlaceNrs);
    }

    protected function convertPlaceCombinationToHomeAway(
        AgainstSportVaritant $sportVariant,
        PlaceCombination $placeCombination
    ): AgainstHomeAway {
        $home = [];
        $away = [];
        foreach ($placeCombination->getPlaces() as $place) {
            if (count($home) < $sportVariant->getNrOfHomePlaces()) {
                array_push($home, $place);
            } else {
                array_push($away, $place);
            }
        }
        return new AgainstHomeAway(new PlaceCombination($home), new PlaceCombination($away));
    }

    /**
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(): array
    {
        return $this->homeAways;
    }

    //    protected function getPlaceCombinationIterator(Poule $poule, AgainstSportVaritant $sportVariant, array $startHomeAwayPlaces ): PlaceCombinationIterator {
//        $nrOfSides = 2;
//        if( $sportVariant->getNrOfGamePlaces() === $poule->getPlaces()->count() &&
//            $sportVariant->getNrOfHomePlaces() === $sportVariant->getNrOfAwayPlaces() &&
//            $sportVariant->getNrOfHomePlaces() === $nrOfSides) {
//            return new DoubleFour($poule, $startHomeAwayPlaces);;
//        }
//        return new PlaceCombinationIterator($poule, $startHomeAwayPlaces);;
//    }
}
