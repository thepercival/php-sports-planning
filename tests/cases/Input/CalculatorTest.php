<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class CalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testMaxNrOfGamesPerBatchSimple(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(4);
        $refereeInfo = new RefereeInfo(0);
        $input = $this->createInput([3, 2, 2], [$sportVariantWithFields], $refereeInfo);
        $calculator = new InputCalculator();
        $sportVariantsWithFields = array_values($input->createSportVariantsWithFields()->toArray());
        $pouleStructure = $input->createPouleStructure();

        $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch(
            $pouleStructure,
            $sportVariantsWithFields,
            new RefereeInfo(0),
        );
        self::assertSame(3, $maxNrOfGamesSim);
    }

    public function testMaxNrOfGamesInARow6Places2Fields(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(2);
        $input = $this->createInput([6], [$sportVariantWithFields]);
        $calculator = new InputCalculator();
        $maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($input, false);
        self::assertSame(3, $maxNrOfGamesInARow);
    }

    //     [8] - [
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) -
//     gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARow5Sports8Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
        ];
        $refereeInfo = new RefereeInfo(0);
        $input = $this->createInput(
            [8],
            $sportVariantsWithFields,
            $refereeInfo
        );
        $calculator = new InputCalculator();
        $maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($input, false);
        self::assertSame(7, $maxNrOfGamesInARow);
    }

    // [10,10,10,10] - [against(1vs1) h2h:gpp=>2:0 f(20)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARowH2h210Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(20, 1, 1, 2)
        ];
        $refereeInfo = new RefereeInfo(0);
        $input = $this->createInput(
            [10, 10, 10, 10],
            $sportVariantsWithFields,
            $refereeInfo
        );
        $calculator = new InputCalculator();
        $maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($input, false);
        self::assertSame(9, $maxNrOfGamesInARow);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields1Referee(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(3);
        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(6),
            [$sportVariantWithFields],
            new RefereeInfo(1)
        );
        self::assertSame(1, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesInARow6Places3Fields2Referees(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(3);
        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(6),
            [$sportVariantWithFields],
            new RefereeInfo(2)
        );
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    // [2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple(): void
    {
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(2);

        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(2, 2, 2, 2),
            [$sportVariantsWithFields],
            new RefereeInfo(SelfReferee::SamePoule)
        );

        self::assertSame(1, $maxNrOfGamesPerBatch);
    }

    // [3,2,2,2] - [allinone gpp=>1 f(2)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesPerBatchAllInOneGame(): void
    {
        $calculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $calculator->getMaxNrOfGamesPerBatch(
            new PouleStructure(3, 2, 2, 2),
            [new VariantWithFields(new AllInOneGameSportVariant(1), 2)],
            new RefereeInfo(SelfReferee::Disabled)
        );

        self::assertSame(2, $maxNrOfGamesPerBatch);
    }
}
