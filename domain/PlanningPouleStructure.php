<?php

namespace SportsPlanning;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Exceptions\SelfRefereeIncompatibleWithPouleStructureException;
use SportsPlanning\Exceptions\SportsIncompatibleWithPouleStructureException;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final readonly class PlanningPouleStructure
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param RefereeInfo|null $refereeInfo
     * @throws \Exception
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportsWithNrOfFieldsAndNrOfCycles,
        public RefereeInfo|null $refereeInfo )
    {
        $selfReferee = $refereeInfo?->selfRefereeInfo?->selfReferee;
        $sports = $this->createSports();
        if( $selfReferee !== null
            && !$pouleStructure->isCompatibleWithSportsAndSelfReferee($sports, $selfReferee) ) {
            throw new SelfRefereeIncompatibleWithPouleStructureException($pouleStructure, $sports, $selfReferee);
        }
        if( !$pouleStructure->isCompatibleWithSports( $sports ) ) {
            throw new SportsIncompatibleWithPouleStructureException($pouleStructure, $sports);
        }
    }

    // per sport kijken wat de maxNrOfGamesPerBatch is
    // van alle sporten pak je de laagste maxNrOfGamesPerBatch
    // dit krijg je dan terug voor de hele poulestructure.
    // lijkt mij niet goed.
    // minimaal 1 lijkt me.

    public function getMinNrOfGamesPerBatch(): int {
        return 1;
//        array_map(
//            function (SportVariantWithFields $sportVariantWithFields): int {
//                return $this->getMax($sportVariantWithFields);
//            },
//            $this->sportVariantsWithFields
//        );
    }



    public function calculateMaxNrOfGamesPerBatch(): int {

        $sortedSportsWithNrOfFieldsAndNrOfCycles = $this->sortSportsByNrOfGamePlaces();

        $nrOfBatchGames = 0;
        $poules = array_reverse($this->pouleStructure->toArray());
        $nrOfReferees = $this->refereeInfo?->nrOfReferees ?? 0;
        $selfReferee = $this->refereeInfo?->selfRefereeInfo->selfReferee;
        $doSelfRefereeOtherPouleCheck = $selfReferee === SelfReferee::OtherPoules;
        $doRefereeCheck = $nrOfReferees > 0;
        $sportWithNrOfFieldsAndNrOfCycles = array_shift($sortedSportsWithNrOfFieldsAndNrOfCycles);
        // $singleSportVariantWithFields = count($sortedSportVariantsWithFields) === 0 ? $sportVariantWithFields : null;
        $currentPouleNrOfPlaces = $this->substractPlaces($poules);
        $nrOfPlaces = $currentPouleNrOfPlaces;

        while ($nrOfPlaces > 0 && $sportWithNrOfFieldsAndNrOfCycles !== null &&  (!$doRefereeCheck || $nrOfReferees > 0)) {
            $nrOfFields = $sportWithNrOfFieldsAndNrOfCycles->nrOfFields;
            // be
            $nrOfGamePlaces = $sportWithNrOfFieldsAndNrOfCycles->sport->getNrOfGamePlaces() ?? $currentPouleNrOfPlaces;
            // SelfRef::SamePoule
            $nrOfGamePlaces += ($selfReferee === SelfReferee::SamePoule ? 1 : 0);

            // BIJ SAMEPOULE, METEEN HET AANTAL ERAF HALEN
            // BIJ OTHERPOULES, KIJKEN ALS DE OVERIGE

            while (
                $nrOfPlaces >= $nrOfGamePlaces
                && $nrOfFields-- > 0
                && (!$doRefereeCheck || $nrOfReferees-- > 0)
                && (!$doSelfRefereeOtherPouleCheck || $this->enoughSelfRefereesLeft(array_sum($poules) + $nrOfPlaces, $nrOfGamePlaces, $nrOfBatchGames) )
            ) {
                $nrOfPlaces -= $nrOfGamePlaces;
                $nrOfBatchGames++;
                if ($nrOfPlaces < $nrOfGamePlaces) {
                    $currentPouleNrOfPlaces = $this->substractPlaces($poules); // NEW POULE
                    $nrOfPlaces += $currentPouleNrOfPlaces;
                }
            }
            $sportWithNrOfFieldsAndNrOfCycles = array_shift($sortedSportsWithNrOfFieldsAndNrOfCycles);
        }
        if ($nrOfBatchGames === 0) {
            return 1;
        }

//        if ($pouleStructure->isBalanced() && $singleSportVariantWithFields !== null) {
//            $maxNrOfBatchGames = $planningPouleStructure->getMaxNrOfGamesPerBatchForSportVa(
//                $singleSportVariantWithFields
//            // $singleSportVariantWithFields->getSportVariant()  WAS THIS LINE
//            );
//            return min( $maxNrOfBatchGames, $nrOfBatchGames );
//        }

        return $nrOfBatchGames;
    }




    protected function enoughSelfRefereesLeft(int $nrOfPlacesToGo, int $nrOfGamePlaces, int $nrOfBatchGames): bool {
        $selfRefereeInfo = $this->refereeInfo?->selfRefereeInfo;
        if( $selfRefereeInfo === null ) {
            return true;
        }
        $newNrOfBatchGames = $nrOfBatchGames + 1;
        $newNrOfPlacesToGo = $nrOfPlacesToGo - $nrOfGamePlaces;
        $newNrOfSelfRefereePlacesUsed = (int)ceil($newNrOfBatchGames / $selfRefereeInfo->nrOfSimSelfRefs);

        return $newNrOfPlacesToGo >= $newNrOfSelfRefereePlacesUsed;
    }


    public function calculateMaxNrOfGamesInARow(): int
    {
        $pouleStructure = $this->pouleStructure;
        $biggestPouleNrOfPlaces = $pouleStructure->getBiggestPoule();
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        if( $nrOfPlaces === null) {
            throw new \Exception('unknown nrOfPoulesByNrOfPlaces');
        }
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch();

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            $nrOfPlacesPouleStructure = new PlanningPouleStructure(
                new PouleStructure([$nrOfPlaces]),
                $this->sportsWithNrOfFieldsAndNrOfCycles,
                $this->refereeInfo );

            $totalNrOfGamesPerPlace = $nrOfPlacesPouleStructure->calculateNrOfGames();
            if ($totalNrOfGamesPerPlace > ($biggestPouleNrOfPlaces - 1)) {
                $totalNrOfGamesPerPlace = $biggestPouleNrOfPlaces - 1;
            }
            return $totalNrOfGamesPerPlace;
        }
        $maxNrOfGamesInARow = (int)ceil($nrOfPlaces / $nrOfRestPlaces);
        if ($maxNrOfGamesInARow > ($biggestPouleNrOfPlaces - 1)) {
            $maxNrOfGamesInARow = $biggestPouleNrOfPlaces - 1;
        }
        return $maxNrOfGamesInARow;
    }

    /**
     * @return list<SportWithNrOfFields>
     */
    public function createSportsWithNrOfFields(): array
    {
        return array_map(
            function (SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): SportWithNrOfFields {
                return $sportWithNrOfFieldsAndNrOfCycles->createSportWithNrOfFields();
            } , $this->sportsWithNrOfFieldsAndNrOfCycles);
    }

    /**
     * @return list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport>
     */
    public function createSports(): array
    {
        return array_map(
            function (SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport {
                return $sportWithNrOfFieldsAndNrOfCycles->sport;
            } , $this->sportsWithNrOfFieldsAndNrOfCycles);
    }

    /**
     * @param list<int> $poules
     * @return int
     */
    protected function substractPlaces(array &$poules): int
    {
        return array_shift($poules) ?? 0;
    }

    /**
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     */
    protected function getMaxNrOfPlacesPerBatch(): int
    {
        $sortedSportsWithNrofFieldsAndNrOfCycles = $this->sortSportsByNrOfGamePlaces();
        $selfReferee = $this->refereeInfo?->selfRefereeInfo?->selfReferee !== null;
        $nrOfBatchPlaces = 0;
        $nrOfPlaces = $this->pouleStructure->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sortedSportsWithNrofFieldsAndNrOfCycles) > 0) {
            $sportWithNrofFieldsAndNrOfCycles = array_shift($sortedSportsWithNrofFieldsAndNrOfCycles);
            $sportNrOfGamePlaces = $sportWithNrofFieldsAndNrOfCycles->sport->getNrOfGamePlaces() ?? $this->pouleStructure->getBiggestPoule();
            $sportNrOfGamePlaces = $sportNrOfGamePlaces + ($selfReferee ? 1 : 0);
            $nrOfFields = $sportWithNrofFieldsAndNrOfCycles->nrOfFields;
            while ($nrOfPlaces > 0 && $nrOfFields-- > 0) {
                $nrOfGamePlaces = $sportNrOfGamePlaces + ($selfReferee ? 1 : 0);
                $nrOfPlaces -= $nrOfGamePlaces;
                $nrOfBatchPlaces += $nrOfGamePlaces;
            }
        }

        if ($nrOfPlaces < 0) {
            $nrOfBatchPlaces += $nrOfPlaces;
        }
        return $nrOfBatchPlaces === 0 ? 1 : $nrOfBatchPlaces;
    }

    /**
     * @return list<SportWithNrOfFieldsAndNrOfCycles>
     */
    protected function sortSportsByNrOfGamePlaces(): array {
        $sportsWithNrOfFieldsAndNrOfCycles = array_slice( $this->sportsWithNrOfFieldsAndNrOfCycles, 0);
        $nrOfPlaces = $this->pouleStructure->getBiggestPoule();

        uasort(
            $sportsWithNrOfFieldsAndNrOfCycles,
            function (SportWithNrOfFieldsAndNrOfCycles $a,SportWithNrOfFieldsAndNrOfCycles $b)
                use($nrOfPlaces): int {
                    $nrOfGamePlacesA = $a->sport->getNrOfGamePlaces() ?? $nrOfPlaces;
                    $nrOfGamePlacesB = $b->sport->getNrOfGamePlaces() ?? $nrOfPlaces;
                    return $nrOfGamePlacesA < $nrOfGamePlacesB ? -1 : 1;
            }
        );
        return array_values( $sportsWithNrOfFieldsAndNrOfCycles );
    }

    // MOVE TO OTHER
