<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PlanningOrchestrationTest extends TestCase
{

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planningBase);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planningBase);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planningBase);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planningBase);
        $planningB->setState(Planning\PlanningState::Failed);
        $planningB->setNrOfBatches(5);

        $bestPlanning = $orchestration->getBestPlanning(null);
        self::assertSame($planningA, $bestPlanning);
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planningBase);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planningBase);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planningBase = Planning::fromConfiguration($configuration);
        $orchestration = new PlanningOrchestration($configuration);
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planningBase);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new PlanningWithMeta($orchestration, $batchGamesRange, 2, $planningBase);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $orchestration->getBestPlanning(null));
    }
}
