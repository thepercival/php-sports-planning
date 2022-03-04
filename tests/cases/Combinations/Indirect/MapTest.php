<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations\Indirect;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\Indirect\Map;
use SportsPlanning\TestHelper\PlanningCreator;

class MapTest extends TestCase
{
    use PlanningCreator;

    public function testBaseDepth1(): void
    {
        $input = $this->createInput([4]);
        $poule = $input->getFirstPoule();
        $map = new Map();
        $place1 = $poule->getPlace(1);
        $place2 = $poule->getPlace(2);

        $newMap = $map->add($place1, $place2);

        self::assertSame(1, $newMap->count($place1, $place2, 1));
        self::assertSame(1, $newMap->count($place2, $place1, 1));
    }

    public function testBaseDepth2(): void
    {
        $input = $this->createInput([4]);
        $poule = $input->getFirstPoule();
        $map = new Map();
        $place1 = $poule->getPlace(1);
        $place2 = $poule->getPlace(2);
        $place3 = $poule->getPlace(3);

        $newMap = $map->add($place1, $place2);
        $newMap2 = $newMap->add($place1, $place3);

        self::assertSame(1, $newMap2->count($place2, $place3, 2));
    }

    public function testTwoConnectionsDepth2(): void
    {
        $input = $this->createInput([4]);
        $poule = $input->getFirstPoule();
        $map = new Map();
        $place1 = $poule->getPlace(1);
        $place2 = $poule->getPlace(2);
        $place3 = $poule->getPlace(3);
        $place4 = $poule->getPlace(4);

        $newMap1 = $map->add($place1, $place2);
        $newMap2 = $newMap1->add($place1, $place3);
        $newMap3 = $newMap2->add($place4, $place2);
        $newMap4 = $newMap3->add($place4, $place3);

        self::assertSame(2, $newMap4->count($place2, $place3, 2));
    }
}
