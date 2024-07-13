<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;
use SportsPlanning\Poule;

final class TogetherCounterMap extends PlaceCombinationCounterMap
{
    public function __construct(Poule $poule)
    {
        $placeCombinationCounterMap = [];
        foreach ($poule->getPlaces() as $place) {
            foreach ($poule->getPlaces() as $coPlace) {
                if ($place->getPlaceNr() >= $coPlace->getPlaceNr() ) {
                    continue;
                }
                $placeCombination = new PlaceCombination([$place, $coPlace]);
                $newCounter = new CounterForPlaceCombination($placeCombination);
                $placeCombinationCounterMap[$placeCombination->getIndex()] = $newCounter;
            }
        }
        parent::__construct($placeCombinationCounterMap);
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
            $this->addPlaceCombination($withPlaceCombination);
        }
        foreach ($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
            $this->addPlaceCombination($againstPlaceCombination);
        }
    }
}
