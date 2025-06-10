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
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PlanningOrchestrationTest extends TestCase
{

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        ));
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 0);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        ));
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 1);
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
        $orchestration = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        ));
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 1);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        ));
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 1);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 2);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $orchestration->getBestPlanning(null));
    }
}
