<?php

namespace SportsPlanning;

use SportsHelpers\SelfReferee;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Helpers\SportVariantWithNrOfPlacesCreator;
use SportsHelpers\SportVariants\Helpers\SportVariantWithNrOfPlacesCreator as VariantCreator;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;
use SportsHelpers\SportVariants\Single;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsPlanning\Exceptions\SelfRefereeIncompatibleWithPouleStructureException;
use SportsPlanning\Referee\Info as RefereeInfo;

readonly class PlanningPouleStructure
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithNrOfFields
     * @param RefereeInfo $refereeInfo
     * @throws \Exception
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportVariantsWithNrOfFields,
        public RefereeInfo $refereeInfo )
    {

        if( !$pouleStructure->areSportsAndSelfRefereeCompatible(
            $this->createSportVariants(), $refereeInfo->selfRefereeInfo->selfReferee) ) {
            throw new SelfRefereeIncompatibleWithPouleStructureException(
                $pouleStructure, $sportVariantsWithNrOfFields,
                $refereeInfo->selfRefereeInfo->selfReferee
            );
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



    public function getMaxNrOfGamesPerBatch(): int {

        $sortedSportVariantsWithFields = $this->getSortedSportVariantsByNrOfGamePlaces();

        $nrOfBatchGames = 0;
        $poules = array_reverse($this->pouleStructure->toArray());
        $nrOfReferees = $this->refereeInfo->nrOfReferees;
        $doSelfRefereeOtherPouleCheck = $this->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::OtherPoules;
        $doRefereeCheck = $nrOfReferees > 0;
        $sportVariantWithFields = array_shift($sortedSportVariantsWithFields);
        // $singleSportVariantWithFields = count($sortedSportVariantsWithFields) === 0 ? $sportVariantWithFields : null;
        $currentPouleNrOfPlaces = $this->substractPlaces($poules);
        $nrOfPlaces = $currentPouleNrOfPlaces;

        while ($nrOfPlaces > 0 && $sportVariantWithFields !== null &&  (!$doRefereeCheck || $nrOfReferees > 0)) {
            $nrOfFields = $sportVariantWithFields->nrOfFields;
            $sportVariant = $sportVariantWithFields->createSportVariant();
            $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $currentPouleNrOfPlaces);
            // SelfRef::SamePoule
            $nrOfGamePlaces += ($this->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::SamePoule ? 1 : 0);

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
            $sportVariantWithFields = array_shift($sortedSportVariantsWithFields);
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
        $nrOfSelfRefSim = $this->refereeInfo->selfRefereeInfo->nrIfSimSelfRefs;
        $newNrOfBatchGames = $nrOfBatchGames + 1;
        $newNrOfPlacesToGo = $nrOfPlacesToGo - $nrOfGamePlaces;
        $newNrOfSelfRefereePlacesUsed = (int)ceil($newNrOfBatchGames / $nrOfSelfRefSim);

        return $newNrOfPlacesToGo >= $newNrOfSelfRefereePlacesUsed;
    }


    public function getMaxNrOfGamesInARow(): int
    {
        $pouleStructure = $this->pouleStructure;
        $biggestPouleNrOfPlaces = $pouleStructure->getBiggestPoule();
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        $nrOfPlaces *= $nrOfPoulesByNrOfPlaces[$nrOfPlaces];
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch();

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            $totalNrOfGamesPerPlace = $this->getTotalNrOfGamesPerPlaceForPoule($nrOfPlaces);
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
     * @return list<Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_map(
            function (SportPersistVariantWithNrOfFields $sportVariantWithField): Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame {
                return $sportVariantWithField->createSportVariant();
        }, $this->sportVariantsWithNrOfFields);
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
        $sortedSportVariantsWithFields = $this->getSortedSportVariantsByNrOfGamePlaces();
        $selfReferee = $this->refereeInfo->selfRefereeInfo->selfReferee !== SelfReferee::Disabled;
        $nrOfBatchPlaces = 0;
        $nrOfPlaces = $this->pouleStructure->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sortedSportVariantsWithFields) > 0) {
            $sportVariantWithFields = array_shift($sortedSportVariantsWithFields);
            $sportVariant = $sportVariantWithFields->createSportVariant();
            if ($sportVariant instanceof AllInOneGame) {
                return $this->pouleStructure->getBiggestPoule();
            }
            $sportNrOfGamePlaces = ($sportVariant instanceof Single) ? $sportVariant->nrOfGamePlaces : $sportVariant->getNrOfGamePlaces();
            $sportNrOfGamePlaces = $sportNrOfGamePlaces + ($selfReferee ? 1 : 0);
            $nrOfFields = $sportVariantWithFields->nrOfFields;
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
     * @return list<SportPersistVariantWithNrOfFields>
     */
    protected function getSortedSportVariantsByNrOfGamePlaces(): array {
        $sportVariantsWithFields = array_slice( $this->sportVariantsWithNrOfFields, 0);
        $nrOfPlaces = $this->pouleStructure->getBiggestPoule();

        uasort(
            $sportVariantsWithFields,
            function (SportPersistVariantWithNrOfFields $a, SportPersistVariantWithNrOfFields $b) use($nrOfPlaces): int {
                $nrOfGamePlacesA = $this->getNrOfGamePlaces($a->createSportVariant(), $nrOfPlaces);
                $nrOfGamePlacesB = $this->getNrOfGamePlaces($b->createSportVariant(), $nrOfPlaces);
                return $nrOfGamePlacesA < $nrOfGamePlacesB ? -1 : 1;
            }
        );
        return array_values( $sportVariantsWithFields );
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

    public function getMaxNrOfGamesPerPlaceRange(): SportRange
    {
        return new SportRange(
            $this->getTotalNrOfGamesPerPlaceForPoule( $this->pouleStructure->getSmallestPoule() ),
            $this->getTotalNrOfGamesPerPlaceForPoule( $this->pouleStructure->getBiggestPoule() )
        );
    }

    private function getTotalNrOfGamesPerPlaceForPoule(int $nrOfPlaces): int
    {
        $sportVariants = $this->createSportVariants();
        $sportVariantsWithNrOfPlaces = (new SportVariantWithNrOfPlacesCreator())->createListWithNrOfPlaces($nrOfPlaces, $sportVariants);

        $nrOfGamesPerPlace = 0;
        foreach ($sportVariantsWithNrOfPlaces as $sportVariantWithNrOfPlaces) {
            $nrOfGamesPerPlace += $sportVariantWithNrOfPlaces->getTotalNrOfGamesPerPlace();
        }
        return $nrOfGamesPerPlace;
    }

    protected function getNrOfGamePlaces(AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame $sportVariant, int $nrOfPlaces): int
    {
        if( $sportVariant instanceof AllInOneGame ) {
            return $nrOfPlaces;
        }
        if( $sportVariant instanceof Single ) {
            return $sportVariant->nrOfGamePlaces;
        }
        return $sportVariant->getNrOfGamePlaces();
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