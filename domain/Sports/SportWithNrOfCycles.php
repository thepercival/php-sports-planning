<?php

namespace SportsPlanning\Sports;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

readonly class SportWithNrOfCycles implements \Stringable
{
    public function __construct(
        public AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport,
        public int $nrOfCycles )
    {
    }

    public function __toString(): string
    {
        if( $this->sport instanceof TogetherSport) {
            $sport = 'together(' . ($this->sport->getNrOfGamePlaces() ?? 'null') . ')';
        } else {
            $sport = 'against(' . $this->sport->nrOfHomePlaces . 'vs' . $this->sport->nrOfAwayPlaces . ')';
        }
        return $sport . ' cy(' . $this->nrOfCycles . ')';
    }

}