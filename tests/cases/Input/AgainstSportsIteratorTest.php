<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Input\AgainstSportsIterator;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstSportsIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportVariantWithFields = $sportsIterator->current();
        self::assertNotNull($sportVariantWithFields);
        $sportVariant = $sportVariantWithFields->getSportVariant();
        self::assertInstanceOf(AgainstSportVariant::class, $sportVariant);
        self::assertGreaterThan(50, $sportsIterator->key());
        self::assertEquals(GameMode::Against, $sportVariant->getGameMode());
        self::assertEquals(2, $sportVariant->getNrOfGamePlaces());
        self::assertEquals(1, $sportVariantWithFields->getNrOfFields());
        self::assertEquals(1, $sportVariant->getNrOfH2H());
    }

    public function testLast(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportVariantWithFields = null;
        while ($sportsIterator->current() !== null) {
            $sportVariantWithFields = $sportsIterator->current();
            // echo $sportVariantWithFields . PHP_EOL;
            $sportsIterator->next();
        }
        self::assertNotNull($sportVariantWithFields);
        $sportVariant = $sportVariantWithFields->getSportVariant();
        self::assertInstanceOf(AgainstSportVariant::class, $sportVariant);

        self::assertEquals(GameMode::Against, $sportVariant->getGameMode());
        self::assertEquals(4, $sportVariant->getNrOfGamePlaces());
        self::assertEquals(2, $sportVariantWithFields->getNrOfFields());
        self::assertEquals(0, $sportVariant->getNrOfH2H());
        self::assertEquals(1, $sportVariant->getNrOfGamesPerPlace());
    }

    public function testCount(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

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
