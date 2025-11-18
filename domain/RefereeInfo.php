<?php

namespace SportsPlanning;

use SportsHelpers\SelfRefereeInfo;

/**
 * @psalm-api
 */
readonly class RefereeInfo
{
//    public SelfRefereeInfo $selfRefereeInfo;
//    public int $nrOfReferees;

//    public function __construct(SelfRefereeInfo|int|null $selfRefereeInfoOrNrOfReferees = null)
//    {
//        $nrOfReferees = 0;
//        if ($selfRefereeInfoOrNrOfReferees instanceof SelfRefereeInfo) {
//            $this->selfRefereeInfo = $selfRefereeInfoOrNrOfReferees;
//        } else {
//            if ($selfRefereeInfoOrNrOfReferees !== null) {
//                $nrOfReferees = $selfRefereeInfoOrNrOfReferees;
//            }
//            $this->selfRefereeInfo = new SelfRefereeInfo(SelfReferee::Disabled, 0);
//        }
//        $this->nrOfReferees = $nrOfReferees;
//    }

    private function __construct(
        public SelfRefereeInfo|null $selfRefereeInfo = null,
        public array $configReferees = [] )
    {

    }

    public static function fromSelfRefereeInfo(SelfRefereeInfo $selfRefereeInfo): self {
        return new self($selfRefereeInfo);
    }

    /**
     * @param non-empty-list<ConfigReferee> $configReferees
     * @return self
     */
    public static function fromConfigReferees(array $configReferees): self {
        return new self(null, $configReferees);
    }
}