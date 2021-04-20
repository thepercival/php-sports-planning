<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Place\Range as PlaceRange;
use SportsPlanning\Input\Iterator as InputIterator;
use SportsHelpers\SportRange;
use SportsHelpers\SelfReferee;
use SportsPlanning\TestHelper\PlanningCreator;

class IteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind(): void
    {
        $inputIterator = new InputIterator(
            new PlaceRange(2, 6, new SportRange(2, 6)),
            new SportRange(1, 3),
            new SportRange(0, 3),
            new SportRange(1, 3),
            new SportRange(1, 2)
        );

        $planningInput = $inputIterator->current();
        self::assertNotNull($planningInput);
        // self::assertGreaterThan(30, $inputIterator->key());
        self::assertEquals([2], $planningInput->createPouleStructure()->toArray());
        self::assertCount(0, $planningInput->getReferees());
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

    public function testCount(): void
    {
        $inputIterator = new InputIterator(
            new PlaceRange(2, 6, new SportRange(2, 6)),
            new SportRange(1, 3),
            new SportRange(0, 3),
            new SportRange(1, 3),
            new SportRange(1, 2)
        );

        $nrOfPossibilities = 0;
        while ($inputIterator->valid()) {
            // echo $inputIterator->key() . PHP_EOL;
            $nrOfPossibilities++;
            $inputIterator->next();
        }
        $inputIterator->next(); // should do nothing
        self::assertFalse($inputIterator->valid());
        self::assertEquals(918, $nrOfPossibilities);
    }
}
