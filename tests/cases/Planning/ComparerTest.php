<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Planning;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\TestHelper\PlanningCreator;

final class ComparerTest extends TestCase
{
    use PlanningCreator;

    public function testNrOfBatchesBetterCurrent(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(2);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testNrOfBatchesBetterHistorical(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(4);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testEqual(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertSame(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testGamesInARowEqualBatcheGamesRangeBetterCurrent(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 3), 0);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 1, 3), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testGamesInARowEqualBatcheGamesMinBetterCurrent(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 3, 3), 0);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testEqualMaxGamesInARowGreaterThanZero(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 1);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertSame(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterCurrent(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 1);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterHistorical(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterCurrentNoZeroes(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 1);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 2, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterHistoricalNoZeroes(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 2);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testIntegrationBestCurrent(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(2);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningBase|HistoricalBestPlanning $first, PlanningBase|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $planning);
    }
    public function testIntegrationEqual(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningBase|HistoricalBestPlanning $first, PlanningBase|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $historicalBestPlanning);

        $plannings = [$planning];
        array_push( $plannings, $historicalBestPlanning );
        uasort($plannings, function (PlanningBase|HistoricalBestPlanning $first, PlanningBase|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $planning);
    }

    public function testIntegrationCurrentSecond(): void
    {
        $orchestration = $this->createOrchestration([4]);
        $planning = new PlanningBase(
            $orchestration, new SportRange( 2, 2), 0);
        $planning->setNrOfBatches(4);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningBase|HistoricalBestPlanning $first, PlanningBase|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $historicalBestPlanning);
    }
}
