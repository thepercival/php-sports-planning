<?php

namespace SportsPlanning\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Poule;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;

class AgainstGppDifferenceManager
{
    /**
     * @var array<int, AmountRange>
     */
    private array $againstAmountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $withAmountRange = [];

    // private bool|null $canVariantAgainstBeEquallyAssigned = null;
    // private bool|null $canVariantWithBeEquallyAssigned = null;

    /**
     * @param Poule $poule
     * @param non-empty-array<int, AgainstGpp> $againstGppVariantMap
     * @param int $allowedMargin
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Poule $poule,
        array $againstGppVariantMap,
        protected int $allowedMargin,
        protected LoggerInterface $logger)
    {
        $this->initAmountMaps($poule, $againstGppVariantMap);
    }

    /**
     * @param Poule $poule
     * @param non-empty-array<int, AgainstGpp> $againstGppVariantMap
     * @return void
     */
    private function initAmountMaps(Poule $poule, array $againstGppVariantMap): void
    {
        $nrOfGames = 0;
        foreach ($againstGppVariantMap as $againstGpp) {
            $againstGppsWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfGames += $againstGppsWithPoule->getTotalNrOfGames();
        }

        $allowedMarginCumulative = 0;
        $nrOfAgainstCombinationsCumulative = 0;
        $nrOfWithCombinationsCumulative = 0;
        // $nrOfSportVariants = count($againstGppVariantMap);

        $counter = 0;
        foreach ($againstGppVariantMap as $sportNr => $againstGpp) {
            $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();
            $lastSportVariant = ++$counter === count($againstGppVariantMap);

            if ($this->allowedMargin === 0) { // alle 1 en de laatste 0
                $allowedMarginCumulative = $lastSportVariant ? 0 : 1;
                // @TODO CDK
                //            if( $lastSportVariant && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
//                $allowedMarginCumulative++;
//            }

//                if( $allowedMarginCumulative === 0 && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
//                    $allowedMarginCumulative = 1;
//                }
            } else {
                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $nrOfGames * $this->allowedMargin);
                $allowedMarginCumulative += $allowedAgainstMarginSport;
            }

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

            $allowedAgainstMaxSport = $allowedAgainstAmountCum + (int)ceil($allowedMarginCumulative / 2);
            $allowedAgainstMinSport = $allowedAgainstAmountCum - (int)ceil($allowedMarginCumulative / 2);
            if( $minNrOfAgainstAllowedToAssignedToMinimumCum > 0 ) {
                $allowedAgainstMinSport--;
            }

            $allowedWithMaxSport = $allowedWithAmountCum + (int)ceil($allowedMarginCumulative / 2);
            $allowedWithMinSport = $allowedWithAmountCum - (int)ceil($allowedMarginCumulative / 2);
            if( $minNrOfWithAllowedToAssignedToMinimumCum > 0 ) {
                $allowedWithMinSport--;
            }
            if( $allowedAgainstMinSport < 0 ) {
                $allowedAgainstMinSport = 0;
                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
            }
            if( $allowedWithMinSport < 0 ) {
                $allowedWithMinSport = 0;
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
            }

             $this->againstAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedAgainstMinSport, $minNrOfAgainstAllowedToAssignedToMinimumCum),
                new Amount( $allowedAgainstMaxSport, $maxNrOfAgainstAllowedToAssignedToMaximumCum)
            );
            $this->withAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedWithMinSport, $minNrOfWithAllowedToAssignedToMinimumCum),
                new Amount( $allowedWithMaxSport, $maxNrOfWithAllowedToAssignedToMaximumCum)
            );
        }
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

    public function getAgainstRange(int $sportNr): AmountRange {
        return $this->againstAmountRange[$sportNr];
    }

    public function getWithRange(int $sportNr): AmountRange {
        return $this->withAmountRange[$sportNr];
    }
}