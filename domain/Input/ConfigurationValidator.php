<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsPlanning\PouleStructure as PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;

class ConfigurationValidator
{
    public function __construct()
    {
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function createReducedAndValidatedInputConfiguration(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo,
        bool $perPoule
        ): InputConfiguration
    {

        $sportVariants = array_map( function (SportVariantWithFields $sportVariantWithField): Single|AgainstH2h|AgainstGpp|AllInOneGame {
            return $sportVariantWithField->getSportVariant();
        }, $sportVariantsWithFields);

        $validatedRefereeInfo = $this->getValidatedRefereeInfo(
            $refereeInfo, $pouleStructure, $sportVariants);

        $efficientSportVariants = $this->reduceFields($pouleStructure, $sportVariantsWithFields, $validatedRefereeInfo);
        return new InputConfiguration(
            $pouleStructure,
            $efficientSportVariants,
            $validatedRefereeInfo,
            $perPoule
        );
    }

    /**
     * @param RefereeInfo $refereeInfo
     * @param list<AgainstH2h|AgainstGpp|Single|AllInOneGame> $sportVariants
     * @param PouleStructure $pouleStructure
     * @return RefereeInfo
     */
    protected function getValidatedRefereeInfo(
        RefereeInfo $refereeInfo, PouleStructure $pouleStructure, array $sportVariants
        ): RefereeInfo
    {
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        $newSelfReferee = $this->getValidatedSelfReferee($selfRefereeInfo->selfReferee, $pouleStructure, $sportVariants);
        if( $newSelfReferee === SelfReferee::Disabled ) {
            return new RefereeInfo( $refereeInfo->nrOfReferees );
        }
        return new RefereeInfo( new SelfRefereeInfo( $newSelfReferee, $selfRefereeInfo->nrIfSimSelfRefs ) );
    }

    /**
     * @param SelfReferee $selfReferee
     * @param PouleStructure $pouleStructure
     * @param list<AgainstH2h|AgainstGpp|Single|AllInOneGame> $sportVariants
    * @return SelfReferee
     */
    protected function getValidatedSelfReferee(
        SelfReferee $selfReferee,
        PouleStructure $pouleStructure,
        array $sportVariants,
        ): SelfReferee
    {
        $planningInputService = new PlanningInputService();
        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sportVariants);
        if (!$otherPoulesAvailable && !$samePouleAvailable) {
            return SelfReferee::Disabled;
        }
        if ($selfReferee === SelfReferee::OtherPoules && !$otherPoulesAvailable) {
            return SelfReferee::SamePoule;
        }
        if ($selfReferee === SelfReferee::SamePoule && !$samePouleAvailable) {
            return SelfReferee::OtherPoules;
        }
        return $selfReferee;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return list<SportVariantWithFields>
     */
    protected function reduceFields(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructure,
            $sportVariantsWithFields,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
        $reducedSportVariants = [];
        foreach ($sportVariantsWithFields as $sportVariantWithField) {
            $reducedNrOfFields = $sportVariantWithField->getNrOfFields();
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportVariants[] = new SportVariantWithFields(
                $sportVariantWithField->getSportVariant(),
                $reducedNrOfFields
            );
        }

        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportVariants);

        usort(
            $moreReducedSportVariants,
            function (SportVariantWithFields $sportA, SportVariantWithFields $sportB): int {
                return $sportA->getNrOfFields() > $sportB->getNrOfFields() ? -1 : 1;
            }
        );
        return $moreReducedSportVariants;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @return list<SportVariantWithFields>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportVariantsWithFields): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportVariantsWithFields);
        return array_map(
            function (SportVariantWithFields $sportVariantWithFields) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportVariantWithFields {
                return $this->reduceSportVariantFields(
                    $pouleStructure,
                    $sportVariantWithFields,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportVariantsWithFields
        );
    }

    protected function reduceSportVariantFields(
        PouleStructure $pouleStructure,
        SportVariantWithFields $sportVariantWithFields,
        int $minNrOfBatches
    ): SportVariantWithFields {
        $sportVariant = $sportVariantWithFields->getSportVariant();
        $nrOfFields = $sportVariantWithFields->getNrOfFields();
        if ($nrOfFields === 1) {
            return $sportVariantWithFields;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        }
        return new SportVariantWithFields($sportVariant, $nrOfFields);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @return int
     */
    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportVariantsWithFields): int
    {
        $leastNrOfBatchesNeeded = null;
        foreach ($sportVariantsWithFields as $sportVariantWithField) {
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
                $pouleStructure,
                $sportVariantWithField->getSportVariant(),
                $sportVariantWithField->getNrOfFields()
            );
            if ($leastNrOfBatchesNeeded === null || $nrOfBatchesNeeded > $leastNrOfBatchesNeeded) {
                $leastNrOfBatchesNeeded = $nrOfBatchesNeeded;
            }
        }
        if ($leastNrOfBatchesNeeded === null) {
            throw new \Exception('at least one sport is needed', E_ERROR);
        }
        return $leastNrOfBatchesNeeded;
    }

    protected function getNrOfBatchesNeeded(
        PouleStructure $pouleStructure,
        AgainstH2h|AgainstGpp|Single|AllInOneGame $sportVariant,
        int $nrOfFields
    ): int {
        $nrOfGames = $pouleStructure->getTotalNrOfGames([$sportVariant]);
        return (int)ceil($nrOfGames / $nrOfFields);
    }
}