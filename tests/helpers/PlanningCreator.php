<?php

declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\Range;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Input;
use SportsHelpers\PouleStructure;

trait PlanningCreator {

    /**
     * @return array|SportConfig[]
     */
    protected function getDefaultSportConfigs(): array {
        return [$this->getDefaultSportConfig()];
    }

    /**
     * @return SportConfig
     */
    protected function getDefaultSportConfig(): SportConfig {
        return new SportConfig( new SportBase(2), 2, 1 );
    }

    protected function getLogger(): LoggerInterface {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getDefaultNrOfReferees(): int {
        return 2;
    }

    /**
     * @param array|int[] $structureConfig
     * @param int|null $gameMode
     * @param array|SportConfig[]|null $sportConfigs
     * @param int|null $nrOfReferees
     * @param int|null $selfReferee
     * @return Input
     */
    protected function createInput(
        array $structureConfig,
        int $gameMode = null,
        array $sportConfigs = null,
        int $nrOfReferees = null,
        int $selfReferee = null
    ) {
        if( $gameMode === null ) {
            $gameMode = SportConfig::GAMEMODE_AGAINST;
        }
        if( $sportConfigs === null ) {
            $sportConfigs = $this->getDefaultSportConfigs();
        }
        if( $nrOfReferees === null ) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if( $selfReferee === null ) {
            $selfReferee = Input::SELFREFEREE_DISABLED;
        }
        return new Input(
            new PouleStructure($structureConfig),
            $sportConfigs,
            $gameMode,
            $nrOfReferees,
            $selfReferee
        );
    }

    protected function createPlanning( Input $input, Range $range = null ): Planning
    {
        if( $range === null ){
            $range = new Range(1,1);
        }
        $planning = new Planning( $input, $range, 0 );
        $gameCreator = new GameCreator( $this->getLogger() );
        if (Planning::STATE_SUCCEEDED !== $gameCreator->createGames($planning) ) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}

