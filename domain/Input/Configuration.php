<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsOne;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsTwo;
use SportsPlanning\Sports\Plannable\AgainstPlannableTwoVsTwo;
use SportsPlanning\Sports\Plannable\TogetherPlannableSport;
use SportsPlanning\Sports\SportWithNrOfFields;

class Configuration
{
    private string|null $name = null;

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFields> $sportsWithNrOfFields
     * @param RefereeInfo $refereeInfo
     * @param bool $perPoule
     * @throws \Exception
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportsWithNrOfFields,
        public RefereeInfo $refereeInfo,
        public bool $perPoule )
    {
        $selfReferee = $refereeInfo->selfRefereeInfo->selfReferee;

        if( !$pouleStructure->isCompatibleWithSportsAndSelfReferee( $this->getSports(), $selfReferee ) ) {
            throw new \Exception('selfReferee is not compatible with poulestructure', E_ERROR);
        }
        if( !$pouleStructure->isCompatibleWithSports( $this->getSports() ) ) {
            throw new \Exception('te weinig poule-plekken om wedstrijden te kunnen plannen, maak de poule(s) groter', E_ERROR);
        }
    }

    /**
     * @return list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport>
     */
    private function getSports(): array {
        return array_map( function(SportWithNrOfFields $sportWithNrOfFields): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport {
            return $sportWithNrOfFields->sport;
        }, $this->sportsWithNrOfFields );
    }

    public function getName(): string {
        if( $this->name === null ) {
            $nameParts = [
                '[' . $this->pouleStructure . ']',
                '[' . join(' & ', $this->sportsWithNrOfFields) . ']',
                'ref=>' . $this->refereeInfo
            ];
            if( $this->perPoule ) {
                $nameParts[] =  'pp';
            }
            $this->name = join(' - ', $nameParts);
        }
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