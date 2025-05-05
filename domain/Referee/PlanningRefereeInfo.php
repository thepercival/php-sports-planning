<?php

declare(strict_types=1);

namespace SportsPlanning\Referee;

use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;

class PlanningRefereeInfo extends RefereeInfo implements \Stringable
{
    public function __construct(RefereeInfo|SelfRefereeInfo|int|null $selfRefereeInfoOrNrOfReferees = null)
    {
        if( $selfRefereeInfoOrNrOfReferees instanceof RefereeInfo) {
            if( $selfRefereeInfoOrNrOfReferees->nrOfReferees === 0) {
                parent::__construct($selfRefereeInfoOrNrOfReferees->selfRefereeInfo);
            } else {
                parent::__construct($selfRefereeInfoOrNrOfReferees->nrOfReferees);
            }
        } else {
            parent::__construct($selfRefereeInfoOrNrOfReferees);
        }
    }


    public function __toString(): string
    {
        return $this->nrOfReferees . ':' . $this->getSelfRefereeAsString();
    }

    protected function getSelfRefereeAsString(): string
    {
        $key = '';
        switch ($this->selfRefereeInfo->selfReferee) {
            case SelfReferee::OtherPoules:
                $key = 'OP';
                break;
            case SelfReferee::SamePoule:
                $key = 'SP';
        }
        if ( $this->selfRefereeInfo->selfReferee !== SelfReferee::Disabled ) {
            $key .= '(' . $this->selfRefereeInfo->nrIfSimSelfRefs . ')';
        }
        return $key;
    }
}
