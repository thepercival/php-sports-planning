<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\TestHelper\PlanningCreator;

class CalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(4);
        $input = $this->createInput([3, 2, 2], [$sportVariantWithFields]);
        $calculator = new InputCalculator();
        $sportVariantsWithFields = array_values($input->createSportVariantsWithFields()->toArray());
        $pouleStructure = $input->createPouleStructure();

        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch($pouleStructure, $sportVariantsWithFields, false);
        self::assertSame(3, $maxNrOfGamesSim);
    }

//    public function testOneExtra(): void
//    {
//        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(4);
//        $input = $this->createInput([3, 2, 2], [$sportVariantWithFields]);
//        $calculator = new InputCalculator();
//        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch($input, false);
//        self::assertSame(4, $maxNrOfGamesSim);
//    }

    public function testGetMaxNrOfGamesInARow(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([6], [$sportVariantWithFields]);
        $calculator = new InputCalculator();
        $maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($input, false);
        self::assertSame(3, $maxNrOfGamesInARow);
    }
}
