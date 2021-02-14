<?php


namespace SportsPlanning\Tests\Planning\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportConfig;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\TestHelper\PlanningCreator;

class ServiceTest extends TestCase
{
    use PlanningCreator;

    public function test332()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([3, 2, 2], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test2()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([2], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test22SamePoule()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([2, 2], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test32SamePoule()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([3, 2], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test33SamePoule()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([3, 3], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test3OtherPoule()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([3], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($planning->getPouleStructure())
        );
    }

    public function test22OtherPoule()
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput([2,2], GameMode::AGAINST, [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($planning->getPouleStructure())
        );
    }
}
