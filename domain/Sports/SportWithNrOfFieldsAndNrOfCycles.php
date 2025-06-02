<?php

namespace SportsPlanning\Sports;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsOneWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

class SportWithNrOfFieldsAndNrOfCycles extends SportWithNrOfFields
{
    public function __construct(
        AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport,
        int $nrOfFields,
        public readonly int $nrOfCycles)
    {
        parent::__construct($sport, $nrOfFields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): TogetherSportWithNrOfPlaces|AgainstOneVsOneWithNrOfPlaces|AgainstOneVsTwoWithNrOfPlaces|AgainstTwoVsTwoWithNrOfPlaces {
        if( $this->sport instanceof TogetherSport) {
            return new TogetherSportWithNrOfPlaces($nrOfPlaces, $this->sport);
        } else if( $this->sport instanceof AgainstOneVsOne) {
            return new AgainstOneVsOneWithNrOfPlaces($nrOfPlaces, $this->sport);
        } else if( $this->sport instanceof AgainstOneVsTwo) {
            return new AgainstOneVsTwoWithNrOfPlaces($nrOfPlaces, $this->sport);
        }
        return new AgainstTwoVsTwoWithNrOfPlaces($nrOfPlaces, $this->sport);
    }

    public function createSportWithNrOfCycles(): SportWithNrOfCycles {
        return new SportWithNrOfCycles($this->sport, $this->nrOfCycles);
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields($this->sport, $this->nrOfFields);
    }
}