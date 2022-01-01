<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validator\GameAssignments as GameAssignmentsValidator;
use SportsPlanning\Resource\GameCounter;

class Output extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(Planning $planning, bool $withInput, string $prefix = null, string $suffix = null, int $colorNr = -1): void
    {
        $this->outputHelper($planning, $withInput, false, false, $prefix, $suffix, $colorNr);
    }

    public function outputWithGames(
        Planning $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, true, false, $prefix, $suffix);
    }

    public function outputWithTotals(
        Planning $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, false, true, $prefix, $suffix);
    }

    protected function outputHelper(
        Planning $planning,
        bool $withInput,
        bool $withGames,
        bool $withTotals,
        string $prefix = null,
        string $suffix = null,
        int $colorNr = -1
    ): void {
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->getMin()
            . '->' . $planning->getNrOfBatchGames()->getMax()
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeout ' . $planning->getTimeoutSeconds();
        if ($withInput) {
            $output = $this->getInputAsString($planning->getInput()) . ', ' . $output;
        }
        $output = $this->getColored($colorNr, ($prefix ?? '') . $output . ($suffix ?? ''));
        $this->logger->info($output);
        if ($withGames) {
            $batchOutput = new BatchOutput($this->logger);
            $batchOutput->output($planning->createFirstBatch());
        }
        if ($withTotals) {
            $assignmentValidator = new GameAssignmentsValidator($planning);
            $this->outputTotals($assignmentValidator->getCounters());
        }
    }

    public function outputInput(PlanningInput $input, string $prefix = null, string $suffix = null): void
    {
        $output = $this->getInputAsString($input, $prefix, $suffix);
        $this->logger->info($output);
    }

    public function getInputAsString(PlanningInput $input, string $prefix = null, string $suffix = null): string
    {
        return ($prefix ?? '') . $input->getUniqueString() . ($suffix ?? '');
    }

    /**
     * @param array<int,array<string,GameCounter>> $planningTotals
     */
    protected function outputTotals(array $planningTotals): void
    {
        foreach ($planningTotals as $totalsType => $gameCounters) {
            $name = '';
            if ($totalsType === GameAssignmentsValidator::FIELDS) {
                $name = 'fields';
            } else {
                if ($totalsType === GameAssignmentsValidator::REFEREES) {
                    $name = 'referees';
                } else {
                    if ($totalsType === GameAssignmentsValidator::REFEREEPLACES) {
                        $name = 'refereeplaces';
                    }
                }
            }
            $this->logger->info($this->getPlanningTotalAsString($name, $gameCounters));
        }
    }

    /**
     * @param string $name
     * @param array<string,GameCounter> $gameCounters
     * @return string
     */
    protected function getPlanningTotalAsString(string $name, array $gameCounters): string
    {
        $retVal = "";
        foreach ($gameCounters as $gameCounter) {
            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ", ";
        }
        return $name . " => " . $retVal;
    }
}
