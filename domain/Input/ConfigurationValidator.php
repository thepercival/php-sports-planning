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
use SportsPlanning\PlanningPouleStructure as PlanningPouleStructure;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

class ConfigurationValidator
{
    public function __construct()
    {
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param PlanningRefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function createReducedAndValidatedInputConfiguration(
        PouleStructure $pouleStructure,
        array $sportsWithNrOfFieldsAndNrOfCycles,
        PlanningRefereeInfo $refereeInfo,
        bool $perPoule
        ): InputConfiguration
    {
        $sports = array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport{
                return $sportWithNrOfFieldsAndNrOfCycles->sport;
            }, $sportsWithNrOfFieldsAndNrOfCycles
        );
        $validatedRefereeInfo = $this->getValidatedRefereeInfo($refereeInfo, $pouleStructure, $sports);

        $efficientSports = $this->reduceFields($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $validatedRefereeInfo);
        return new InputConfiguration(
            $pouleStructure,
            $efficientSports,
            $validatedRefereeInfo,
            $perPoule
        );
    }

    /**
     * @param PlanningRefereeInfo $refereeInfo
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport> $sports
     * @param PouleStructure $pouleStructure
     * @return PlanningRefereeInfo
     */
    protected function getValidatedRefereeInfo(
        PlanningRefereeInfo $refereeInfo, PouleStructure $pouleStructure, array $sports
        ): PlanningRefereeInfo
    {
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        $newSelfReferee = $this->getValidatedSelfReferee($selfRefereeInfo->selfReferee, $pouleStructure, $sports);
        if( $newSelfReferee === SelfReferee::Disabled ) {
            return new PlanningRefereeInfo( $refereeInfo->nrOfReferees );
        }
        return new PlanningRefereeInfo( new SelfRefereeInfo( $newSelfReferee, $selfRefereeInfo->nrIfSimSelfRefs ) );
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
        $validatorSelfRef = new SelfRefereeValidator();
        $otherPoulesAvailable = $validatorSelfRef->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $validatorSelfRef->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sports);
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
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param PlanningRefereeInfo $refereeInfo
     * @return list<SportWithNrOfFieldsAndNrOfCycles>
     */
    protected function reduceFields(
        PouleStructure $pouleStructure,
        array $sportsWithNrOfFieldsAndNrOfCycles,
        PlanningRefereeInfo $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructure,
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
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