<?php

declare(strict_types=1);

namespace SportsPlanning\Referee;

use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;

class Info implements \Stringable
{
    public SelfRefereeInfo $selfRefereeInfo;
    public int $nrOfReferees = 0;

    public function __construct(SelfRefereeInfo|int|null $selfRefereeInfoOrNrOfReferees = null)
    {
        if ($selfRefereeInfoOrNrOfReferees instanceof SelfRefereeInfo) {
            $this->selfRefereeInfo = $selfRefereeInfoOrNrOfReferees;
        } else {
            if ( $selfRefereeInfoOrNrOfReferees !== null ) {
                $this->nrOfReferees = $selfRefereeInfoOrNrOfReferees;
            }
            $this->selfRefereeInfo = new SelfRefereeInfo(SelfReferee::Disabled, 0);
        }
    }

    public function __toString()
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
