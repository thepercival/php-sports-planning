<?php

namespace SportsPlanning\Tests\Combinations\StatisticsCalculator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\Input;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\TestHelper\PlanningCreator;

class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;

    public function testSortHomeAway(): void {

        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
        $input = $this->createInput([18], [new SportVariantWithFields($sportVariant, 1)]);
        $poule = $input->getPoule(1);
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $mapper = new Mapper();
        $assignedCounter = new AssignedCounter($poule, [$sportVariant]);
        $allowedGppMargin = ScheduleCreator::MAX_ALLOWED_GPP_MARGIN;
        $againstGppMap = $this->getAgainstGppSportVariantMap($input);
        if( count($againstGppMap) === 0 ) {
            return;
        }
        $differenceManager = new AgainstGppDifferenceManager(
            $poule,
            $againstGppMap,
            $allowedGppMargin,
            $this->getLogger());
        $againstAmountRange = $differenceManager->getAgainstRange(1);

        $assignedAgainstMap = new RangedPlaceCombinationCounterMap(
            new PlaceCombinationCounterMap( $assignedCounter->getAssignedAgainstMap() ),
            $againstAmountRange->getMin(),
            $againstAmountRange->getMax()
        );

        $withAmountRange = $differenceManager->getWithRange(1);
        $assignedWithMap = new RangedPlaceCombinationCounterMap(
            new PlaceCombinationCounterMap( $assignedCounter->getAssignedWithMap() ),
            $withAmountRange->getMin(),
            $withAmountRange->getMax()
        );

        $statisticsCalculator = new GppStatisticsCalculator(
            $variantWithPoule,
            $assignedCounter->getAssignedHomeMap(),
            0,
            new PlaceCounterMap( array_values( $mapper->getPlaceMap($poule) ) ),
            new PlaceCounterMap( array_values($assignedCounter->getAssignedMap() ) ),
            $assignedAgainstMap,
            $assignedWithMap,
            $assignedCounter->assignAgainstGppSportsEqually(),
            $againstAmountRange,
            $withAmountRange,
            $this->getLogger()
        );

        $homeAwayCreator = new GppHomeAwayCreator();
        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);

        $time_start = microtime(true);
        $statisticsCalculator->sortHomeAways($homeAways, $this->getLogger());
        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
        self::assertTrue((microtime(true) - $time_start) < 2.5);
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param Poule $poule
     * @param AgainstGpp $sportVariant
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        Poule $poule,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = (new AgainstGppWithPoule($poule, $sportVariant));
        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($variantWithPoule));
        }
        return $homeAways;
    }

    /**
     * @param Input $input
     * @return array<int, AgainstGpp>
     */
    protected function getAgainstGppSportVariantMap(Input $input): array
    {
        $map = [];
        foreach( $input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            if( $sportVariant instanceof AgainstGpp) {
                $map[$sport->getNumber()] = $sportVariant;
            }
        }
        return $map;
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}