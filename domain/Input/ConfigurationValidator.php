<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
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
            $pouleStructure,
            $efficientSports,
            $validatedRefereeInfo,
            $perPoule
        );
    }

    /**
     * @param RefereeInfo $refereeInfo
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport> $sports
     * @param PouleStructure $pouleStructure
     * @return RefereeInfo
     */
    protected function getValidatedRefereeInfo(
        RefereeInfo $refereeInfo, PouleStructure $pouleStructure, array $sports
        ): RefereeInfo
    {
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        $newSelfReferee = $this->getValidatedSelfReferee($selfRefereeInfo->selfReferee, $pouleStructure, $sports);
        if( $newSelfReferee === SelfReferee::Disabled ) {
            return new RefereeInfo( $refereeInfo->nrOfReferees );
        }
        return new RefereeInfo( new SelfRefereeInfo( $newSelfReferee, $selfRefereeInfo->nrIfSimSelfRefs ) );
    }

    /**
     * @param SelfReferee $selfReferee
     * @param PouleStructure $pouleStructure
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport> $sports
    * @return SelfReferee
     */
    protected function getValidatedSelfReferee(
        SelfReferee $selfReferee,
        PouleStructure $pouleStructure,
        array $sports,
        ): SelfReferee
    {
        $planningInputService = new PlanningInputService();
        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sports);
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
     * @param list<AgainstPlannableOneVsOne|AgainstPlannableOneVsTwo|AgainstPlannableTwoVsTwo|TogetherPlannableSport> $plannableSports
     * @param RefereeInfo $refereeInfo
     * @return list<SportWithNrOfFields>
     */
    protected function reduceFields(
        PouleStructure $pouleStructure,
        array $plannableSports,
        RefereeInfo $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructure,
            $plannableSports,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
        $reducedSportsWithNrOfFields = [];
        foreach ($plannableSports as $plannableSport) {
            $sportWithNrOfFields = $plannableSport->createSportWithNrOfFields();
            $reducedNrOfFields = $sportWithNrOfFields->nrOfFields;
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportsWithNrOfFields[] = new SportWithNrOfFields(
                $sportWithNrOfFields->sport,
                $reducedNrOfFields
            );
        }

        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportsWithNrOfFields);

        usort(
            $moreReducedSportVariants,
            function (SportWithNrOfFields $sportA, SportWithNrOfFields $sportB): int {
                return $sportA->nrOfFields > $sportB->nrOfFields ? -1 : 1;
            }
        );
        return $moreReducedSportVariants;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFields> $sportsWithNrOfFields
     * @return list<SportWithNrOfFields>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportsWithNrOfFields): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportsWithNrOfFields);
        return array_map(
            function (SportWithNrOfFields $sportWithNrOfFields) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportWithNrOfFields {
                return $this->reduceSportVariantFields(
                    $pouleStructure,
                    $sportWithNrOfFields,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportsWithNrOfFields
        );
    }

    protected function reduceSportVariantFields(
        PouleStructure $pouleStructure,
        SportWithNrOfFields $sportWithNrOfFields,
        int $minNrOfBatches
    ): SportWithNrOfFields {
        $sport = $sportWithNrOfFields->sport;
        $nrOfFields = $sportWithNrOfFields->nrOfFields;
        if ($nrOfFields === 1) {
            return $sportWithNrOfFields;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sport, $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sport, $nrOfFields);
        }
        return new SportWithNrOfFields($sport, $nrOfFields);
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
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sportVariants
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