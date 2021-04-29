<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

class StrategyCalculator
{
    /**
     * @param list<SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant> $sportVariants
     * @return int
     */
    public function calculate(array $sportVariants): int
    {
        //foreach ($sportVariants as $sportVariant) {
          //  if (!($sportVariant instanceof AgainstSportVariant && $sportVariant->getNrOfGamePlaces() > 2)) {
                return Strategy::Static;
            //}
        //}
        //return Strategy::Incremental;
    }
}
