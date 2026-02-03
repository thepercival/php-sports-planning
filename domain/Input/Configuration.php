<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\PlanningRefereeInfo;

final class Configuration
{
    private string|null $name = null;

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param PlanningRefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportVariantsWithFields,
        public PlanningRefereeInfo $refereeInfo,
        public bool $perPoule
    )
    {
        if( $refereeInfo->selfRefereeInfo !== null &&
            $refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::Disabled &&
            !$pouleStructure->sportsAndSelfRefereeAreCompatible(
                $this->createSportVariants(), $refereeInfo->selfRefereeInfo->selfReferee) ) {
            throw new \Exception('selfReferee is not compatible with poulestructure', E_ERROR);
        }
    }

    public function getName(): string {
        if( $this->name === null ) {
            $nameParts = [
                '[' . ((string)$this->pouleStructure) . ']',
                '[' . join(' & ', $this->sportVariantsWithFields) . ']',
                'ref=>' . ((string)$this->refereeInfo)
            ];
            if( $this->perPoule ) {
                $nameParts[] =  'pp';
            }
            $this->name = join(' - ', $nameParts);
        }
        return $this->name;
    }

    /**
     * @return list<Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_map( function (SportVariantWithFields $sportVariantWithField): Single|AgainstH2h|AgainstGpp|AllInOneGame {
            return $sportVariantWithField->getSportVariant();
        }, $this->sportVariantsWithFields);
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