<?php
declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\GameAmountVariant as SportGameAmountVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Input;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;

trait PlanningCreator
{
    /**
     * @return list<SportGameAmountVariant>
     */
    protected function getDefaultSportVariants(): array
    {
        return [$this->getDefaultSportVariant()];
    }

    protected function getDefaultSportVariant(int $gameMode = null): SportGameAmountVariant
    {
        return new SportGameAmountVariant($gameMode ?? GameMode::AGAINST, 2, 2, 1);
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
     * @param list<SportGameAmountVariant>|null $sportVariants
     * @param int|null $nrOfReferees
     * @param int|null $selfReferee
     * @return Input
     */
    protected function createInputNew(
        array $structureConfig,
        array $sportVariants = null,
        int $nrOfReferees = null,
        int $selfReferee = null
    ) {
        if ($sportVariants === null) {
            $sportVariants = $this->getDefaultSportVariants();
        }
        if ($nrOfReferees === null) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if ($selfReferee === null) {
            $selfReferee = SelfReferee::DISABLED;
        }
        return new Input(
            new PouleStructure(...$structureConfig),
            $sportVariants,
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
