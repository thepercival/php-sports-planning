<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator\GameMode;

use SportsHelpers\SportRange;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Planning\Output as PlanningOutput;
use PHPUnit\Framework\TestCase;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\Planning\Validator as PlanningValidator;

class AgainstTest extends TestCase
{
    use PlanningCreator;


    // should be equal result than previous
    public function test1V1Places5H2H0Partials1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 0, 1),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        self::expectException(\Exception::class);
        $gameGenerator->generateUnassignedGames($planning);
    }

    public function test2V2Places5H2H0MaxPartials(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 2),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places5H2H0MaxPlusOnePartials(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3 /* max = 2 */),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places5H2H1Partials1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(5, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces2GamePlaces1vs1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([2], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces3GamePlaces1vs1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([3], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces3GamePlaces1vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 1),
        ];
        $planning = new Planning($this->createInput([3], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces3GamePlaces2vs1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 1, 0, 1),
        ];
        $planning = new Planning($this->createInput([3], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces4GamePlaces1vs1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(6, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces4GamePlaces1vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 3),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces4GamePlaces2vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces5GamePlaces1vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 6),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(30, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces5GamePlaces2vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces5GamePlaces2vs3(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 3, 0, 2),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces6GamePlaces1vs2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 10),
        ];
        $planning = new Planning($this->createInput([6], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(60, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces6GamePlaces2vs2(): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 8);
        $planning = new Planning($this->createInput([6], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(45, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces6GamePlaces2vs3(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 3, 0, 10),
        ];
        $planning = new Planning($this->createInput([6], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(60, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces6GamePlaces3vs3(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 3, 3, 0, 10),
        ];
        $planning = new Planning($this->createInput([6], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces7GamePlaces2vs2(): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 15);
        $planning = new Planning($this->createInput([7], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(105, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testPlaces8GamePlaces2vs2(): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 27 /* 1 h2h */);
//        if( $sportVariant->getSportVariant() instanceof AgainstSportVariant ) {
//            $x = $sportVariant->getSportVariant()->getTotalNrOfGamesOneH2H(8);
//            $y = $sportVariant->getSportVariant()->getNrOfPartialsOneH2H(8);
//        }
        $planning = new Planning($this->createInput([8], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(210, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }
}
