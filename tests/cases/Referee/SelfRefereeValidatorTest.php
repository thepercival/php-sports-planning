<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Referee;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class SelfRefereeValidatorTest extends TestCase
{
    use PlanningCreator;

    public function test332(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput(
            [3, 2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test2(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput([2], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $validator->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test22SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test32SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput(
            [3, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test33SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput(
            [3, 3],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test3OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput([3], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $validator->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }

    public function test22OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $input = $this->createInput(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }
}
