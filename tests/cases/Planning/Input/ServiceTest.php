<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Planning\Input;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\TestHelper\PlanningCreator;

class ServiceTest extends TestCase
{
    use PlanningCreator;

    public function test332(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([3, 2, 2], [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test2(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([2], [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test22SamePoule(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([2, 2], [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test32SamePoule(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([3, 2], [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test33SamePoule(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([3, 3], [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeSamePouleBeAvailable($planning->getPouleStructure(), [$defaultSportConfig])
        );
    }

    public function test3OtherPoule(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([3], [$defaultSportConfig], 0)
        );

        self::assertFalse(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($planning->getPouleStructure())
        );
    }

    public function test22OtherPoule(): void
    {
        $inputService = new InputService();
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([2,2], [$defaultSportConfig], 0)
        );

        self::assertTrue(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($planning->getPouleStructure())
        );
    }
}