//    /**
//     * @param non-empty-list<SportVariantWithFields> $sportVariantsWithFields
//     * @return int
//     */
//    public function getLowestNrOfMaxNrOfGamesPerBatch(array $sportVariantsWithFields): int {
//        $lowestNrOfMaxNrOfGamesPerBatch = min(
//            array_map(
//                function (SportVariantWithFields $sportVariantWithFields): int {
//                    $maxNrOfGamesPerBatch = $this->getMaxNrOfGamesPerBatch($sportVariantWithFields);
//                    return min($maxNrOfGamesPerBatch, $sportVariantWithFields->getNrOfFields());
//                },
//                $sportVariantsWithFields
//            )
//        );
//        return $lowestNrOfMaxNrOfGamesPerBatch;
//    }

    // DIT GELDT BLIJKBAAR VOOR ALLEEN DE PLACES, REFS AND FIELDS WORDT NIET NAAR GEKEKEN

//    public function getMaxNrOfGamesPerBatchWithoutFields(
//        SportVariantWithFields|Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant ): int {
//
//        if( $this->isBalanced() === false ) {
//            throw new \Exception('ONLY FOR BALLANCED');
//        }
//        // no fields
//        $variantWithPoule = (new VariantCreator())->createWithPoule($this->getBiggestPoule(), $sportVariant);
//        $nrOfGamePlaces = $variantWithPoule->getNrOfGamePlaces();
//
//        $countSelfReferee = $this->refereeInfo->selfReferee === SelfReferee::SamePoule;
//        $nrOfGamePlacesWithSelfRef = $nrOfGamePlaces + ($countSelfReferee ? 1 : 0 );
//        $maxNrOfGamesPerBatchPerPoule = (int)floor($this->getBiggestPoule() / $nrOfGamePlacesWithSelfRef);
//        if ($maxNrOfGamesPerBatchPerPoule === 0) {
//            $maxNrOfGamesPerBatchPerPoule = 1;
//        }
//        // pak maximale aantal wedstrijden per poule tegelijk * het aantal poules
//        return $maxNrOfGamesPerBatchPerPoule * $this->getNrOfPoules();
//    }

