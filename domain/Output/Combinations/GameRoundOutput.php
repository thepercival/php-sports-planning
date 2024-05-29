<?php

declare(strict_types=1);

namespace SportsPlanning\Output\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Combinations\HomeAway as HomeAwayBase;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Output\Combinations\HomeAwayOutput as HomeAwayOutput;

class GameRoundOutput extends OutputHelper
{
    private HomeAwayOutput $homeAwayOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->homeAwayOutput = new HomeAwayOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        AgainstGameRound $gameRound,
        bool $showGameRoundHeaderLine,
        string $title = null,
        int $max = null,
        int $min = null
    ): void {
        if ($title !== null) {
            $this->logger->info('------ title: ' . $title . ' -------------');
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->outputHelper($gameRound->getFirst(), $showGameRoundHeaderLine, $min, $max);
    }

    protected function outputHelper(
        AgainstGameRound $gameRound,
        bool $showGameRoundHeaderLine,
        int|null $min = null,
        int|null $max = null
    ): void {
        if ($min !== null && $gameRound->getNumber() < $min) {
            $nextGameRound = $gameRound->getNext();
            if ($nextGameRound !== null) {
                $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
            }
            return;
        }
        if ($max !== null && $gameRound->getNumber() > $max) {
            return;
        }
        if( $showGameRoundHeaderLine) {
            $this->logger->info('------ gameround ' . $gameRound->getNumber() . ' -------------');
        }
        $this->outputHomeAways($gameRound->getHomeAways());
        $nextGameRound = $gameRound->getNext();
        if ($nextGameRound !== null) {
            $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
        }
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @param AgainstGameRound|null $gameRound
     * @return void
     */
    public function outputHomeAways(array $homeAways, AgainstGameRound|null $gameRound = null, string|null $header = null): void
    {
        if ($header !== null) {
            $this->logger->info($header);
        }
        $prefix = ''; // $gameRound->getNumber() . ' : ';
        foreach ($homeAways as $homeAway) {
            $this->homeAwayOutput->output($homeAway, $gameRound, $prefix);
        }
    }
}
