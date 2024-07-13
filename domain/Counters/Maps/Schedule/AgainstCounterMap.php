<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\Mapper as CombinationMapper;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Poule;

final class AgainstCounterMap extends PlaceCombinationCounterMap
{
    public function __construct(Poule $poule)
    {
        $combinationMapper = new CombinationMapper();
        parent::__construct($combinationMapper->getAgainstMap($poule));
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getAgainstPlaceCombinations() as $withPlaceCombination) {
            $this->addPlaceCombination($withPlaceCombination);
        }
    }
}
