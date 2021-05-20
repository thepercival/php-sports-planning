<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\AgainstSerie;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\AgainstPartial;
use SportsPlanning\Combinations\AgainstSerie;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use Voetbal\Planning\GameRound;
use Voetbal\PoulePlace;
use Voetbal\PoulePlace\Combination as PoulePlaceCombination;

class OneVersusOne extends AgainstSerie
{
    public function __construct(Poule $poule, AgainstSportVariant $sportVariant)
    {
        parent::__construct($poule, $sportVariant);

        $maxNrOfIncrements = $sportVariant->getNrOfGamesOnePartial($poule->getPlaces()->count());
        // $nrOfHomeAwaysOneH2H = $sportVariant->getNrOfGamesOneH2H($poule->getPlaces()->count());
        $nrOfPartials = $sportVariant->getNrOfPartialsOneSerie($poule->getPlaces()->count());
        $placeNrs = $this->getPlaceNrs($poule->getPlaceList());
        /** @var \Iterator<string, list<int>> $it */
        $it = new CombinationIt($placeNrs, $this->sportVariant->getNrOfGamePlaces());
        while ($nrOfPartials-- > 0) {
            $homeAwayPlaceNrs =  $it->current();
            if (!$it->valid()) {
                $homeAwayPlaceNrs = array_reverse($homeAwayPlaceNrs);
            }
            $partial = new AgainstPartial($poule, $sportVariant, $homeAwayPlaceNrs, $maxNrOfIncrements);
            array_push($this->partials, $partial);
            $it->next();
        }
    }


//    /**
//     * @param int $h2hNr
//     * @return list<AgainstHomeAway>
//     */
//    public function getHomeAways(int $h2hNr): array
//    {
//        $homeAways = [];
//
//        /** @var \Iterator<string, list<Place>> $it */
//        $it = new CombinationIt($this->poule->getPlaceList(), $this->sportVariant->getNrOfGamePlaces());
//        while ($it->valid()) {
//            $places = $it->current();
//            if ($h2hNr % 2 === 0) {
//                $places = array_reverse($places);
//            }
//            array_push($homeAways, new AgainstHomeAway(
//                new PlaceCombination([$places[0]]),
//                new PlaceCombination([$places[1]])
//            ));
//            $it->next();
//        }
//        return $homeAways;
//    }

//    /**
//     * @param list<int> $places
//     * @return list<list<int>>
//     */
//    protected function generateRRSchedule(array $placeNrs): array
//    {
//        $nrOfPlaces = count($placeNrs);
//
//        $nrOfHomeGames = [];
//        foreach ($placeNrs as $placeNr) {
//            $nrOfHomeGames[$placeNr] = 0;
//        }
//
//        // add a placeholder if the count is odd
//        if ($nrOfPlaces % 2 === 1) {
//            $placeNrs[] = null;
//            $nrOfPlaces++;
//        }
//
//        // calculate the number of sets and matches per set
//        $nrOfRoundNumbers = $nrOfPlaces - 1;
//        $nrOfMatches = $nrOfPlaces / 2;
//        $gameRounds = [];
//
//        // generate each set
//        for ($roundNumber = 1; $roundNumber <= $nrOfRoundNumbers; $roundNumber++) {
//            $evenRoundNumber = ($roundNumber % 2) === 0;
//            $combinations = [];
//            // break the list in half
//            $halves = array_chunk($placeNrs, $nrOfMatches);
//            $firstHalf = array_shift($halves);
//            // reverse the order of one half
//            $secondHalf = array_reverse(array_shift($halves));
//            // generate each match in the set
//            for ($i = 0; $i < $nrOfMatches; $i++) {
//                if ($firstHalf[$i] === null || $secondHalf[$i] === null) {
//                    continue;
//                }
//                $homePlace = $evenRoundNumber ? $secondHalf[$i] : $firstHalf[$i];
//                $awayPlace = $evenRoundNumber ? $firstHalf[$i] : $secondHalf[$i];
//                if ($nrOfHomeGames[$awayPlace->getNumber()] < $nrOfHomeGames[$homePlace->getNumber()]) {
//                    $tmpPlace = $homePlace;
//                    $homePlace = $awayPlace;
//                    $awayPlace = $tmpPlace;
//                }
//                $combinations[] = new PlaceCombination([$homePlace], [$awayPlace]);
//                $nrOfHomeGames[$homePlace->getNumber()]++;
//            }
//            $gameRounds[] = new GameRound($roundNumber, $combinations);
//            // remove the first player and store
//            $first = array_shift($places);
//            // move the second player to the end of the list
//            $places[] = array_shift($places);
//            // place the first item back in the first position
//            array_unshift($places, $first);
//        }
//        return $gameRounds;
//    }
}
