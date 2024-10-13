<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;

class Configuration
{
    private string|null $name = null;

    public function __construct(public PlanningPouleStructure $planningPouleStructure, public bool $perPoule )
    {
        $pouleStructure = $this->planningPouleStructure->pouleStructure;
        $selfReferee = $this->planningPouleStructure->refereeInfo->selfRefereeInfo->selfReferee;
        if( !$pouleStructure->areSportsAndSelfRefereeCompatible( $this->createSportVariants(), $selfReferee ) ) {
            throw new \Exception('selfReferee is not compatible with poulestructure', E_ERROR);
        }
    }

    public function getName(): string {
        if( $this->name === null ) {
            $pouleStructure = $this->planningPouleStructure->pouleStructure;
            $sportVariantsWithNrOfFields = $this->planningPouleStructure->sportVariantsWithNrOfFields;
            $nameParts = [
                '[' . $pouleStructure . ']',
                '[' . join(' & ', $sportVariantsWithNrOfFields) . ']',
                'ref=>' . $this->planningPouleStructure->refereeInfo
            ];
            if( $this->perPoule ) {
                $nameParts[] =  'pp';
            }
            $this->name = join(' - ', $nameParts);
        }
        return $this->name;
    }

    /**
     * @return list<Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        $sportVariantsWithNrOfFields = $this->planningPouleStructure->sportVariantsWithNrOfFields;
        return array_map( function (SportPersistVariantWithNrOfFields $sportVariantWithField): Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame {
            return $sportVariantWithField->createSportVariant();
        }, $sportVariantsWithNrOfFields);
    }

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