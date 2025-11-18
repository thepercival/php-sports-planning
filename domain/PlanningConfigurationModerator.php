<?php

namespace SportsPlanning;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PlanningConfigurationModerator
{
    public function __construct()
    {
    }

    /**
     * @param list<PouleStructureWithCategoryNr> $pouleStructures
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param RefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function createReducedAndValidatedConfiguration(
        array $pouleStructures,
        array $sportsWithNrOfFieldsAndNrOfCycles,
        RefereeInfo|null $refereeInfo,
        bool $perPoule
        ): PlanningConfiguration
    {
        $sports = array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport{
                return $sportWithNrOfFieldsAndNrOfCycles->sport;
            }, $sportsWithNrOfFieldsAndNrOfCycles
        );
        $validatedRefereeInfo = null;
        if( $refereeInfo !== null ) {
            $validatedRefereeInfo = $this->getValidatedRefereeInfo($refereeInfo, $pouleStructures, $sports);
        }

        $efficientSports = $this->reduceFields($pouleStructures, $sportsWithNrOfFieldsAndNrOfCycles, $validatedRefereeInfo);
        return new PlanningConfiguration(
            $pouleStructures,
            $efficientSports,
            $validatedRefereeInfo,
            $perPoule
        );
    }

    /**
     * @param RefereeInfo $refereeInfo
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     * @param list<PouleStructureWithCategoryNr> $pouleStructures
     * @return RefereeInfo
     */
    protected function getValidatedRefereeInfo(
        RefereeInfo $refereeInfo, array $pouleStructures, array $sports
        ): RefereeInfo
    {
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        if( $selfRefereeInfo === null ) {
            return RefereeInfo::fromNrOfReferees( $refereeInfo->nrOfReferees );
        }
        $newSelfReferee = $this->getValidatedSelfReferee($selfRefereeInfo->selfReferee, $pouleStructures, $sports);
        if( $newSelfReferee === null ) {
            return RefereeInfo::fromNrOfReferees( $refereeInfo->nrOfReferees );
        }
        return RefereeInfo::fromSelfRefereeInfo( new SelfRefereeInfo( $newSelfReferee, $selfRefereeInfo->nrOfSimSelfRefs ) );
    }

    /**
     * @param SelfReferee $selfReferee
     * @param list<PouleStructureWithCategoryNr> $pouleStructures
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
    * @return SelfReferee
     */
    protected function getValidatedSelfReferee(
        SelfReferee $selfReferee,
        array $pouleStructures,
        array $sports,
        ): SelfReferee|null
    {
        $validatorSelfRef = new SelfRefereeValidator();
        $otherPoulesAvailable = $validatorSelfRef->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $validatorSelfRef->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sports);
        if (!$otherPoulesAvailable && !$samePouleAvailable) {
            return null;
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
     * @param list<PouleStructureWithCategoryNr> $pouleStructures
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param RefereeInfo $refereeInfo
     * @return list<SportWithNrOfFieldsAndNrOfCycles>
     */
    protected function reduceFields(
        array $pouleStructuresWithCategoryNr,
        array $sportsWithNrOfFieldsAndNrOfCycles,
        RefereeInfo|null $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructuresWithCategoryNr,
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        $reducedSportsWithNrOfFieldsAndNrOfCycles = [];
        foreach ($sportsWithNrOfFieldsAndNrOfCycles as $sportWithNrOfFieldsAndNrOfCycles) {
            $reducedNrOfFields = $sportWithNrOfFieldsAndNrOfCycles->nrOfFields;
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportsWithNrOfFieldsAndNrOfCycles[] = new SportWithNrOfFieldsAndNrOfCycles(
                $sportWithNrOfFieldsAndNrOfCycles->sport,
                $reducedNrOfFields,
                $sportWithNrOfFieldsAndNrOfCycles->nrOfCycles
            );
        }

        $pouleStructure = $planningPouleStructure->createMergedPouleStructure();
        $moreReducedSports = $this->reduceFieldsBySports($pouleStructure, $reducedSportsWithNrOfFieldsAndNrOfCycles);

        usort(
            $moreReducedSports,
            function (SportWithNrOfFields $sportA, SportWithNrOfFields $sportB): int {
                return $sportA->nrOfFields > $sportB->nrOfFields ? -1 : 1;
            }
        );
        return $moreReducedSports;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @return list<SportWithNrOfFieldsAndNrOfCycles>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportsWithNrOfFieldsAndNrOfCycles): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles);
        return array_map(
            function (SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportWithNrOfFieldsAndNrOfCycles {
                return $this->reduceSportWithNrOfFieldsAndNrOfCycles(
                    $pouleStructure,
                    $sportWithNrOfFieldsAndNrOfCycles,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportsWithNrOfFieldsAndNrOfCycles
        );
    }

    protected function reduceSportWithNrOfFieldsAndNrOfCycles(
        PouleStructure $pouleStructure,
        SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles,
        int $minNrOfBatches
    ): SportWithNrOfFieldsAndNrOfCycles {
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        $nrOfFields = $sportWithNrOfFieldsAndNrOfCycles->nrOfFields;
        if ($nrOfFields === 1) {
            return $sportWithNrOfFieldsAndNrOfCycles;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles], $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles], $nrOfFields);
        }
        return new SportWithNrOfFieldsAndNrOfCycles($sport, $nrOfFields, $sportWithNrOfFieldsAndNrOfCycles->nrOfCycles);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @return int
     */
    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportsWithNrOfFieldsAndNrOfCycles): int
    {
        $leastNrOfBatchesNeeded = null;
        foreach ($sportsWithNrOfFieldsAndNrOfCycles as $sportWithNrOfFieldsAndNrOfCycles) {
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
                $pouleStructure,
                [$sportWithNrOfFieldsAndNrOfCycles],
                $sportWithNrOfFieldsAndNrOfCycles->nrOfFields
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
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param int $nrOfFields
     * @return int
     */
    protected function getNrOfBatchesNeeded(
        PouleStructure $pouleStructure,
        array $sportsWithNrOfFieldsAndNrOfCycles,
        int $nrOfFields
    ): int {
        $nrOfGames = $this->calculateTotalNrOfGames($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles);
        return (int)ceil($nrOfGames / $nrOfFields);
    }


    /**
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @return int
     */
    public function calculateTotalNrOfGames(PouleStructure $pouleStructure, array $sportsWithNrOfFieldsAndNrOfCycles): int
    {
        return array_sum(
            array_map( function(int $nrOfPlaces) use($sportsWithNrOfFieldsAndNrOfCycles): int  {

                return array_sum(
                    array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles) use($nrOfPlaces): int {
                        $sportWithNrOfPlaces = $sportWithNrOfFieldsAndNrOfCycles->createSportWithNrOfPlaces($nrOfPlaces);
//                        if( !($sportWithNrOfPlaces instanceof SportWithNrOfPlacesInterface)) {
//                            throw new \Exception('unknown class (not SportWithNrOfPlacesInterface)');
//                        }
                        return $sportWithNrOfPlaces->calculateNrOfGames($sportWithNrOfFieldsAndNrOfCycles->nrOfCycles);
                    }, $sportsWithNrOfFieldsAndNrOfCycles )
                );
            }, $pouleStructure->toArray()));
    }
}