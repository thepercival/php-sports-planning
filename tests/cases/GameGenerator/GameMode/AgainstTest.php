<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator\GameMode;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
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

    public function test1V1Places2H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([2], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places3H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([3], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(6, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places5H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places6H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $planning = new Planning($this->createInput([6], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }


    public function test2V2Places4GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1 /* max = 2 */),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 2 /* max = 2 */),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(2, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace3(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3 /* max = 2 */),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace4(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 4 /* max = 2 */),
        ];
        $planning = new Planning($this->createInput([4], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(4, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places5GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(2, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2Places5GamesPerPlace12(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 12),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2Places6GamesPerPlace30(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 30),
        ];
        $planning = new Planning($this->createInput([6], $sportVariants), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(45, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    // commented for performance reasons
//    public function test2VS2Places7GamesPerPlace60(): void
//    {
//        $sportVariants = [
//            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 60),
//        ];
//        $planning = new Planning($this->createInput([7], $sportVariants), new SportRange(1, 1), 0);
//
//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        //(new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(105, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }
//
//    public function test2VS2Places8GamesPerPlace105(): void
//    {
//        $sportVariants = [
//            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 105),
//        ];
//        $planning = new Planning($this->createInput([7], $sportVariants), new SportRange(1, 1), 0);
//
//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        //(new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(210, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }


    protected function getLogger(): LoggerInterface {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}
