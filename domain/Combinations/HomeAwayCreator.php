<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

class HomeAwayCreator
{
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $gameCounterMap = [];
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $homeCounterMap = [];

    protected int $minNrOfHomeGamesPerPlace = 0;
    protected int $nrOfGamesPerPlace = 0;


    public function __construct(protected AgainstSportVariant $sportVariant)
    {
    }

    /**
     * @param Poule $poule
     */
    protected function initCounters(Poule $poule): void
    {
        $this->gameCounterMap = [];
        $this->homeCounterMap = [];
        foreach ($poule->getPlaces() as $place) {
            $this->gameCounterMap[$place->getNumber()] = new PlaceCounter($place);
            $this->homeCounterMap[$place->getNumber()] = new PlaceCounter($place);
        }
    }

    /**
     * @param Poule $poule
     * @return list<AgainstHomeAway>
     */
    public function createForOneH2H(Poule $poule): array
    {
        $this->initCounters($poule);
        $nrOfPlaces = $poule->getPlaces()->count();
        $this->nrOfGamesPerPlace = $this->sportVariant->getNrOfGamesPerPlaceOneH2H($nrOfPlaces);
        $this->minNrOfHomeGamesPerPlace = (int)floor($this->nrOfGamesPerPlace / 2);

        $homeAways = [];

        // $nrOfHomeAwaysOneH2H = $this->sportVariant->getNrOfGamesOneH2H($poule->getPlaces()->count());

        /** @var \Iterator<string, list<Place>> $homeIt */
        $homeIt = new CombinationIt($poule->getPlaceList(), $this->sportVariant->getNrOfHomePlaces());
        while ($homeIt->valid()) {
            $homePlaceCombination = new PlaceCombination($homeIt->current());
            $awayPlaces = array_diff($poule->getPlaceList(), $homeIt->current());
            /** @var \Iterator<string, list<Place>> $awayIt */
            $awayIt = new CombinationIt($awayPlaces, $this->sportVariant->getNrOfAwayPlaces());
            while ($awayIt->valid()) {
                $awayPlaceCombination = new PlaceCombination($awayIt->current());
                if ($this->sportVariant->getNrOfHomePlaces() !== $this->sportVariant->getNrOfAwayPlaces()
                    || $homePlaceCombination->getNumber() < $awayPlaceCombination->getNumber()) {
                    $homeAway = $this->createHomeAway($homePlaceCombination, $awayPlaceCombination);
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
//
//    /**
//     * @param list<Place> $places
//     * @return list<int>
//     */
//    protected function getPlaceNrs(array $places): array
//    {
//        return array_values(array_map(function (Place $place): int {
//            return $place->getNumber();
//        }, $places));
//    }

    protected function createHomeAway(PlaceCombination $home, PlaceCombination $away): AgainstHomeAway
    {
        if ($this->shouldSwap($home, $away)) {
            foreach ($home->getPlaces() as $homePlace) {
                $this->gameCounterMap[$homePlace->getNumber()]->increment();
            }
            foreach ($away->getPlaces() as $awayPlace) {
                $this->gameCounterMap[$awayPlace->getNumber()]->increment();
                $this->homeCounterMap[$awayPlace->getNumber()]->increment();
            }
            return new AgainstHomeAway($away, $home);
        }
        foreach ($home->getPlaces() as $homePlace) {
            $this->gameCounterMap[$homePlace->getNumber()]->increment();
            $this->homeCounterMap[$homePlace->getNumber()]->increment();
        }
        foreach ($away->getPlaces() as $awayPlace) {
            $this->gameCounterMap[$awayPlace->getNumber()]->increment();
        }
        return new AgainstHomeAway($home, $away);
    }

    protected function shouldSwap(PlaceCombination $home, PlaceCombination $away): bool
    {
        if ($this->sportVariant->getNrOfHomePlaces() !== $this->sportVariant->getNrOfAwayPlaces()) {
            return false;
        }
        if ($this->mustBeHome($home)) {
            return false;
        }
        if ($this->mustBeHome($away)) {
            return true;
        }
        return $this->getNrOfHomeGames($home) > $this->getNrOfHomeGames($away);
    }

    protected function mustBeHome(PlaceCombination $placeCombination): bool
    {
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames = $this->getNrOfGamesForPlace($place);
            $nrOfHomeGames = $this->getNrOfHomeGamesForPlace($place);
            $nrOfGamesLeft = $this->nrOfGamesPerPlace - $nrOfGames;
            if ($nrOfGamesLeft === ($this->minNrOfHomeGamesPerPlace - $nrOfHomeGames)) {
                return true;
            }
        }
        return false;
    }

    protected function getNrOfGames(PlaceCombination $placeCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames += $this->getNrOfGamesForPlace($place);
        }
        return $nrOfGames;
    }

    protected function getNrOfGamesForPlace(Place $place): int
    {
        return $this->gameCounterMap[$place->getNumber()]->count();
    }

    protected function getNrOfHomeGames(PlaceCombination $placeCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames += $this->getNrOfHomeGamesForPlace($place);
        }
        return $nrOfGames;
    }

    protected function getNrOfHomeGamesForPlace(Place $place): int
    {
        return $this->homeCounterMap[$place->getNumber()]->count();
    }
}
