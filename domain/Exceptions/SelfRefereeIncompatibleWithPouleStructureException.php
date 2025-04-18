<?php

namespace SportsPlanning\Exceptions;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

class SelfRefereeIncompatibleWithPouleStructureException extends \Exception
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport> $sports
     * @param SelfReferee $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sports,
        SelfReferee $selfReferee
    ) {
        $sportDescriptions = array_map(
            function (AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport): string {
                if( $sport instanceof TogetherSport) {
                    return "t(".($sport->getNrOfGamePlaces() ?? 'null').")";
                }
                return "a(".$sport->nrOfHomePlaces."vs".$sport->nrOfAwayPlaces.")";
        }, $sports );
        $sportVariantsAsString = 'sports "[' . join(',', $sportDescriptions ) . ']"';
        $pouleStructureAsString = 'poulestructure "[' . $pouleStructure . ']"';
        $selfRefereeAsString = 'selfReferee "'.$selfReferee->value.'"';
        parent::__construct($selfRefereeAsString . ' is not compatible with ' .
            $pouleStructureAsString . ' and ' . $sportVariantsAsString, E_ERROR);
    }
}