<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\RefereeInfo;

final class RefereeInfoTest extends TestCase
{
    public function testRefereeInfoSelfReferee(): void
    {
        $selfRefereeInfo = new SelfRefereeInfo(SelfReferee::SamePoule);
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo($selfRefereeInfo);
        self::assertEquals($selfRefereeInfo, $refereeInfo->selfRefereeInfo);
        self::assertEquals(0, $refereeInfo->nrOfReferees);
    }

    public function testRefereeInfoNrOfReferees(): void
    {
        $refereeInfo = RefereeInfo::fromNrOfReferees(12);
        self::assertNull($refereeInfo->selfRefereeInfo);
        self::assertEquals(12, $refereeInfo->nrOfReferees);
    }

}
