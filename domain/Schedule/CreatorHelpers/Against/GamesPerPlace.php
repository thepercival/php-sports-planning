<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\GameRound\Creator\Against\GamesPerPlace as AgainstGppGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Schedule\CreatorHelpers\Against as AgainstHelper;

class GamesPerPlace extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param array<int, AgainstGpp> $sportVariantMap
     * @param AssignedCounter $assignedCounter
     * @param AgainstGppDifferenceManager $againstGppDifferenceManager,
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $sportVariantMap,
        AssignedCounter $assignedCounter,
        AgainstGppDifferenceManager $againstGppDifferenceManager,
        int|null $nrOfSecondsBeforeTimeout
    ): void
    {
        $homeAwayCreator = new GppHomeAwayCreator();

        foreach ($sportVariantMap as $sportNr => $sportVariant) {
            $sportSchedule = new SportSchedule($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $poule,
                $sportVariant,
                $homeAwayCreator,
                $assignedCounter,
                $againstGppDifferenceManager->getDifference($sportNr),
                $nrOfSecondsBeforeTimeout
            );

            $this->createGames($sportSchedule, $gameRound);
            $assignedCounter->assignHomeAways($gameRound->getAllHomeAways());
        }
    }




}
