<?php

namespace SportsPlanning\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsHelpers\SportRange;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;

class AgainstGppDifferenceManager
{
    /**
     * @var array<int, AgainstGppDifference>
     */
    private array $differenceMap = [];
    private bool|null $canVariantAgainstBeEquallyAssigned = null;
    private bool|null $canVariantWithBeEquallyAssigned = null;

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppVariantMap
     * @param int $allowedMargin
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Poule $poule,
        array $againstGppVariantMap,
        protected int $allowedMargin,
        protected LoggerInterface $logger)
    {
        $this->initDifferenceMap($poule, $againstGppVariantMap);
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppVariantMap
     * @return void
     */
    private function initDifferenceMap(Poule $poule, array $againstGppVariantMap): void
    {
        $nrOfGames = 0;
        foreach ($againstGppVariantMap as $againstGpp) {
            $againstGppsWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfGames += $againstGppsWithPoule->getTotalNrOfGames();
        }

        $allowedMarginCumulative = 0;
        $nrOfAgainstCombinationsCumulative = 0;
        $nrOfWithCombinationsCumulative = 0;
        $nrOfSportVariants = count($againstGppVariantMap);

        $counter = 0;
        // $nrOfPossibleWithCombinations = 0;
        foreach ($againstGppVariantMap as $sportNr => $againstGpp) {
            // $againstGppVariantsCumulative[] = $againstGpp;
            $againstGppWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();
            $lastSportVariant = ++$counter === count($againstGppVariantMap);

            // $nrOfGamesCumulative += $againstGppsWithPoule->getTotalNrOfGames();
            //$allowedAgainstDeficitCumulative += $againstGppsWithPoule->getDeficit();

            if ($this->allowedMargin === 0) { // alle 1 en de laatste 0
                // $allowedAgainstSportDiff = $againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ? 0: 1;

                $allowedMarginCumulative = $lastSportVariant ? 0 : 1;
                if( $allowedMarginCumulative === 0 && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
                    $allowedMarginCumulative = 1;
                }
            } else {
                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $nrOfGames * $this->allowedMargin);
                $allowedMarginCumulative += $allowedAgainstMarginSport;
            }

            $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
            $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;
            $minNrOfAgainstAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
            );
            $maxNrOfAgainstAllowedToAssignedToMaximumCum = $againstGppWithPoule->getNrOfPossibleAgainstCombinations() - $minNrOfAgainstAllowedToAssignedToMinimumCum;

            $allowedAgainstAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
            );

            if( $againstGppWithPoule->getSportVariant()->hasMultipleSidePlaces()) {

                if( $againstGppWithPoule->getSportVariant()->getNrOfHomePlaces() > 2
                    || $againstGppWithPoule->getSportVariant()->getNrOfAwayPlaces() > 2) {
                    throw new \Exception('Only 2 NrOfWithPlaces ALLOWED');
                }

                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;
                $minNrOfWithAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );
                $maxNrOfWithAllowedToAssignedToMaximumCum = $againstGppWithPoule->getNrOfPossibleWithCombinations() - $minNrOfWithAllowedToAssignedToMinimumCum;
                $allowedWithAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );
            } else {
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
                $maxNrOfWithAllowedToAssignedToMaximumCum = 0;
                $allowedWithAmountCum = 0;
            }

            $allowedAgainstAmountCum += (int)ceil($this->allowedMargin / 2);
            $allowedWithAmountCum += (int)ceil($this->allowedMargin / 2);
//            if( $this->allowedMargin > 0) {
//                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
//                $maxNrOfAgainstAllowedToAssignedToMaximumCum = 0;
//                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
//                $maxNrOfWithAllowedToAssignedToMaximumCum = 0;
//            }

            $this->differenceMap[$sportNr] = new AgainstGppDifference(
                $this->allowedMargin,
                $allowedMarginCumulative,
                $minNrOfAgainstAllowedToAssignedToMinimumCum,
                $maxNrOfAgainstAllowedToAssignedToMaximumCum,
                $minNrOfWithAllowedToAssignedToMinimumCum,
                $maxNrOfWithAllowedToAssignedToMaximumCum,
                new SportRange( $allowedAgainstAmountCum - $allowedMarginCumulative, $allowedAgainstAmountCum),
                new SportRange( $allowedWithAmountCum - $allowedMarginCumulative, $allowedWithAmountCum),
                $nrOfSportVariants === $counter
            );
        }
    }

    /**
     * @param Poule $poule
     * @param list<AgainstGpp> $againstGppVariants
     * @return bool
     */
    private function canVariantAgainstBeEquallyAssigned(Poule $poule, array $againstGppVariants): bool {
        if( $this->canVariantAgainstBeEquallyAssigned === null ) {
            $calculator = new EquallyAssignCalculator();
            $this->canVariantAgainstBeEquallyAssigned = $calculator->assignAgainstSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
        }
        return $this->canVariantAgainstBeEquallyAssigned;
    }

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

    public function getDifference(int $sportNr): AgainstGppDifference {
        return $this->differenceMap[$sportNr];
    }
}