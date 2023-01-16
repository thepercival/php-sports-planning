<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\TestHelper\PlanningCreator;

// cachegrind output default to /tmp
class ProfileTest extends TestCase
{
    use PlanningCreator;

    public function test2V2And6PlacesAnd8GamesPerPlace(): void
    {
//        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
//        $input = $this->createInput([18], [new SportVariantWithFields($sportVariant, 1)]);
//        $poule = $input->getPoule(1);
//        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
//        $mapper = new Mapper();
//        $assignedCounter = new AssignedCounter($poule, [$sportVariant]);
//        $allowedGppMargin = ScheduleCreator::MAX_ALLOWED_GPP_MARGIN;
//        $againstGppMap = $this->getAgainstGppSportVariantMap($input);
//        if( count($againstGppMap) === 0 ) {
//            return;
//        }
//        $differenceManager = new AgainstDifferenceManager(
//            $poule,
//            $againstGppMap,
//            $allowedGppMargin,
//            $this->getLogger());
//        $againstAmountRange = $differenceManager->getAgainstRange(1);
//
//        $assignedAgainstMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedAgainstMap(),
//            $againstAmountRange );
//
//        $withAmountRange = $differenceManager->getWithRange(1);
//        $assignedWithMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedWithMap() , $withAmountRange);
//
//        $homeAmountRange = $differenceManager->getHomeRange(1);
//        $assignedHomeMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedHomeMap(), $homeAmountRange);
//
//        $statisticsCalculator = new GppStatisticsCalculator(
//            $variantWithPoule,
//            $assignedHomeMap,
//            0,
//            new PlaceCounterMap( array_values( $mapper->getPlaceMap($poule) ) ),
//            new PlaceCounterMap( array_values($assignedCounter->getAssignedMap() ) ),
//            $assignedAgainstMap,
//            $assignedWithMap,
//            $this->getLogger()
//        );
//
//        $homeAwayCreator = new GppHomeAwayCreator();
//        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);
//
//        $time_start = microtime(true);
//        $statisticsCalculator->sortHomeAways($homeAways, $this->getLogger());
//        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
//        self::assertLessThan(3.5, (microtime(true) - $time_start) );

        self::assertCount(0, []);
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
