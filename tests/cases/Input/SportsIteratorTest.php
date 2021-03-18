<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportRange;
use SportsPlanning\Input\SportsIterator;
use SportsPlanning\TestHelper\PlanningCreator;

class SportsIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind()
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportConfig = $sportsIterator->current();
        self::assertNotNull($sportConfig);
        self::assertGreaterThan(50, $sportsIterator->key());
        self::assertEquals(GameMode::AGAINST, $sportConfig->getGameMode());
        self::assertEquals(2, $sportConfig->getNrOfGamePlaces());
        self::assertEquals(1, $sportConfig->getNrOfFields());
        self::assertEquals(1, $sportConfig->getGameAmount());
    }

    public function testLast()
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportConfig = null;
        while ($sportsIterator->current() !== null) {
            $sportConfig = $sportsIterator->current();
            $sportsIterator->next();
        }
        self::assertNotNull($sportConfig);

        self::assertEquals(GameMode::TOGETHER, $sportConfig->getGameMode());
        self::assertEquals(2, $sportConfig->getNrOfGamePlaces());
        self::assertEquals(2, $sportConfig->getNrOfFields());
        self::assertEquals(2, $sportConfig->getGameAmount());
    }

    public function testCount()
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportConfig = null;
        $nrOfPossibilities = 0;
        while ($sportsIterator->valid()) {
            // echo $sportsIterator->key() . PHP_EOL;
            $nrOfPossibilities++;
            $sportsIterator->next();
        }
        $sportsIterator->next(); // should do nothing
        self::assertFalse($sportsIterator->valid());
        self::assertEquals(12, $nrOfPossibilities);
    }
}
