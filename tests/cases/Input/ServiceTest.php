<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Planning;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\TestHelper\PlanningCreator;

class ServiceTest extends TestCase
{
    use PlanningCreator;

    public function test332(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([3, 2, 2], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertTrue(
            $inputService->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportVariantWithFields->getSportVariant()])
        );
    }

    public function test2(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([2], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertFalse(
            $inputService->canSelfRefereeBeAvailable($input->createPouleStructure(), [$sportVariantWithFields->getSportVariant()])
        );
    }

    public function test22SamePoule(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([2, 2], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportVariantWithFields->getSportVariant()])
        );
    }

    public function test32SamePoule(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([3, 2], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertFalse(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportVariantWithFields->getSportVariant()])
        );
    }

    public function test33SamePoule(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([3, 3], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertTrue(
            $inputService->canSelfRefereeSamePouleBeAvailable($input->createPouleStructure(), [$sportVariantWithFields->getSportVariant()])
        );
    }

    public function test3OtherPoule(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([3], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertFalse(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }

    public function test22OtherPoule(): void
    {
        $inputService = new InputService();
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(2);
        $input = $this->createInput([2, 2], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, 0);

        self::assertTrue(
            $inputService->canSelfRefereeOtherPoulesBeAvailable($input->createPouleStructure())
        );
    }
}
