<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Identifiable;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsOne;
use SportsPlanning\Sports\Plannable\PlannableAgainstOneVsTwo;
use SportsPlanning\Sports\Plannable\PlannableAgainstTwoVsTwo;
use SportsPlanning\Sports\Plannable\PlannableSport;
use SportsPlanning\Sports\Plannable\PlannableTogetherSport;

class Field extends Identifiable implements Resource
{
    protected int $number;

    public function __construct(protected PlannableAgainstOneVsOne|PlannableAgainstOneVsTwo|PlannableAgainstTwoVsTwo|PlannableTogetherSport $sport, int $number = null)
    {
        if( $number === null ) {
            $number = $sport->getFields()->count() + 1;
        }
        $this->number = $number;
        $sport->getFields()->add($this);
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueIndex(): string
    {
        return $this->getSport()->getNumber() . '.' . $this->getNumber();
    }

    public function getSport(): PlannableAgainstOneVsOne|PlannableAgainstOneVsTwo|PlannableAgainstTwoVsTwo|PlannableTogetherSport
    {
        return $this->sport;
    }
}
