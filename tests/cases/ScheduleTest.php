<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\SportVariantWithNr;
use SportsPlanning\Sport;

class ScheduleTest extends TestCase
{
    public function testGetNrOfPlaces(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule(
            $nrOfPlaces,
            [
                new SportVariantWithNr( 1, new AgainstH2h(1, 1, 1) )
            ]
        );
        self::assertSame(5, $schedule->getNrOfPlaces() );
    }

    public function testToJsonCustom(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule(
            $nrOfPlaces,
            [
                new SportVariantWithNr( 1, new AgainstH2h(1, 1, 1) )
            ]
        );
        self::assertSame('{"nrOfPlaces":5,"sportVariants":[{"nrOfHomePlaces":1,"nrOfAwayPlaces":1,"nrOfH2h":1}]}', $schedule->toJsonCustom() );
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

    public function testGetSetSucceededMargin(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule(
            $nrOfPlaces,
            [
                new SportVariantWithNr( 1, new AgainstH2h(1, 1, 1) )
            ]
        );
        $schedule->setSucceededMargin(1);
        self::assertSame(1, $schedule->getSucceededMargin() );
    }

    public function testNrOfTimeoutSecondsTried(): void
    {
        $nrOfPlaces = 5;
        $schedule = new Schedule(
            $nrOfPlaces,
            [
                new SportVariantWithNr( 1, new AgainstH2h(1, 1, 1) )
            ]
        );
        $schedule->setNrOfTimeoutSecondsTried(5);
        self::assertSame(5, $schedule->getNrOfTimeoutSecondsTried() );
    }

}
