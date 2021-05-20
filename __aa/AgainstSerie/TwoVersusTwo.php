<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\AgainstSerie;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\AgainstPartial;
use SportsPlanning\Combinations\AgainstSerie;
use SportsPlanning\Poule;

// gamePl      pl       games(per h2h)      partials    gamesPerPartial gamesPerPlacePerPartial gamesPerPlace
// 2 vs 2      4        6(3)                3           2               1                       ?
// 2 vs 2      5        30(15)              6           5               4                       24
// 2 vs 2      6        90(45)              15          6               ?                       30
// 2 vs 2      7        210(105)            30          7               ?                       ?

class TwoVersusTwo extends AgainstSerie
{
    public function __construct(Poule $poule, AgainstSportVariant $sportVariant)
    {
        parent::__construct($poule, $sportVariant);
    }

    /**
     * @param int $nrOfHomeAways
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(int $nrOfHomeAways): array
    {
        $homeAways = [];
        $nrOfHomeAways = $this->getValidNrOfHomeAways($nrOfHomeAways);
        $partials = $this->getPartialsHelper($nrOfHomeAways);
        while ($nrOfHomeAways > 0 && count($partials) > 0) {
            $partialHomeAways = $this->getHomeAwaysFromPartial(array_shift($partials), $nrOfHomeAways);
            $homeAways = array_merge($homeAways, $partialHomeAways);
            $nrOfHomeAways -= count($partialHomeAways);
        }
        return $homeAways;
    }

    /**
     * @param int $nrOfHomeAways
     * @return list<AgainstPartial>
     */
    protected function getPartialsHelper(int $nrOfHomeAways): array {
        $nrOfHomeAwaysOnePartial = $this->sportVariant->getNrOfGamesOnePartial($this->poule->getPlaces()->count());
        $maxNrOfPartials = (int)ceil($nrOfHomeAways / $nrOfHomeAwaysOnePartial);

        $nrOfGamePlaces = $this->sportVariant->getNrOfGamePlaces();
        $placeNrs = $this->getPlaceNrs($this->poule->getPlaceList());
        if (count($placeNrs) > $nrOfGamePlaces) {
            array_pop($placeNrs);
        }

        $maxNrOfIncrements = $nrOfHomeAwaysOnePartial;
        $partials = [];
        /** @var \Iterator<string, list<int>> $it */
        $it = new CombinationIt($placeNrs, $nrOfGamePlaces);
        while ($it->valid() && count($partials) <= $maxNrOfPartials) {
            $uniqueCombinations = $this->getUniqueCombinations($it->current());
            foreach ($uniqueCombinations as $uniqueCombination) {
                $partial = new AgainstPartial($this->poule, $this->sportVariant, $uniqueCombination, $maxNrOfIncrements);
                if( count($partials) > $maxNrOfPartials ) {
                    break;
                }
                array_push($partials, $partial);
            }
            $it->next();
        }
        return $partials;
    }

    /**
     * @param list<int> $placeNrs
     * @return list<list<int>>
     */
    protected function getUniqueCombinations(array $placeNrs): array
    {
        return [
            $placeNrs,
            [$placeNrs[0], $placeNrs[2], $placeNrs[1], $placeNrs[3]],
            [$placeNrs[0], $placeNrs[3], $placeNrs[1], $placeNrs[2]]
        ];
    }
}
