<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;

final readonly class PlanningRefereeInfo extends RefereeInfo implements \Stringable
{
    public function __construct(RefereeInfo|SelfRefereeInfo|int|null $selfRefereeInfoOrNrOfReferees = null)
    {
        if( $selfRefereeInfoOrNrOfReferees instanceof RefereeInfo) {
            if( $selfRefereeInfoOrNrOfReferees->selfRefereeInfo !== null) {
                parent::__construct($selfRefereeInfoOrNrOfReferees->selfRefereeInfo);
            } else {
                parent::__construct(null,$selfRefereeInfoOrNrOfReferees->nrOfReferees);
            }
        } else if( $selfRefereeInfoOrNrOfReferees instanceof SelfRefereeInfo) {
            parent::__construct($selfRefereeInfoOrNrOfReferees);
        }
        else if ( $selfRefereeInfoOrNrOfReferees === null )
        {
            parent::__construct();
        } else
        {
            parent::__construct(null,$selfRefereeInfoOrNrOfReferees);
        }
    }


    #[\Override]
    public function __toString(): string
    {
        return $this->nrOfReferees . ':' . $this->getSelfRefereeAsString();
    }

    protected function getSelfRefereeAsString(): string
    {
        $key = '';
        switch ($this->selfRefereeInfo?->selfReferee) {
            case SelfReferee::OtherPoules:
                $key = 'OP';
                break;
            case SelfReferee::SamePoule:
                $key = 'SP';
        }
        if ( $this->selfRefereeInfo !== null ) {
            $key .= '(' . $this->selfRefereeInfo->nrOfSimSelfRefs . ')';
        }
        return $key;
    }
}
