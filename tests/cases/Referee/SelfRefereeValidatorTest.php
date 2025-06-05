<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Referee;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

final class SelfRefereeValidatorTest extends TestCase
{
    use PlanningCreator;

    public function test332(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration(
            [3, 2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test2(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration([2], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $validator->canSelfRefereeBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test22SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test32SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration(
            [3, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test33SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration(
            [3, 3],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test3OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration([3], [$sportWithNrOfFieldsAndNrOfCycles], $refereeInfo);

        self::assertFalse(
            $validator->canSelfRefereeOtherPoulesBeAvailable($orchestration->configuration->pouleStructure)
        );
    }

    public function test22OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $refereeInfo = new PlanningRefereeInfo();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = $this->createOrchestration(
            [2, 2],
            [$sportWithNrOfFieldsAndNrOfCycles],
            $refereeInfo
        );

        self::assertTrue(
            $validator->canSelfRefereeOtherPoulesBeAvailable($orchestration->configuration->pouleStructure)
        );
    }
}
