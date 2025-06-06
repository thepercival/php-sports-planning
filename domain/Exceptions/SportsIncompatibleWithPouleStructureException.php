<?php

namespace SportsPlanning\Exceptions;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

final class SportsIncompatibleWithPouleStructureException  extends \Exception
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     */
    public function __construct(PouleStructure $pouleStructure, array $sports) {
        $sportDescriptions = array_map(
            function (AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport): string {
                if( $sport instanceof TogetherSport) {
                    return "t(".($sport->getNrOfGamePlaces() ?? 'null').")";
                }
                return "a(".$sport->nrOfHomePlaces."vs".$sport->nrOfAwayPlaces.")";
            }, $sports );
        $sportVariantsAsString = 'sports "[' . join(',', $sportDescriptions ) . ']"';
        $pouleStructureJson = json_encode($pouleStructure);
        $pouleStructureJson = $pouleStructureJson === false ? '?' : $pouleStructureJson;
        $pouleStructureAsString = 'poulestructure: '. $pouleStructureJson;
        parent::__construct(' maxNrOfGamePlaces > nrOfPoulePlaces with ' .
            $pouleStructureAsString . ' and ' . $sportVariantsAsString, E_ERROR);
    }
}