<?php

namespace SportsPlanning\Exceptions;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

final class SelfRefereeIncompatibleWithPouleStructureException extends \Exception
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param SelfReferee $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array     $sportVariantsWithFields,
        SelfReferee $selfReferee
    ) {
        $sportVariantStrings = array_map( function (SportVariantWithFields $sportVariantWithFields): string {
            return (string)$sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields );
        $sportVariantsAsString = 'sports "[' . join(',', $sportVariantStrings ) . ']"';
        $pouleStructureAsString = 'poulestructure "[' . ((string)$pouleStructure) . ']"';
        $selfRefereeAsString = 'selfReferee "'.$selfReferee->value.'"';
        parent::__construct($selfRefereeAsString . ' is not compatible with ' .
            $pouleStructureAsString . ' and ' . $sportVariantsAsString, E_ERROR);
    }
}