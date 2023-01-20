<?php

namespace SportsPlanning\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;

class AgainstDifferenceManager
{
    /**
     * @var array<int, AmountRange>
     */
    private array $amountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $againstAmountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $withAmountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $homeAmountRange = [];

    // private bool|null $canVariantAgainstBeEquallyAssigned = null;
    // private bool|null $canVariantWithBeEquallyAssigned = null;

    /**
     * @param Poule $poule
     * @param non-empty-array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @param int $allowedMargin
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Poule $poule,
        array $againstVariantMap,
        protected int $allowedMargin,
        protected LoggerInterface $logger)
    {
        $this->initAmountMaps($poule, $againstVariantMap);
    }

    /**
     * @param Poule $poule
     * @param non-empty-array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @return void
     */
    private function initAmountMaps(Poule $poule, array $againstVariantMap): void
    {
        $againstGppMap = $this->getAgainstGppMap($againstVariantMap);
        $this->initAmountMap($poule, $againstGppMap);
        $this->initAgainstAmountMap($poule, $againstGppMap);
        $this->initWithAmountMap($poule, $againstGppMap);
        $this->initHomeAmountMap($poule, $againstVariantMap);
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initAmountMap(Poule $poule, array $againstGppMap): void
    {
        $nrOfAmountCumulative = 0;

        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();

            $nrOfAmountSport = $againstGpp->getNrOfGamePlaces() * $nrOfSportGames;
            $nrOfAmountCumulative += $nrOfAmountSport;

            $allowedAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfAmountCumulative,
                $againstGppWithPoule->getNrOfPlaces()
            );

            $minNrAllowedToAssignToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfAmountCumulative,
                $againstGppWithPoule->getNrOfPlaces()
            );
            $maxNrAllowedToAssignToMaximumCum = $againstGppWithPoule->getNrOfPlaces() - $minNrAllowedToAssignToMinimumCum;

            $allowedMaxSport = $allowedAmountCum;
            $allowedMinSport = $allowedAmountCum;
            if( $minNrAllowedToAssignToMinimumCum > 0 ) {
               $allowedMinSport--;
            }

            if( $allowedMinSport < 0 ) {
                $allowedMinSport = 0;
                $minNrAllowedToAssignToMinimumCum = 0;
            }

