<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Poule;

abstract class HomeAwayCreator
{
    private bool $swap = false;

    public function __construct(
        protected Poule $poule,
        protected AgainstSportVariant $sportVariant
    ) {
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    protected function createForOneH2HHelper(array $homeAways): array
    {
        if ($this->swap === true) {
            $homeAways = $this->swapHomeAways($homeAways);
        }
        $this->swap = !$this->swap;
        return $homeAways;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    private function swapHomeAways(array $homeAways): array
    {
        $swapped = [];
        foreach ($homeAways as $homeAway) {
            array_push($swapped, $homeAway->swap());
        }
        return $swapped;
    }
}
