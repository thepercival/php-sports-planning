<?php

namespace SportsPlanning\Tests\Schedules;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;

class ScheduleWithNrOfPlacesTest extends TestCase
{
    public function testGetNrOfPlaces(): void
    {
        $nrOfPlaces = 5;
        $sports = [
            new AgainstOneVsOne()
        ];
        $schedule = new ScheduleWithNrOfPlaces($nrOfPlaces, $sports);
        self::assertSame(5, $schedule->nrOfPlaces );
    }

//    public function testMultipleException(): void
//    {
//        $nrOfPlaces = 5;
//        $sports = [
//            new AgainstTwoVsTwo(), new AgainstTwoVsTwo()
//        ];
//        self::expectException(\Exception::class);
//        new ScheduleWithNrOfPlaces($nrOfPlaces, $sports);
//    }

    public function testCreateJson(): void
    {
        $nrOfPlaces = 5;
        $sports = [
            new AgainstOneVsOne()
        ];
        $schedule = new ScheduleWithNrOfPlaces($nrOfPlaces, $sports);
        self::assertSame('{"nrOfPlaces":5,"sports":[{"nrOfHomePlaces":1,"nrOfAwayPlaces":1}]}', $schedule->createJson() );
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