            $this->amountRange[$sportNr] = new AmountRange(
                new Amount( $allowedMinSport, $minNrAllowedToAssignToMinimumCum),
                new Amount( $allowedMaxSport, $maxNrAllowedToAssignToMaximumCum)
            );
        }
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initAgainstAmountMap(Poule $poule, array $againstGppMap): void
    {
        $nrOfAgainstCombinationsCumulative = 0;

        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();

            $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
            $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;

            $allowedAgainstAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
            );

            $minNrOfAgainstAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
            );
            $maxNrOfAgainstAllowedToAssignedToMaximumCum = $againstGppWithPoule->getNrOfPossibleAgainstCombinations() - $minNrOfAgainstAllowedToAssignedToMinimumCum;

            $allowedAgainstMaxSport = $allowedAgainstAmountCum + $this->allowedMargin;
            $allowedAgainstMinSport = $allowedAgainstAmountCum - $this->allowedMargin;
            if( $this->allowedMargin > 0 ) {
                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
            } else if( $minNrOfAgainstAllowedToAssignedToMinimumCum > 0 ) {
                $allowedAgainstMinSport--;
            }

            if( $allowedAgainstMinSport < 0 ) {
                $allowedAgainstMinSport = 0;
                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
            }

            $this->againstAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedAgainstMinSport, $minNrOfAgainstAllowedToAssignedToMinimumCum),
                new Amount( $allowedAgainstMaxSport, $maxNrOfAgainstAllowedToAssignedToMaximumCum)
            );
        }
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initWithAmountMap(Poule $poule, array $againstGppMap): void
    {
      //  $totalNrOfGames = $this->getTotalNrOfGames($poule, $againstGppMap);

        // $allowedMarginCumulative = 0;
        $nrOfWithCombinationsCumulative = 0;

//        $counter = 0;
        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();
            //$lastSportVariant = ++$counter === count($againstGppMap);

//            if ($this->allowedMargin === 0) { // alle 1 en de laatste 0
//                $allowedMarginCumulative = $lastSportVariant ? 0 : 1;
//                // @TODO CDK
//                //            if( $lastSportVariant && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                $allowedMarginCumulative++;
////            }
//
////                if( $allowedMarginCumulative === 0 && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                    $allowedMarginCumulative = 1;
////                }
//            } else {
//                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $totalNrOfGames * $this->allowedMargin);
//                $allowedMarginCumulative += $allowedAgainstMarginSport;
//            }

            if( $againstGppWithPoule->getSportVariant()->hasMultipleSidePlaces()) {

                if( $againstGppWithPoule->getSportVariant()->getNrOfHomePlaces() > 2
                    || $againstGppWithPoule->getSportVariant()->getNrOfAwayPlaces() > 2) {
                    throw new \Exception('Only 2 NrOfWithPlaces ALLOWED');
                }

                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;

                $allowedWithAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );

                $minNrOfWithAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );
                $maxNrOfWithAllowedToAssignedToMaximumCum = $againstGppWithPoule->getNrOfPossibleWithCombinations() - $minNrOfWithAllowedToAssignedToMinimumCum;
            } else {
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
                $maxNrOfWithAllowedToAssignedToMaximumCum = 0;
                $allowedWithAmountCum = 0;
            }

            $allowedWithMaxSport = $allowedWithAmountCum + $this->allowedMargin;
            $allowedWithMinSport = $allowedWithAmountCum - $this->allowedMargin;
            if( $this->allowedMargin > 0 ) {
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
            } else if( $minNrOfWithAllowedToAssignedToMinimumCum > 0 ) {
                $allowedWithMinSport--;
            }

            if( $allowedWithMinSport < 0 ) {
                $allowedWithMinSport = 0;
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
            }

            $this->withAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedWithMinSport, $minNrOfWithAllowedToAssignedToMinimumCum),
                new Amount( $allowedWithMaxSport, $maxNrOfWithAllowedToAssignedToMaximumCum)
            );
        }
    }

    /**
     * @param Poule $poule
     * @param non-empty-array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @return void
     */
    private function initHomeAmountMap(Poule $poule, array $againstVariantMap): void
    {
        // $totalNrOfGames = $this->getTotalNrOfGames($poule, $againstVariantMap);

        // $allowedMarginCumulative = 0;
        $nrOfHomeCombinationsCumulative = 0;

//        $counter = 0;
        foreach ($againstVariantMap as $sportNr => $againstVariant) {
            $againstWithPoule = $this->getVariantWithPoule($poule, $againstVariant);
            $nrOfSportGames = $againstWithPoule->getTotalNrOfGames();
           // $lastSportVariant = ++$counter === count($againstVariantMap);

//            if ($this->allowedMargin === 0) { // alle 1 en de laatste 0
//                $allowedMarginCumulative = $lastSportVariant ? 0 : 1;
//                // @TODO CDK
//                //            if( $lastSportVariant && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                $allowedMarginCumulative++;
////            }
//
////                if( $allowedMarginCumulative === 0 && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                    $allowedMarginCumulative = 1;
////                }
//            } else {
//                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $totalNrOfGames * $this->allowedMargin);
//                $allowedMarginCumulative += $allowedAgainstMarginSport;
//            }

            // $nrOfHomeCombinations = 1;
            $nrOfHomeCombinationsSport = $againstVariant->getNrOfWithCombinationsPerGame(Side::Home) * $nrOfSportGames;
            $nrOfHomeCombinationsCumulative += $nrOfHomeCombinationsSport;
            $allowedHomeAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfHomeCombinationsCumulative,
                $againstWithPoule->getNrOfPossibleWithCombinations(Side::Home)
            );

            $minNrOfHomeAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfHomeCombinationsCumulative,
                $againstWithPoule->getNrOfPossibleWithCombinations(Side::Home)
            );
            $maxNrOfHomeAllowedToAssignedToMinimumCum = $againstWithPoule->getNrOfPossibleWithCombinations(Side::Home) - $minNrOfHomeAllowedToAssignedToMinimumCum;

            $allowedHomeMaxSport = $allowedHomeAmountCum + $this->allowedMargin;
            $allowedHomeMinSport = $allowedHomeAmountCum - $this->allowedMargin;
            if( $this->allowedMargin > 0 ) {
                $minNrOfHomeAllowedToAssignedToMinimumCum = 0;
            }  else if( $minNrOfHomeAllowedToAssignedToMinimumCum > 0 ) {
                $allowedHomeMinSport--;
            }

            if( $allowedHomeMinSport < 0 ) {
                $allowedHomeMinSport = 0;
                $minNrOfHomeAllowedToAssignedToMinimumCum = 0;
            }

            $this->homeAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedHomeMinSport, $minNrOfHomeAllowedToAssignedToMinimumCum),
                new Amount( $allowedHomeMaxSport, $maxNrOfHomeAllowedToAssignedToMinimumCum)
            );
        }
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @return int
     */
    private function getTotalNrOfGames(Poule $poule, array $againstVariantMap): int {
        $nrOfGames = 0;
        foreach ($againstVariantMap as $againstVariant) {
            if( $againstVariant instanceof AgainstGpp) {
                $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstVariant);
                $nrOfGames += $againstGppWithPoule->getTotalNrOfGames();
            } else {
                $againstH2hWithPoule = new AgainstH2hWithPoule($poule, $againstVariant);
                $nrOfGames += $againstH2hWithPoule->getTotalNrOfGames();
            }
        }
        return $nrOfGames;
    }

    /**
     * @param array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @return array<int, AgainstGpp>
     */
    protected function getAgainstGppMap(array $againstVariantMap): array
    {
        $map = [];
        foreach( $againstVariantMap as $sportNr => $againstVariant) {
            if( $againstVariant instanceof AgainstGpp) {
                $map[$sportNr] = $againstVariant;
            }
        }
        return $map;
    }

    protected function getVariantWithPoule(
        Poule $poule,
        AgainstH2h|AgainstGpp $againstVariant): AgainstH2hWithPoule|AgainstGppWithPoule {
        if( $againstVariant instanceof AgainstGpp) {
            return new AgainstGppWithPoule($poule, $againstVariant);
        }
        return new AgainstH2hWithPoule($poule, $againstVariant);
    }


