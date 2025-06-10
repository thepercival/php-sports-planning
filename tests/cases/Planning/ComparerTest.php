<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Planning;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class ComparerTest extends TestCase
{

    public function testNrOfBatchesBetterCurrent(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta($orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(2);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testNrOfBatchesBetterHistorical(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta($orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(4);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testEqual(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertSame(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testGamesInARowEqualBatcheGamesRangeBetterCurrent(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 3), 0, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 1, 3), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testGamesInARowEqualBatcheGamesMinBetterCurrent(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);

        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 3, 3), 0, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testEqualMaxGamesInARowGreaterThanZero(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 1, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertSame(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterCurrent(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 1, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterHistorical(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterCurrentNoZeroes(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 1, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 2, 'test', 3);
        self::assertLessThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testMaxNrOfGamesInARowBetterHistoricalNoZeroes(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 2, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 1, 'test', 3);
        self::assertGreaterThan(0, (new Comparer())->compare($planning, $historicalBestPlanning));
    }

    public function testIntegrationBestCurrent(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(2);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningWithMeta|HistoricalBestPlanning $first, PlanningWithMeta|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $planning);
    }
    public function testIntegrationEqual(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $planning = new PlanningWithMeta(
            $orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(3);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningWithMeta|HistoricalBestPlanning $first, PlanningWithMeta|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $historicalBestPlanning);

        $plannings = [$planning];
        array_push( $plannings, $historicalBestPlanning );
        uasort($plannings, function (PlanningWithMeta|HistoricalBestPlanning $first, PlanningWithMeta|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $planning);
    }

    public function testIntegrationCurrentSecond(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningBase = Planning::fromConfiguration($configuration);
        $planning = new PlanningWithMeta($orchestration, new SportRange( 2, 2), 0, $planningBase);
        $planning->setNrOfBatches(4);
        $historicalBestPlanning = new HistoricalBestPlanning(
            $orchestration, new SportRange( 2, 2), 0, 'test', 3);

        $plannings = [$historicalBestPlanning];
        array_push( $plannings, $planning );
        uasort($plannings, function (PlanningWithMeta|HistoricalBestPlanning $first, PlanningWithMeta|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $firstPlanning = array_shift($plannings);
        self::assertSame($firstPlanning, $historicalBestPlanning);
    }
}
