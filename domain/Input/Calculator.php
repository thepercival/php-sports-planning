<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\GamePlaceCalculator;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\WithPoule\AllInOneGame as AllInOneGameWithPoule;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Sport;

class Calculator
{
    protected GamePlaceCalculator $sportGamePlaceCalculator;

    public function __construct()
    {
        $this->sportGamePlaceCalculator = new GamePlaceCalculator();
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param non-empty-list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return int
     */
    public function getMinNrOfGamesPerBatch(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo
    ): int {
        $minNrOfGamesPerBatch = min(
            array_map(
                function (SportVariantWithFields $sportVariantWithFields) use ($pouleStructure, $refereeInfo): int {
                    $minNrOfGamesPerBatch = $this->getMaxNrOfSportGamesPerBatchByPlaces(
                        $pouleStructure,
                        $sportVariantWithFields,
                        $refereeInfo
                    );
                    return $this->reduceByFields($minNrOfGamesPerBatch, $sportVariantWithFields->getNrOfFields());
                },
                $sportVariantsWithFields
            )
        );

        // $minNrOfGamesPerBatch = $minNrOfGamesPerBatch === false ? 1 : $minNrOfGamesPerBatch;
        return $this->reduceByReferees($minNrOfGamesPerBatch, $refereeInfo);
    }


    public function getMaxNrOfSportGamesPerBatchByPlaces(
        PouleStructure $pouleStructure,
        SportVariantWithFields $sportVariantWithFields,
        RefereeInfo $refereeInfo
    ): int {
        $maxNrOfGamesPerBatch = 0;
        foreach ($pouleStructure->toArray() as $nrOfPlaces) {
            $variantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $sportVariantWithFields->getSportVariant());
            $maxNrOfGamesPerBatch += $variantWithPoule->getMaxNrOfGamesSimultaneously($refereeInfo->selfReferee);
        }

        $maxNrOfGamesPerBatch = $this->reduceByFields($maxNrOfGamesPerBatch, $sportVariantWithFields->getNrOfFields());
        return $this->reduceByReferees($maxNrOfGamesPerBatch, $refereeInfo);
    }

    public function reduceByFields(int $maxNrOfGamesPerBatch, int $nrOfFields): int
    {
        if ($nrOfFields < $maxNrOfGamesPerBatch) {
            return $nrOfFields;
        }
        return $maxNrOfGamesPerBatch;
    }

    public function reduceByReferees(int $maxNrOfGamesPerBatch, RefereeInfo $refereeInfo): int
    {
        if ($refereeInfo->selfReferee === SelfReferee::Disabled
            && $refereeInfo->nrOfReferees > 0
            && $refereeInfo->nrOfReferees < $maxNrOfGamesPerBatch) {
            return $refereeInfo->nrOfReferees;
        }
        return $maxNrOfGamesPerBatch;
    }

//    public function reduceByPlaces(int $maxNrOfGamesPerBatch, PouleStructure $pouleStructure): int
//    {
//        // dan zou je door
//        $nrOfGamePlaces
//
//        $maxTmp = $this->getMaxNrOfSportGamesPerBatchByPlaces(
//            $pouleStructure,
//        SportVariantWithFields $sportVariantWithFields,
//        RefereeInfo $refereeInfo
//    )
//        $pouleStructure->
//        if ($refereeInfo->selfReferee === SelfReferee::Disabled
//            && $refereeInfo->nrOfReferees > 0
//            && $refereeInfo->nrOfReferees < $maxNrOfGamesPerBatch) {
//            return $refereeInfo->nrOfReferees;
//        }
//        return $maxNrOfGamesPerBatch;
//    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return int
     */
    public function getMaxNrOfGamesPerBatch(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo
    ): int {
        uasort(
            $sportVariantsWithFields,
            function (SportVariantWithFields $a, SportVariantWithFields $b) use ($pouleStructure): int {
                $nrOfGamePlacesA = $this->getNrOfGamePlaces($a->getSportVariant(), $pouleStructure->getBiggestPoule());
                $nrOfGamePlacesB = $this->getNrOfGamePlaces($b->getSportVariant(), $pouleStructure->getBiggestPoule());
                return $nrOfGamePlacesA < $nrOfGamePlacesB ? -1 : 1;
            }
        );

        $nrOfBatchGames = 0;
        $poules = array_reverse($pouleStructure->toArray());
        $doRefereeCheck = $refereeInfo->nrOfReferees > 0;
        $nrOfReferees = $refereeInfo->nrOfReferees;
        $sportVariantWithFields = array_shift($sportVariantsWithFields);
        $singleSportVariantWithFields = count($sportVariantsWithFields) === 0 ? $sportVariantWithFields : null;
        $currentPouleNrOfPlaces = $this->substractPlaces($poules);
        $nrOfPlaces = $currentPouleNrOfPlaces;

        while ($nrOfPlaces > 0 && $sportVariantWithFields !== null && (!$doRefereeCheck || $nrOfReferees > 0)) {
            $nrOfFields = $sportVariantWithFields->getNrOfFields();
            $sportVariant = $sportVariantWithFields->getSportVariant();
            $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $currentPouleNrOfPlaces);
            $nrOfGamePlaces += ($refereeInfo->selfReferee === SelfReferee::SamePoule ? 1 : 0);

            while ($nrOfPlaces >= $nrOfGamePlaces && $nrOfFields-- > 0 && (!$doRefereeCheck || $nrOfReferees-- > 0)) {
                $nrOfPlaces -= $nrOfGamePlaces;
                $nrOfBatchGames++;
                if ($nrOfPlaces < $nrOfGamePlaces) {
                    $currentPouleNrOfPlaces = $this->substractPlaces($poules);
                    $nrOfPlaces += $currentPouleNrOfPlaces;
                }
            }
            $sportVariantWithFields = array_shift($sportVariantsWithFields);
        }
        if ($nrOfBatchGames === 0) {
            return 1;
        }

