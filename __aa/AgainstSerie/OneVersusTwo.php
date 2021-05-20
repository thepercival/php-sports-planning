<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\AgainstSerie;

// gamePl      pl       games(per h2h)      partials    gamesPerPartial gamesPerPlacePerPartial gamesPerPlace
// 1 vs 2      3        3(3)                1           3               2                       3(2 x 1 + 1)
// 1 vs 2      4        12(12)              3           4               3                       10(3 x 2 + 4)
// 1 vs 2      5        30(30)              6           5               4                       17(4 x 3 + 5)
// 1 vs 2      6        60(60)              10          6               5                       36(5 x 6 + 6)

use drupol\phpermutations\Generators\Permutations;
use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\AgainstPartial;
use SportsPlanning\Combinations\AgainstSerie;
use SportsPlanning\Poule;

class OneVersusTwo extends AgainstSerie
{
    public function __construct(Poule $poule, AgainstSportVariant $sportVariant)
    {
        parent::__construct($poule, $sportVariant);
        $maxNrOfIncrements = $sportVariant->getNrOfGamesOnePartial($poule->getPlaces()->count());
        $placeNrs = $this->getPlaceNrs($poule->getPlaceList());
        /** @var \Iterator<string, list<int>> $it */
        $it = new CombinationIt($placeNrs, $this->sportVariant->getNrOfGamePlaces());
        while ($it->valid()) {
            $partial = new AgainstPartial($poule, $sportVariant, $it->current(), $maxNrOfIncrements);
            array_push($this->partials, $partial);
            $it->next();
        }
    }

    /**
     * @param int $nrOfHomeAways
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(int $nrOfHomeAways): array
    {
        $homeAways = parent::getHomeAways($nrOfHomeAways);

        $nrOfHomeAwaysOneH2H = $this->sportVariant->getNrOfGamesOneH2H($this->poule->getPlaces()->count());
        $nrOfHomeAways -= $nrOfHomeAwaysOneH2H;
        if ($nrOfHomeAways <= 0) {
            return $homeAways;
        }

        $homeAwaysToReverse = $homeAways;
        while ($nrOfHomeAways > 0) {
            $homeAwayToSwap = array_shift($homeAwaysToReverse);
            if( $homeAwayToSwap === null ) {
                break;
            }
            array_push($homeAways, $homeAwayToSwap->swap());
            $nrOfHomeAways--;
        }
        return $homeAways;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    protected function swapHomeAways(array $homeAways): array
    {
        return array_values(array_map(function (AgainstHomeAway $homeAway): AgainstHomeAway {
            return $homeAway->swap();
        }, $homeAways));
    }
}
