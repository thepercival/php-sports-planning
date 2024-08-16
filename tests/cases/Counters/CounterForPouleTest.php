<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\VariantWithFields;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\CounterForPoule;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info as RefereeInfo;

class CounterForPouleTest extends TestCase
{
    public function testCountSmallerThanZero(): void
    {
        $poule = $this->getPoule();
        self::expectException(\Exception::class);
        new CounterForPoule($poule, -1);
    }

    public function testGetPoule(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule);
        self::assertSame($poule, $counterForPoule->getPoule());
    }

    public function testIncrement(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 1);
        self::assertCount(2, $counterForPoule->increment());
    }

    public function testDecrement(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 1);
        self::assertCount(0, $counterForPoule->decrement());
    }

    public function testDecrementException(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule);
        self::expectException(\Exception::class);
        $counterForPoule->decrement();
    }

    public function testToString(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 2);
        self::assertSame('1 2x', (string)$counterForPoule);
    }

    private function getPoule(): Poule
    {
        $input = new Input( new Input\Configuration(
            new PouleStructure(3),
            [new VariantWithFields(new AgainstH2h(1,1,1), 1)],
            new RefereeInfo(),
            false
        ));
        return $input->getFirstPoule();
    }
}
