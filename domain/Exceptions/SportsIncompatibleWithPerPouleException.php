<?php

namespace SportsPlanning\Exceptions;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

class SportsIncompatibleWithPerPouleException  extends \Exception
{
    /**
     * @param PouleStructure $pouleStructure
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
 */
    public function __construct(array $sports) {
        parent::__construct(' perPoule = true than sports can be max 1 (' .
            count($sports) . ')', E_ERROR);
    }
}