//    public function getMaxNrOfGamesPerPlaceRange(): SportRange
//    {
//        return new SportRange(
//            $this->getTotalNrOfGamesPerPlaceForNrOfPlaces( $this->pouleStructure->getSmallestPoule() ),
//            $this->getTotalNrOfGamesPerPlaceForNrOfPlaces( $this->pouleStructure->getBiggestPoule() )
//        );
//    }

    public function calculateNrOfGames(): int
    {
        return array_sum(
            array_map( function(int $nrOfPlaces): int {

                return array_sum(
                    array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles) use($nrOfPlaces): int {
                        $sportWithNrOfPlaces = $sportWithNrOfFieldsAndNrOfCycles->createSportWithNrOfPlaces($nrOfPlaces);

//                        |AgainstOneVsOneWithNrOfPlaces|AgainstOneVsTwoWithNrOfPlaces|AgainstTwoVsTwoWithNrOfPlaces

//                        if( $sportWithNrOfPlaces instanceof TogetherSportWithNrOfPlaces) {
                            return $sportWithNrOfPlaces->calculateNrOfGames($sportWithNrOfFieldsAndNrOfCycles->nrOfCycles);
//                        }

                    }, $this->sportsWithNrOfFieldsAndNrOfCycles )
                );
            }, $this->pouleStructure->toArray()));
    }

//    public function getMaxNrOfGamesPerBatchForSportVariantWithFields(
//        SportVariantWithFields $sportVariantWithFields ): int {
//
//        $maxNrOfGamesPerBatch = $this->getMaxNrOfGamesPerBatchForPoulePlaces($sportVariantWithFields );
//
//        $maxNrOfGamesPerBatch = min($maxNrOfGamesPerBatch, $sportVariantWithFields->getNrOfFields());
//
//        if( $this->refereeInfo->nrOfReferees > 0 ) {
//            $maxNrOfGamesPerBatch = min($this->refereeInfo->nrOfReferees, $maxNrOfGamesPerBatch);
//        }
//        return $maxNrOfGamesPerBatch;
//    }
//
//
//    private function getMaxNrOfGamesPerBatchForPoulePlaces(SportVariantWithFields $sportVariantWithFields): int {
//        $maxNrOfGamesPerBatch = 0;
//        $selfRefereeInfo = $this->refereeInfo->selfRefereeInfo;
//        foreach ($this->toArray() as $nrOfPlaces) {
//            $sportVariantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $sportVariantWithFields->getSportVariant());
//            $maxNrOfGamesPerBatch += $sportVariantWithPoule->getMaxNrOfGamesSimultaneously($selfRefereeInfo);
//        }
//        return $maxNrOfGamesPerBatch;
//    }
}