<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Planning;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

final class InputTest extends TestCase
{
    use PlanningCreator;

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $input = $this->createInput(
            [5],
            [$sportVariantsWithFields],
            new RefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\State::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 0);
        $planningB->setState(Planning\State::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $input = $this->createInput(
            [5],
            [$sportVariantsWithFields],
            new RefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\State::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(Planning\State::Failed);
        $planningB->setNrOfBatches(5);

        $bestPlanning = $input->getBestPlanning(null);
        self::assertSame($planningA, $bestPlanning);
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $input = $this->createInput(
            [5],
            [$sportVariantsWithFields],
            new RefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(Planning\State::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(Planning\State::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $input = $this->createInput(
            [5],
            [$sportVariantsWithFields],
            new RefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 1);
        $planningA->setState(Planning\State::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 2);
        $planningB->setState(Planning\State::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $input->getBestPlanning(null));
    }
}
