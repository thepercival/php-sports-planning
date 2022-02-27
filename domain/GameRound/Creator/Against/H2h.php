<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

class H2h extends AgainstCreator
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstH2h $sportVariant,
        AssignedCounter $assignedCounter
    ): AgainstGameRound {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);

        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedAgainstMap = $assignedCounter->getAssignedAgainstMap();
        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();
        $homeAwayCreator = $this->getHomeAwayCreator($poule, $sportVariant);
        $homeAways = $homeAwayCreator->createForOneH2H();
        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $homeAways,
                $homeAways,
                $this->getAssignedSportCounters($poule),
                $assignedMap,
                $assignedWithMap,
                $assignedAgainstMap,
                $assignedHomeMap,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    protected function getHomeAwayCreator(Poule $poule, AgainstH2h $sportVariant): H2hHomeAwayCreator
    {
        return new H2hHomeAwayCreator($poule, $sportVariant);
    }
}
