<?php

declare(strict_types=1);

namespace SportsPlanning\Referee;

use SportsHelpers\SelfReferee;

class Info implements \Stringable
{
    public SelfReferee $selfReferee;
    public int $nrOfReferees = 0;

    public function __construct(SelfReferee|int $selfRefereeOrNrOfReferees)
    {
        if ($selfRefereeOrNrOfReferees instanceof SelfReferee) {
            $this->selfReferee = $selfRefereeOrNrOfReferees;
            $this->nrOfReferees = 0;
        } else {
            $this->selfReferee = SelfReferee::Disabled;
            $this->nrOfReferees = $selfRefereeOrNrOfReferees;
        }
    }

    public function __toString()
    {
        return $this->nrOfReferees . ':' . $this->getSelfRefereeAsString();
    }

    protected function getSelfRefereeAsString(): string
    {
        if ($this->selfReferee === SelfReferee::Disabled) {
            return '';
        } elseif ($this->selfReferee === SelfReferee::OtherPoules) {
            return 'OP';
        } elseif ($this->selfReferee === SelfReferee::SamePoule) {
            return 'SP';
        }
        return '?';
    }
}
