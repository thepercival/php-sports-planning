<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Output\Combinations\HomeAwayOutput as HomeAwayOutput;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;

class ScheduleCyclePartAgainstOutput extends OutputHelper
{
    private HomeAwayOutput $homeAwayOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->homeAwayOutput = new HomeAwayOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        ScheduleCyclePartAgainstOneVsOne $cycle,
        bool                             $showGameRoundHeaderLine,
        string                           $title = null,
        int                              $max = null,
        int                              $min = null
    ): void {
        if ($title !== null) {
            $this->logger->info('------ title: ' . $title . ' -------------');
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->outputHelper($cycle->getFirst(), $showGameRoundHeaderLine, $min, $max);
    }

    protected function outputHelper(
        ScheduleCyclePartAgainstOneVsOne $cycle,
        bool                             $showGameRoundHeaderLine,
        int|null                         $min = null,
        int|null                         $max = null
    ): void {
        if ($min !== null && $cycle->getNumber() < $min) {
            $nextGameRound = $cycle->getNext();
            if ($nextGameRound !== null) {
                $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
            }
            return;
        }
        if ($max !== null && $cycle->getNumber() > $max) {
            return;
        }
        if( $showGameRoundHeaderLine) {
            $this->logger->info('------ gameround ' . $cycle->getNumber() . ' -------------');
        }
        $this->outputHomeAways($cycle->getGamesAsHomeAways());
        $nextGameRound = $cycle->getNext();
        if ($nextGameRound !== null) {
            $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
        }
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param ScheduleCyclePartAgainstOneVsOne|null $cycle
     * @param string|null $header
     * @return void
     */
    public function outputHomeAways(
        array                                 $homeAways,
        ScheduleCyclePartAgainstOneVsOne|null $cycle = null,
        string|null                           $header = null): void
    {
        if ($header !== null) {
            $this->logger->info($header);
        }
        $prefix = ''; // $gameRound->getNumber() . ' : ';
        foreach ($homeAways as $homeAway) {
            $this->homeAwayOutput->output($homeAway, $cycle, $prefix);
        }
    }
}
