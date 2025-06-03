<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class PlanningOrchestrationTest extends TestCase
{
    use PlanningCreator;

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput([5],$sportsWithNrOfFieldsAndNrOfCycles,new PlanningRefereeInfo());
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 0);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput([5],$sportsWithNrOfFieldsAndNrOfCycles,new PlanningRefereeInfo());
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(Planning\PlanningState::Failed);
        $planningB->setNrOfBatches(5);

        $bestPlanning = $input->getBestPlanning(null);
        self::assertSame($planningA, $bestPlanning);
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput([5],$sportsWithNrOfFieldsAndNrOfCycles,new PlanningRefereeInfo());
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput([5],$sportsWithNrOfFieldsAndNrOfCycles,new PlanningRefereeInfo());
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 1);
        $planningA->setState(Planning\PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 2);
        $planningB->setState(Planning\PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $input->getBestPlanning(null));
    }
}
