<?php

namespace SportsPlanning\Sports;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

class SportWithNrOfFields implements \Stringable
{
    public function __construct(
        public readonly AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport,
        public readonly int $nrOfFields )
    {
    }

    public function __toString(): string
    {
        if( $this->sport instanceof TogetherSport) {
            $sport = 'together(' . ($this->sport->getNrOfGamePlaces() ?? 'null') . ')';
        } else {
            $sport = 'against(' . $this->sport->nrOfHomePlaces . 'vs' . $this->sport->nrOfAwayPlaces . ')';
        }
        return $sport . ' f(' . $this->nrOfFields . ')';
    }

}