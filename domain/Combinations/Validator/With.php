<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\Validator;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\MultipleCombinationsCounter\With as WithCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Validator;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

/**
 * @template-extends Validator<WithCounter>
 */
class With extends Validator
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);

        $this->initCounters($this->sportVariant->getNrOfHomePlaces());
        if ($this->sportVariant->getNrOfHomePlaces() !== $this->sportVariant->getNrOfAwayPlaces()) {
            $this->initCounters($this->sportVariant->getNrOfAwayPlaces());
        }
    }

    protected function initCounters(int $nrOfPlaces): void
    {
        $poulePlaces = $this->poule->getPlaceList();
        foreach ($this->poule->getPlaces() as $place) {
            $withPlaceCombinations = $this->getWithCombinations($place, $poulePlaces, $nrOfPlaces);
            $this->counters[$place->getNumber()] = new WithCounter($place, $withPlaceCombinations);
        }
    }

    /**
     * @param Place $place
     * @param list<Place> $places
     * @param int $nrOfPlaces
     * @return list<PlaceCombination>
     */
    protected function getWithCombinations(Place $place, array $places, int $nrOfPlaces): array
    {
        $placesMinPlace = array_values(array_filter($places, function (Place $placeIt) use ($place): bool {
            return $placeIt !== $place;
        }));
        $combinationIt = new CombinationIt($placesMinPlace, $nrOfPlaces - 1);
        /** @var array<int, list<Place>> $allCombinations */
        $allCombinations = $combinationIt->toArray();
        return array_values(array_map(function (array $combinations): PlaceCombination {
            return new PlaceCombination($combinations);
        }, $allCombinations));
    }

    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }
        $homePlaceCombination = $this->getPlaceCombination($game, Side::Home);
        $awayPlaceCombination = $this->getPlaceCombination($game, Side::Away);

        $this->addCombinations($homePlaceCombination);
        $this->addCombinations($awayPlaceCombination);
    }

    private function addCombinations(PlaceCombination $placeCombination): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $place) {
            $withPlaceCombinations = $this->getWithCombinations($place, $places, count($places));
            if (isset($this->counters[$place->getNumber()])) {
                $this->counters[$place->getNumber()]->addCombinations($withPlaceCombinations);
            }
        }
    }

    public function balanced(): bool
    {
        foreach ($this->counters as $counter) {
            if (!$counter->balanced()) {
                return false;
            }
        }
        return true;
    }

    public function totalCount(): int
    {
        $totalCount = 0;
        foreach ($this->counters as $counter) {
            $totalCount += $counter->totalCount();
        }
        return $totalCount;
    }

    public function __toString(): string
    {
        $header = ' all with-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
        $lines = '';
        foreach ($this->counters as $counter) {
            $lines .= $counter;
        }

        return $header . $lines;
    }
}
