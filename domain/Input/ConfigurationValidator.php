<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsPlanning\PlanningPouleStructure as PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsOne;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsTwo;
use SportsPlanning\Sports\Plannable\AgainstPlannableTwoVsTwo;
use SportsPlanning\Sports\Plannable\TogetherPlannableSport;
use SportsPlanning\Sports\SportWithNrOfFields;

class ConfigurationValidator
{
    public function __construct()
    {
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<AgainstPlannableOneVsOne|AgainstPlannableOneVsTwo|AgainstPlannableTwoVsTwo|TogetherPlannableSport> $plannableSports
     * @param RefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function createReducedAndValidatedInputConfiguration(
        PouleStructure $pouleStructure,
        array $plannableSports,
        RefereeInfo $refereeInfo,
        bool $perPoule
        ): InputConfiguration
    {
        $validatedRefereeInfo = $this->getValidatedRefereeInfo($refereeInfo, $pouleStructure, $plannableSports);

        $efficientSports = $this->reduceFields($pouleStructure, $plannableSports, $validatedRefereeInfo);
        return new InputConfiguration(
            new PlanningPouleStructure(
                $pouleStructure,
                $efficientSports,
                $validatedRefereeInfo
            ),
            $perPoule
        );
    }

    /**
     * @param RefereeInfo $refereeInfo
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame> $sportVariants
     * @param PouleStructure $pouleStructure
     * @return RefereeInfo
     */
    protected function getValidatedRefereeInfo(
        RefereeInfo $refereeInfo, PouleStructure $pouleStructure, array $plannableSports
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
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame> $sportVariants
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
     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return list<SportPersistVariantWithNrOfFields>
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
            $reducedNrOfFields = $sportVariantWithField->nrOfFields;
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportVariants[] = new SportPersistVariantWithNrOfFields(
                $sportVariantWithField->createSportVariant(),
                $reducedNrOfFields
            );
        }

        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportVariants);

        usort(
            $moreReducedSportVariants,
            function (SportPersistVariantWithNrOfFields $sportA, SportPersistVariantWithNrOfFields $sportB): int {
                return $sportA->nrOfFields > $sportB->nrOfFields ? -1 : 1;
            }
        );
        return $moreReducedSportVariants;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithNrOfFields
     * @return list<SportPersistVariantWithNrOfFields>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportVariantsWithNrOfFields): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportVariantsWithNrOfFields);
        return array_map(
            function (SportPersistVariantWithNrOfFields $sportVariantWithNrOfFields) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportPersistVariantWithNrOfFields {
                return $this->reduceSportVariantFields(
                    $pouleStructure,
                    $sportVariantWithNrOfFields,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportVariantsWithNrOfFields
        );
    }

    protected function reduceSportVariantFields(
        PouleStructure $pouleStructure,
        SportPersistVariantWithNrOfFields $sportVariantWithNrOfFields,
        int $minNrOfBatches
    ): SportPersistVariantWithNrOfFields {
        $sportVariant = $sportVariantWithNrOfFields->createSportVariant();
        $nrOfFields = $sportVariantWithNrOfFields->nrOfFields;
        if ($nrOfFields === 1) {
            return $sportVariantWithNrOfFields;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        }
        return new SportPersistVariantWithNrOfFields($sportVariant, $nrOfFields);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFields> $sportsWithNrOfFields
     * @return int
     */
    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportsWithNrOfFields): int
    {
        $leastNrOfBatchesNeeded = null;
        foreach ($sportWithNrOfFields as $sportsWithNrOfFields) {
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
                $pouleStructure,
                $sportWithNrOfFields
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

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFields> $sportsWithNrOfFields
     * @param int $nrOfFields
     * @return int
     */
    protected function getNrOfBatchesNeeded(
        PouleStructure $pouleStructure,
        array $sportsWithNrOfFields,
        int $nrOfFields
    ): int {
        $nrOfGames = $pouleStructure->calculateTotalNrOfGames([$sportsWithNrOfFields]);
        return (int)ceil($nrOfGames / $nrOfFields);
    }


    /**
     * @param list<AllInOneGame|Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sportVariants
     * @return int
     */
    public function calculateTotalNrOfGames(array $sportVariants): int
    {
        $nrOfGames = 0;
        foreach ($this->poules as $nrOfPlaces) {
            foreach ($sportVariants as $sportVariant) {
                $nrOfGames += (new VariantCreator())->createWithNrOfPlaces($nrOfPlaces, $sportVariant)->getTotalNrOfGames();
            }
        }
        return $nrOfGames;
    }

}