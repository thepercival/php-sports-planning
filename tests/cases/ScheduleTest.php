<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\ScheduleAgainstOneVsOne;

class ScheduleTest extends TestCase
{
    public function testGetNrOfPlaces(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule($nrOfPlaces);
        $schedule->setSportSchedules([
            new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne() )
            ]
        );
        self::assertSame(5, $schedule->getNrOfPlaces() );
    }

    public function testMultipleException(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule($nrOfPlaces);
        self::expectException(\Exception::class);
        $schedule->setSportSchedules([
                new Schedule\ScheduleAgainstTwoVsTwo($schedule, 1, new AgainstTwoVsTwo() ),
                new Schedule\ScheduleAgainstTwoVsTwo($schedule, 2, new AgainstTwoVsTwo() )
            ]
        );
    }

    public function testToJsonCustom(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule($nrOfPlaces);
        $schedule->setSportSchedules([
                new Schedule\ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne() )
            ]
        );

        self::assertSame('{"nrOfPlaces":5,"sports":[{"nrOfHomePlaces":1,"nrOfAwayPlaces":1}]}', $schedule->toJsonCustom() );
    }

//    public function testGetSportsConfigName(): void
//    {
//        $nrOfPlaces = 5;
//        $schedule = new Schedule(
//            $nrOfPlaces,
//            [
//                new SportVariantWithNr( 1, new AgainstH2h(1, 1, 1) )
//            ]
//        );
//        self::assertSame('[{"nrOfHomePlaces":1,"nrOfAwayPlaces":1,"nrOfH2h":1}]', $schedule->getSportsConfigName() );
//    }

//    public function testGetSetSucceededMargin(): void
//    {
//        $nrOfPlaces = 5;
//        $schedule = new Schedule(
//            $nrOfPlaces,
//            [
//                new SportVariantWithNr( 1, new AgainstOneVsOne(1) )
//            ]
//        );
//        $schedule->setSucceededMargin(1);
//        self::assertSame(1, $schedule->getSucceededMargin() );
//    }
//
//    public function testNrOfTimeoutSecondsTried(): void
//    {
//        $nrOfPlaces = 5;
//        $schedule = new Schedule(
//            $nrOfPlaces,
//            [
//                new SportVariantWithNr( 1, new AgainstOneVsOne( 1) )
//            ]
//        );
//        $schedule->setNrOfTimeoutSecondsTried(5);
//        self::assertSame(5, $schedule->getNrOfTimeoutSecondsTried() );
//    }

}