        if ($pouleStructure->isBalanced() && $singleSportVariantWithFields !== null) {
            return $this->applyBalancedStructureAndSingleSportCheck(
                $pouleStructure,
                $singleSportVariantWithFields->getSportVariant(),
                $refereeInfo->selfReferee,
                $nrOfBatchGames
            );
        }

        return $nrOfBatchGames;
    }

    protected function getNrOfGamePlaces(AgainstH2h|AgainstGpp|Single|AllInOneGame $sportVariant, int $nrOfPlaces): int
    {
        $variantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $sportVariant);
        if( $variantWithPoule instanceof AllInOneGameWithPoule) {
            return $variantWithPoule->getNrOfGamePlaces();
        }
        return $variantWithPoule->getSportVariant()->getNrOfGamePlaces();
    }

    protected function applyBalancedStructureAndSingleSportCheck(
        PouleStructure $pouleStructure,
        Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant,
        SelfReferee $selfReferee,
        int $nrOfBatchGames
    ): int {
        $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $pouleStructure->getBiggestPoule());
        $nrOfGamePlaces += ($selfReferee === SelfReferee::SamePoule ? 1 : 0);
        $maxNrOfGamesPerBatchPerPoule = (int)floor($pouleStructure->getBiggestPoule() / $nrOfGamePlaces);
        if ($maxNrOfGamesPerBatchPerPoule === 0) {
            $maxNrOfGamesPerBatchPerPoule = 1;
        }
        // pak maximale aantal wedstrijden per poule tegelijk * het aantal poules
        $maxNrOfBatchGames = $maxNrOfGamesPerBatchPerPoule * $pouleStructure->getNrOfPoules();
        if ($nrOfBatchGames > $maxNrOfBatchGames) {
            $nrOfBatchGames = $maxNrOfBatchGames;
        }

        return $nrOfBatchGames;
    }

    /**
     * @param list<int> $poules
     * @return int
     */
    protected function substractPlaces(array &$poules): int
    {
        return array_shift($poules) ?? 0;
    }

    public function getMaxNrOfGamesInARow(Input $input, bool $selfReferee): int
    {
        $pouleStructure = $input->createPouleStructure();
        $biggestPouleNrOfPlaces = $pouleStructure->getBiggestPoule();
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        $nrOfPlaces *= $nrOfPoulesByNrOfPlaces[$nrOfPlaces];
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch($input, $selfReferee);

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            $sportVariants = array_values($input->createSportVariants()->toArray());
            $variantsWithPoules = (new VariantCreator())->createWithPoules($nrOfPlaces, $sportVariants);
            $maxNrOfGamesPerPlace = $this->sportGamePlaceCalculator->getMaxNrOfGamesPerPlace($variantsWithPoules);
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
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     */
    protected function getMaxNrOfPlacesPerBatch(Input $input, bool $selfReferee): int
    {
        $sports = $input->getSports()->toArray();
        // sort by lowest nrOfGamePlaces first
        uasort($sports, function (Sport $sportA, Sport $sportB): int {
            return $sportA->getNrOfGamePlaces() < $sportB->getNrOfGamePlaces() ? -1 : 1;
        });

        $nrOfBatchPlaces = 0;
        $nrOfPlaces = $input->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sports) > 0) {
            $sport = array_shift($sports);
            $sportVariant = $sport->createVariant();
            if ($sportVariant instanceof AllInOneGame) {
                return $input->getPoule(1)->getPlaces()->count();
            }
            $sportNrOfGamePlaces = $sportVariant->getNrOfGamePlaces() + ($selfReferee ? 1 : 0);
            $nrOfFields = $sport->getNrOfFields();
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
}
