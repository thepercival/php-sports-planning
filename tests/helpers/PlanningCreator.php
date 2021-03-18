<?php
declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsHelpers\SportRange;
use SportsHelpers\SportConfig;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Input;
use SportsHelpers\PouleStructure;
use SportsPlanning\SelfReferee;

trait PlanningCreator
{
    /**
     * @return list<SportConfig>
     */
    protected function getDefaultSportConfigs(): array
    {
        return [$this->getDefaultSportConfig()];
    }

    /**
     * @return SportConfig
     */
    protected function getDefaultSportConfig(int $gameMode = null): SportConfig
    {
        return new SportConfig($gameMode ?? GameMode::AGAINST, 2, 2, 1);
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getDefaultNrOfReferees(): int
    {
        return 2;
    }

    /**
     * @param list<int> $structureConfig
     * @param list<SportConfig>|null $sportConfigs
     * @param int|null $nrOfReferees
     * @param int|null $selfReferee
     * @return Input
     */
    protected function createInputNew(
        array $structureConfig,
        array $sportConfigs = null,
        int $nrOfReferees = null,
        int $selfReferee = null
    ) {
        if ($sportConfigs === null) {
            $sportConfigs = $this->getDefaultSportConfigs();
        }
        if ($nrOfReferees === null) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if ($selfReferee === null) {
            $selfReferee = SelfReferee::DISABLED;
        }
        return new Input(
            new PouleStructure($structureConfig),
            $sportConfigs,
            $nrOfReferees,
            $selfReferee
        );
    }

    protected function createPlanning(Input $input, SportRange $range = null): Planning
    {
        if ($range === null) {
            $range = new SportRange(1, 1);
        }
        $planning = new Planning($input, $range, 0);
        $gameCreator = new GameCreator($this->getLogger());
        if (Planning::STATE_SUCCEEDED !== $gameCreator->createGames($planning)) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
