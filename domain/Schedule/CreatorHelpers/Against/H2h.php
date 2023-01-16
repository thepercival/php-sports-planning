<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against\H2h as AgainstH2hGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Schedule\CreatorHelpers\Against as AgainstHelper;

class H2h extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param array<int, AgainstH2h> $againstH2hVariants
     * @param AssignedCounter $assignedCounter
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $againstH2hVariants,
        AssignedCounter $assignedCounter,
        AgainstDifferenceManager $againstGppDifferenceManager
    ): void
    {
        $homeAwayCreator = new H2hHomeAwayCreator();
        foreach ($againstH2hVariants as $sportNr => $againstH2h) {
            $sportSchedule = new SportSchedule($schedule, $sportNr, $againstH2h->toPersistVariant());

            $gameRoundCreator = new AgainstH2hGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $poule,
                $againstH2h,
                $homeAwayCreator,
                $assignedCounter,
                $againstGppDifferenceManager->getHomeRange($sportNr)
            );

            $this->createGames($sportSchedule, $gameRound);
        }
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }



}
