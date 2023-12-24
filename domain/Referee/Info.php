<?php

declare(strict_types=1);

namespace SportsPlanning\Referee;

use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;

class Info extends RefereeInfo implements \Stringable
{
    public function __construct(SelfRefereeInfo|int|null $selfRefereeInfoOrNrOfReferees = null)
    {
        parent::__construct($selfRefereeInfoOrNrOfReferees);
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
