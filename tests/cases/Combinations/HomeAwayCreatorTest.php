<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Poule;
use SportsPlanning\TestHelper\PlanningCreator;

class HomeAwayCreatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1Pl2(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([2]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(1, $homeAways);
    }

    public function testSimple1VS1Pl3(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS1Pl4(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(6, $homeAways);
    }

    public function testSimple1VS1Pl5(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([5]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(10, $homeAways);
    }

    public function testSimple1VS2Pl3GamesPerPlace1(): void
    {
        $sportVariant = new AgainstSportVariant(1, 2, 0, 1);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(1, $homeAways);
    }

    public function testSimple1VS2Pl3GamesPerPlace2(): void
    {
        $sportVariant = new AgainstSportVariant(1, 2, 0, 2);
        $creator = new HomeAwayCreator($sportVariant);

        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $homeAways = $creator->createForOneH2H($poule);
        $this->homeAwaysToString('', $homeAways);
        self::assertCount(2, $homeAways);
    }

    protected function homeAwaysToString(string $header, array $homeAways): void
    {
        echo '----------- ' . $header . ' -------------' . PHP_EOL;
        foreach ($homeAways as $homeAway) {
            echo $homeAway . PHP_EOL;
        }
    }
}
