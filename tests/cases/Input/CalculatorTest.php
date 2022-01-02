<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\TestHelper\PlanningCreator;

class CalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(4);
        $input = $this->createInput([3, 2, 2], [$sportVariantWithFields], null, 0);
        $calculator = new InputCalculator();
        $sportVariantsWithFields = array_values($input->createSportVariantsWithFields()->toArray());
        $pouleStructure = $input->createPouleStructure();

        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch(
            $pouleStructure,
            $sportVariantsWithFields,
            $input->getReferees()->count(),
            false
        );
        self::assertSame(3, $maxNrOfGamesSim);
    }

    public function testMaxNrOfGamesInARow6Places2Fields(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([6], [$sportVariantWithFields]);
        $calculator = new InputCalculator();
        $maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($input, false);
        self::assertSame(3, $maxNrOfGamesInARow);
    }

    public function testMaxNrOfGamesInARow6Places3Fields1Referee(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(3);
        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(6),
            [$sportVariantWithFields],
            1,
            false
        );
        self::assertSame(1, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesInARow6Places3Fields2Referees(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(3);
        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(6),
            [$sportVariantWithFields],
            2,
            false
        );
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    // [2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:OP
    public function testOtherPouleSimple(): void
    {
        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(2);

        $input = $this->createInput(
            [2, 2, 2, 2],
            [$sportVariantsWithFields],
            GamePlaceStrategy::EquallyAssigned,
            0,
            SelfReferee::OtherPoules
        );

        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            $input->createPouleStructure(),
            [$sportVariantsWithFields],
            0,
            true
        );

        self::assertSame(2, $maxNrOfGamesPerBatch);
    }
}
