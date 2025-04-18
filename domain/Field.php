<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Identifiable;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsOne;
use SportsPlanning\Sports\Plannable\AgainstPlannableOneVsTwo;
use SportsPlanning\Sports\Plannable\AgainstPlannableTwoVsTwo;
use SportsPlanning\Sports\Plannable\PlannableSport;
use SportsPlanning\Sports\Plannable\TogetherPlannableSport;

class Field extends Identifiable implements Resource
{
    protected int $number;

    public function __construct(protected AgainstPlannableOneVsOne|AgainstPlannableOneVsTwo|AgainstPlannableTwoVsTwo|TogetherPlannableSport $sport, int $number = null)
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

    public function getSport(): AgainstPlannableOneVsOne|AgainstPlannableOneVsTwo|AgainstPlannableTwoVsTwo|TogetherPlannableSport
    {
        return $this->sport;
    }
}
