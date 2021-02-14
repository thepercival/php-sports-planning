<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\SportConfig as SportConfig;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Validator\GameAssignments as GameAssignmentsValidator;

class Output extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(Planning $planning, bool $withInput, string $prefix = null, string $suffix = null): void
    {
        $this->outputHelper($planning, $withInput, false, false, $prefix, $suffix);
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
        string $suffix = null
    ): void {
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->min . '->' . $planning->getNrOfBatchGames()->max
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeout ' . $planning->getTimeoutSeconds();
        if ($withInput) {
            $output = $this->getInputAsString($planning->getInput()) . ', ' . $output;
        }
        $this->logger->info($prefix . $output . $suffix);
        if ($withGames) {
            $batchOutput = new BatchOutput($this->logger);
            $batchOutput->output($planning->createFirstBatch());
        }
        if ($withTotals) {
            $assignmentValidator = new GameAssignmentsValidator($planning);
            $this->outputTotals($assignmentValidator->getCounters());
        }
    }

    public function outputInput(Input $input, string $prefix = null, string $suffix = null): void
    {
        $output = $this->getInputAsString($input, $prefix, $suffix);
        $this->logger->info($output);
    }

    public function getInputAsString(Input $input, string $prefix = null, string $suffix = null): string
    {
        $sports = array_map(function (SportConfig $sportConfig): string {
            return '' . $sportConfig->getNrOfFields();
        }, $input->getSportConfigs() );
        $output = 'id ' . $input->getId() . ' => structure [' . implode(
                '|',
                $input->getPouleStructure()->toArray()
            ) . ']'
            . ', sports [' . implode(',', $sports) . ']'
            . ', referees ' . $input->getNrOfReferees()
            . ', selfRef ' . $input->getSelfReferee();
        return $prefix . $output . $suffix;
    }

    protected function outputTotals(array $planningTotals)
    {
        /** @var GameCounter[] $gameCounters */
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
     * @param array|GameCounter[] $gameCounters
     * @return string
     */
    protected function getPlanningTotalAsString(string $name, array $gameCounters)
    {
        $retVal = "";
        foreach ($gameCounters as $gameCounter) {
            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ", ";
        }
        return $name . " => " . $retVal;
    }
}
