<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\GameGenerator;

class StrategyCalculatorTest extends TestCase
{
    /*public function testOnlySingle(): void
    {
        $singleSportVariant1 = new SingleSportVariant(3, 2);
        $singleSportVariant2 = new SingleSportVariant(3, 2);

        $calculator = new GameGenerator\StrategyCalculator();

        $strategy = $calculator->calculate([$singleSportVariant1,$singleSportVariant2]);
        self::assertEquals(GameGenerator\Strategy::Incremental, $strategy);
    }

    public function testOnlyAllInOneGame(): void
    {
        $allInOneGameSportVariant1 = new AllInOneGameSportVariant(2);
        $allInOneGameSportVariant2 = new AllInOneGameSportVariant(2);

        $calculator = new GameGenerator\StrategyCalculator();

        $strategy = $calculator->calculate([$allInOneGameSportVariant1,$allInOneGameSportVariant2]);
        self::assertEquals(GameGenerator\Strategy::Incremental, $strategy);
    }

    public function testSingleAndAllInOneGame(): void
    {
        $singleSportVariant = new SingleSportVariant(3, 2);
        $allInOneGameSportVariant = new AllInOneGameSportVariant(2);

        $calculator = new GameGenerator\StrategyCalculator();

        $strategy = $calculator->calculate([$singleSportVariant,$allInOneGameSportVariant]);
        self::assertEquals(GameGenerator\Strategy::Static, $strategy);
    }*/

    public function testAgainst(): void
    {
        $againstSportVariant = new AgainstSportVariant(1, 1, 1, 0);

        $calculator = new GameGenerator\StrategyCalculator();

        $strategy = $calculator->calculate([$againstSportVariant]);
        self::assertEquals(GameGenerator\Strategy::Static, $strategy);
    }

    public function testAgainstMixed(): void
    {
        $againstMixedSportVariant = new AgainstSportVariant(2, 2, 0, 1);

        $calculator = new GameGenerator\StrategyCalculator();

        $strategy = $calculator->calculate([$againstMixedSportVariant]);
        self::assertEquals(GameGenerator\Strategy::Static, $strategy);
    }
}
