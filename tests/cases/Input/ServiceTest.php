<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class ServiceTest extends TestCase
{
    use PlanningCreator;

    public function test332(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput(
            [3, 2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $inputService->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test2(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput([2], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $inputService->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test22SamePoule(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test32SamePoule(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput(
            [3, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test33SamePoule(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput(
            [3, 3],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test3OtherPoule(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput([3], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }

    public function test22OtherPoule(): void
    {
        $inputService = new InputService();
        $refereeInfo = new RefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2);
        $input = $this->createInput(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }
}
