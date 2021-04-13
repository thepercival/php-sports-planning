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

    public function testRewind(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportVariant = $sportsIterator->current();
        self::assertNotNull($sportVariant);
        self::assertGreaterThan(50, $sportsIterator->key());
        self::assertEquals(GameMode::AGAINST, $sportVariant->getGameMode());
        self::assertEquals(2, $sportVariant->getNrOfGamePlaces());
        self::assertEquals(1, $sportVariant->getNrOfFields());
        self::assertEquals(1, $sportVariant->getGameAmount());
    }

    public function testLast(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportVariant = null;
        while ($sportsIterator->current() !== null) {
            $sportVariant = $sportsIterator->current();
            $sportsIterator->next();
        }
        self::assertNotNull($sportVariant);

        self::assertEquals(GameMode::TOGETHER, $sportVariant->getGameMode());
        self::assertEquals(2, $sportVariant->getNrOfGamePlaces());
        self::assertEquals(2, $sportVariant->getNrOfFields());
        self::assertEquals(2, $sportVariant->getGameAmount());
    }

    public function testCount(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);

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
