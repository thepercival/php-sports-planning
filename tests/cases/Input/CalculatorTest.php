<?php

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructure;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\TestHelper\PlanningCreator;

class CalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple()
    {
        $calculator = new InputCalculator();

        $pouleStructure = new PouleStructure([3,2,2]);
        $sportConfigs = [
            new SportConfig( new SportBase(2), 4, 1 )
        ];
        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch( $pouleStructure, $sportConfigs, false );
        self::assertSame(3, $maxNrOfGamesSim);
    }

    public function testOneExtra()
    {
        $calculator = new InputCalculator();

        $pouleStructure = new PouleStructure([3,3,2]);
        $sportConfigs = [
            new SportConfig( new SportBase(2), 4, 1 )
        ];
        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch( $pouleStructure, $sportConfigs, false );
        self::assertSame(4, $maxNrOfGamesSim);
    }
}