//    /**
//     * @param Poule $poule
//     * @param list<AgainstGpp> $againstGppVariants
//     * @return bool
//     */
//    private function canVariantAgainstBeEquallyAssigned(Poule $poule, array $againstGppVariants): bool {
//        if( $this->canVariantAgainstBeEquallyAssigned === null ) {
//            $calculator = new EquallyAssignCalculator();
//            $this->canVariantAgainstBeEquallyAssigned = $calculator->assignAgainstSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
//        }
//        return $this->canVariantAgainstBeEquallyAssigned;
//    }

//    /**
//     * @param Poule $poule
//     * @param list<AgainstGpp> $againstGppVariants
//     * @return bool
//     */
//    private function canVariantWithBeEquallyAssigned(Poule $poule, array $againstGppVariants): bool {
//        if( $this->canVariantWithBeEquallyAssigned === null ) {
//            $calculator = new EquallyAssignCalculator();
//            $this->canVariantWithBeEquallyAssigned = $calculator->assignWithSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
//        }
//        return $this->canVariantWithBeEquallyAssigned;
//    }

    public function getAmountRange(int $sportNr): AmountRange {
        return $this->amountRange[$sportNr];
    }

    public function getAgainstRange(int $sportNr): AmountRange {
        return $this->againstAmountRange[$sportNr];
    }

    public function getWithRange(int $sportNr): AmountRange {
        return $this->withAmountRange[$sportNr];
    }

    public function getHomeRange(int $sportNr): AmountRange {
        return $this->homeAmountRange[$sportNr];
    }
}