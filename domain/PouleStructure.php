<?php

namespace SportsPlanning;

use Doctrine\Common\Collections\Collection;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\GamePlaceCalculator;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\AllInOneGame as AllInOneGameWithPoule;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\PouleStructure as PouleStructureBase;
use SportsPlanning\Referee\Info as RefereeInfo;

class PouleStructure
{
    /**
     * @param PouleStructureBase $pouleStructureBase
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @throws \Exception
     */
    public function __construct(
        private PouleStructureBase $pouleStructureBase,
        private readonly array     $sportVariantsWithFields,
        private RefereeInfo        $refereeInfo )
    {
        if( !$this->selfRefereeIsValid($refereeInfo->selfRefereeInfo->selfReferee) ) {
            throw new \Exception('selfReferee is not compatible with poulestructure', E_ERROR);
        }
    }

    public function getMaxNrOfGamesPerBatch(): int {

        $sortedSportVariantsWithFields = $this->getSortedSportVariantsByNrOfGamePlaces();

        $nrOfBatchGames = 0;
        $poules = array_reverse($this->pouleStructureBase->toArray());
        $nrOfReferees = $this->refereeInfo->nrOfReferees;
        $doSelfRefereeOtherPouleCheck = $this->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::OtherPoules;
        $doRefereeCheck = $nrOfReferees > 0;
        $sportVariantWithFields = array_shift($sortedSportVariantsWithFields);
        // $singleSportVariantWithFields = count($sortedSportVariantsWithFields) === 0 ? $sportVariantWithFields : null;
        $currentPouleNrOfPlaces = $this->substractPlaces($poules);
        $nrOfPlaces = $currentPouleNrOfPlaces;

        while ($nrOfPlaces > 0 && $sportVariantWithFields !== null &&  (!$doRefereeCheck || $nrOfReferees > 0)) {
            $nrOfFields = $sportVariantWithFields->getNrOfFields();
            $sportVariant = $sportVariantWithFields->getSportVariant();
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
        $pouleStructure = $this->pouleStructureBase;
        $biggestPouleNrOfPlaces = $pouleStructure->getBiggestPoule();
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        $nrOfPlaces *= $nrOfPoulesByNrOfPlaces[$nrOfPlaces];
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch();

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            $maxNrOfGamesPerPlace = $this->getMaxNrOfGamesPerPlaceForPoule($nrOfPlaces);
            if ($maxNrOfGamesPerPlace > ($biggestPouleNrOfPlaces - 1)) {
                $maxNrOfGamesPerPlace = $biggestPouleNrOfPlaces - 1;
            }
            return $maxNrOfGamesPerPlace;
        }
        $maxNrOfGamesInARow = (int)ceil($nrOfPlaces / $nrOfRestPlaces);
        if ($maxNrOfGamesInARow > ($biggestPouleNrOfPlaces - 1)) {
            $maxNrOfGamesInARow = $biggestPouleNrOfPlaces - 1;
        }
        return $maxNrOfGamesInARow;
    }

    /**
     * @return list<Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_map(
            function (SportVariantWithFields $sportVariantWithField): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $sportVariantWithField->getSportVariant();
        }, $this->sportVariantsWithFields);
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
        $nrOfPlaces = $this->pouleStructureBase->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sortedSportVariantsWithFields) > 0) {
            $sportVariantWithFields = array_shift($sortedSportVariantsWithFields);
            $sportVariant = $sportVariantWithFields->getSportVariant();
            if ($sportVariant instanceof AllInOneGame) {
                return $this->pouleStructureBase->getBiggestPoule();
            }
            $sportNrOfGamePlaces = $sportVariant->getNrOfGamePlaces() + ($selfReferee ? 1 : 0);
            $nrOfFields = $sportVariantWithFields->getNrOfFields();
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
     * @return list<SportVariantWithFields>
     */
    protected function getSortedSportVariantsByNrOfGamePlaces(): array {
        $sportVariantsWithFields = array_slice( $this->sportVariantsWithFields, 0);
        $nrOfPlaces = $this->pouleStructureBase->getBiggestPoule();

        uasort(
            $sportVariantsWithFields,
            function (SportVariantWithFields $a, SportVariantWithFields $b) use($nrOfPlaces): int {
                $nrOfGamePlacesA = $this->getNrOfGamePlaces($a->getSportVariant(), $nrOfPlaces);
                $nrOfGamePlacesB = $this->getNrOfGamePlaces($b->getSportVariant(), $nrOfPlaces);
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


    /**
     * @param int $nrOfPlaces
     * @return int
     */
    private function getMaxNrOfGamesPerPlaceForPoule(int $nrOfPlaces): int
    {
        $sportVariants = $this->createSportVariants();
        $sportVariantsWithPoule = (new VariantCreator())->createWithPoules($nrOfPlaces, $sportVariants);

        $nrOfGamesPerPlace = 0;
        foreach ($sportVariantsWithPoule as $sportVariantWithPoule) {
            if( $sportVariantWithPoule instanceof AgainstGppWithPoule ) {
                $nrOfGamesPerPlace += $sportVariantWithPoule->getMaxNrOfGamesPerPlace();
            } else {
                $nrOfGamesPerPlace += $sportVariantWithPoule->getTotalNrOfGamesPerPlace();
            }
        }
        return $nrOfGamesPerPlace;
    }

    // ///////////////////////
    protected function getNrOfGamePlaces(AgainstH2h|AgainstGpp|Single|AllInOneGame $sportVariant, int $nrOfPlaces): int
    {
        return $sportVariant instanceof AllInOneGame ? $nrOfPlaces : $sportVariant->getNrOfGamePlaces();
    }
    // /////////////////////


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

    /**
     * @param SelfReferee $selfReferee
     * @return bool
     */
    protected function selfRefereeIsValid(SelfReferee $selfReferee): bool
    {
        if ($selfReferee === SelfReferee::SamePoule) {
            return $this->selfRefereeSamePouleIsValid();
        } elseif ($selfReferee === SelfReferee::OtherPoules) {
            return $this->selfRefereeOtherPoulesIsValid();
        }
        return true;
    }


    protected function selfRefereeOtherPoulesIsValid(): bool
    {
        return $this->pouleStructureBase->getNrOfPoules() > 1;
    }

    protected function selfRefereeSamePouleIsValid(): bool
    {
        foreach ($this->createSportVariants() as $sportVariant) {
            if( $sportVariant instanceof AllInOneGame) {
                return false;
            }
            return $sportVariant->getNrOfGamePlaces() < $this->pouleStructureBase->getSmallestPoule();
        }
        return true;
    }
}