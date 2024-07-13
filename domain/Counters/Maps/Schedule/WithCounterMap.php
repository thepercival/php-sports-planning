<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\Mapper as CombinationMapper;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Poule;

final class WithCounterMap extends PlaceCombinationCounterMap
{
    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $combinationMapper = new CombinationMapper();
        $againstVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return (($sportVariant instanceof AgainstGpp) || ($sportVariant instanceof AgainstH2h));
            }));
        $withCounters = $combinationMapper->getWithMap($poule, $againstVariants);
        parent::__construct($withCounters);
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
            $this->addPlaceCombination($withPlaceCombination);
        }
    }

}
