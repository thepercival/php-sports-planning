<?php

namespace SportsPlanning\TestHelper;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfigHelper;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Service as PlanningService;
use SportsPlanning\Input;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsHelpers\PouleStructure;

trait PlanningCreator {

    /**
     * @return array|SportConfigHelper[]
     */
    protected function getDefaultSportConfig(): array {
        return $this->createSportConfig( 2 );
    }

    protected function getLogger(): LoggerInterface {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }



    /**
     * @return array|SportConfigHelper[]
     */
    protected function createSportConfig(int $nrOfFields ): array {
        return [ new SportConfigHelper( $nrOfFields, 2 )];
    }

    protected function getDefaultNrOfReferees(): int {
        return 2;
    }

    /**
     * @param array|int[] $structureConfig
     * @param array|SportConfigHelper[]|null $sportConfig
     * @param int|null $nrOfReferees
     * @param bool|null $teamup
     * @param int|null $selfReferee
     * @param int|null $nrOfHeadtohead
     * @return Input
     */
    protected function createInput(
        array $structureConfig,
        array $sportConfig = null,
        int $nrOfReferees = null,
        bool $teamup = null,
        int $selfReferee = null,
        int $nrOfHeadtohead = null
    ) {
        if( $sportConfig === null ) {
            $sportConfig = $this->getDefaultSportConfig();
        }
        if( $nrOfReferees === null ) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if( $teamup === null ) {
            $teamup = false;
        }
        if( $selfReferee === null ) {
            $selfReferee = Input::SELFREFEREE_DISABLED;
        }
        if( $nrOfHeadtohead === null ) {
            $nrOfHeadtohead = 1;
        }
        return new Input(
            new PouleStructure($structureConfig),
            $sportConfig,
            $nrOfReferees,
            $teamup,
            $selfReferee,
            $nrOfHeadtohead
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
            throw new \Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}

