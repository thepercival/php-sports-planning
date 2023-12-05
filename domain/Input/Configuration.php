<?php

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Sport;

class Configuration
{
    private string|null $name = null;

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @param bool $perPoule
     */
    public function __construct(
        public PouleStructure $pouleStructure,
        public array $sportVariantsWithFields,
        public RefereeInfo $refereeInfo,
        public bool $perPoule
    )
    {
    }

    public function getName(): string {
        if( $this->name === null ) {
            $nameParts = [
                '[' . $this->pouleStructure . ']',
                '[' . join(' & ', $this->sportVariantsWithFields) . ']',
                'ref=>' . $this->refereeInfo
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
}