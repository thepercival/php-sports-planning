<?php

namespace SportsPlanning\Exceptions;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;

class SelfRefereeIncompatibleWithPouleStructureException extends \Exception
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithNrOfFields
     * @param SelfReferee $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportVariantsWithNrOfFields,
        SelfReferee $selfReferee
    ) {
        $sportVariantStrings = array_map( function (SportPersistVariantWithNrOfFields $sportVariantWithNrOfFields): string {
            return (string)$sportVariantWithNrOfFields->createSportVariant();
        }, $sportVariantsWithNrOfFields );
        $sportVariantsAsString = 'sports "[' . join(',', $sportVariantStrings ) . ']"';
        $pouleStructureAsString = 'poulestructure "[' . $pouleStructure . ']"';
        $selfRefereeAsString = 'selfReferee "'.$selfReferee->value.'"';
        parent::__construct($selfRefereeAsString . ' is not compatible with ' .
            $pouleStructureAsString . ' and ' . $sportVariantsAsString, E_ERROR);
    }
}