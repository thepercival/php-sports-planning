<?php

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\Place\Range as PlaceRange;
use SportsPlanning\Input\Iterator as InputIterator;
use SportsHelpers\Range;
use SportsPlanning\SelfReferee;
use SportsPlanning\TestHelper\PlanningCreator;

class IteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind()
    {
        $inputIterator = new InputIterator(
            new PlaceRange(2, 6, new Range(2, 6)),
            new Range(1, 3),
            new Range(0, 3),
            new Range(1, 3),
            new Range(1, 2)
        );

        $planningInput = $inputIterator->current();
        self::assertNotNull($planningInput);
        self::assertGreaterThan(30, $inputIterator->key());
        self::assertEquals([2], $planningInput->getPouleStructure()->toArray());
        self::assertEquals(0, $planningInput->getNrOfReferees());
        self::assertEquals(SelfReferee::DISABLED, $planningInput->getSelfReferee());
    }

//    public function testLast()
//    {
//        $rangeNrOfFields = new Range(1, 2);
//        $rangeGameAmount = new Range(1, 2);
//        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);
//
//        $sportConfig = null;
//        while ($sportsIterator->current() !== null) {
//            $sportConfig = $sportsIterator->current();
//            $sportsIterator->next();
//        }
//        self::assertNotNull($sportConfig);
//
//        self::assertEquals(GameMode::TOGETHER, $sportConfig->getSport()->getGameMode());
//        self::assertEquals(2, $sportConfig->getSport()->getNrOfGamePlaces());
//        self::assertEquals(2, $sportConfig->getNrOfFields());
//        self::assertEquals(2, $sportConfig->getGameAmount());
//    }

    public function testCount()
    {
        $inputIterator = new InputIterator(
            new PlaceRange(2, 6, new Range(2, 6)),
            new Range(1, 3),
            new Range(0, 3),
            new Range(1, 3),
            new Range(1, 2)
        );

        $planningInput = null;
        $nrOfPossibilities = 0;
        while ($inputIterator->valid()) {
            // echo $inputIterator->key() . PHP_EOL;
            $nrOfPossibilities++;
            $inputIterator->next();
        }
        $inputIterator->next(); // should do nothing
        self::assertFalse($inputIterator->valid());
        self::assertEquals(744, $nrOfPossibilities);
    }
}