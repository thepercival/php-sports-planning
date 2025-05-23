<?php

namespace SportsPlanning;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Exceptions\SelfRefereeIncompatibleWithPouleStructureException;
use SportsPlanning\Exceptions\SportsIncompatibleWithPouleStructureException;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

readonly class PlanningConfiguration
{
    private string $name;

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFieldsAndNrOfCycles> $sportsWithNrOfFieldsAndNrOfCycles
     * @param PlanningRefereeInfo $refereeInfo
     * @param bool $perPoule
     * @throws \Exception
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportsWithNrOfFieldsAndNrOfCycles,
        public PlanningRefereeInfo $refereeInfo,
        public bool $perPoule )
    {
        $selfReferee = $refereeInfo->selfRefereeInfo->selfReferee;

        $sports = $this->createSports();
        if( !$pouleStructure->isCompatibleWithSportsAndSelfReferee( $sports, $selfReferee ) ) {
            throw new SelfRefereeIncompatibleWithPouleStructureException($pouleStructure, $sports, $selfReferee);
        }
        if( !$pouleStructure->isCompatibleWithSports( $sports ) ) {
            throw new SportsIncompatibleWithPouleStructureException($pouleStructure, $sports);
        }
        $this->name = $this->setNameFromProperties();
    }

    /**
     * @return list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo>
     */
    private function createSports(): array {
        return array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport {
            return $sportWithNrOfFieldsAndNrOfCycles->sport;
        }, $this->sportsWithNrOfFieldsAndNrOfCycles );
    }

    /**
     * @return list<SportWithNrOfCycles>
     */
    public function createSportsWithNrOfCycles(): array {
        return array_map( function(SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles): SportWithNrOfCycles {
            return $sportWithNrOfFieldsAndNrOfCycles->createSportWithNrOfCycles();
        }, $this->sportsWithNrOfFieldsAndNrOfCycles );
    }

    private function setNameFromProperties(): string {

        $nameParts = [
            '[' . $this->pouleStructure . ']',
            '[' . join(' & ', $this->sportsWithNrOfFieldsAndNrOfCycles) . ']',
            'ref=>' . $this->refereeInfo
        ];
        if( $this->perPoule ) {
            $nameParts[] =  'pp';
        }
        return join(' - ', $nameParts);
    }

    public function getName(): string {

        return $this->name;
    }

//    /**
//     * @return list<Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame>
//     */
//    public function createSportVariants(): array
//    {
//        $sportVariantsWithNrOfFields = $this->planningPouleStructure->sportVariantsWithNrOfFields;
//        return array_map( function (SportPersistVariantWithNrOfFields $sportVariantWithField): Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame {
//            return $sportVariantWithField->createSportVariant();
//        }, $sportVariantsWithNrOfFields);
//    }

//    public function equals(Configuration $configuration): bool {
//        if( $this->pouleStructure->equals($configuration->pouleStructure) === false ) {
//            return false;
//        }
//        if( $this->sportVariantsWithFields->equals($configuration->sportVariantsWithFields) === false ) {
//            return false;
//        }
//        if( $this->refereeInfo->equals($configuration->refereeInfo) === false ) {
//            return false;
//        }
//
//        return $this->perPoule === $configuration->perPoule;
//    }
}