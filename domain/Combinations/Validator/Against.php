<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\Validator;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Validator;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

/**
 * @template-extends Validator<AgainstCounter>
 */
class Against extends Validator
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);

        $this->initCounters($this->sportVariant->getNrOfHomePlaces(), $this->sportVariant->getNrOfAwayPlaces());
        if ($this->sportVariant->getNrOfHomePlaces() !== $this->sportVariant->getNrOfAwayPlaces()) {
            $this->initCounters($this->sportVariant->getNrOfAwayPlaces(), $this->sportVariant->getNrOfHomePlaces());
        }
    }

    protected function initCounters(int $nrOfPlaces, int $nrOfAgainstPlaces): void
    {
        $poulePlaces = $this->poule->getPlaceList();
        /** @var \Iterator<int, list<Place>> $placesIt */
        $placesIt = new CombinationIt($poulePlaces, $nrOfPlaces);
        $againstPlacesIt = new CombinationIt($poulePlaces, $nrOfAgainstPlaces);

        while ($placesIt->valid()) {
            $places = $placesIt->current();
            $placeCombination = new PlaceCombination($places);

            /** @var array<int, list<Place>> $allAgainstCombinations */
            $allAgainstCombinations = $againstPlacesIt->toArray();

            $againstPlaceCombinations = array_map(function (array $places): PlaceCombination {
                return new PlaceCombination($places);
            }, $allAgainstCombinations);
            $againstPlaceCombinations = array_values(array_filter($againstPlaceCombinations, function (PlaceCombination $againstPlaceCombinationIt) use ($placeCombination): bool {
                return !$againstPlaceCombinationIt->hasOverlap($placeCombination);
            }));
            $this->counters[$placeCombination->getNumber()] = new AgainstCounter($placeCombination, $againstPlaceCombinations);
            $placesIt->next();
        }
    }

    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }
        $homePlaceCombination = $this->getPlaceCombination($game, Side::Home);
        $awayPlaceCombination = $this->getPlaceCombination($game, Side::Away);
        if (isset($this->counters[$homePlaceCombination->getNumber()])) {
            $this->counters[$homePlaceCombination->getNumber()]->addCombination($awayPlaceCombination);
        }
        if (isset($this->counters[$awayPlaceCombination->getNumber()])) {
            $this->counters[$awayPlaceCombination->getNumber()]->addCombination($homePlaceCombination);
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
        $header = ' all against-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
        $lines = '';
        foreach ($this->counters as $counter) {
            $lines .= $counter;
        }

        return $header . $lines;
    }
}
