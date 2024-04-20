<?php

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsHelpers\SportRange;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Output\BatchOutput as BatchOutput;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsPlanning\Resource\ResourceType;
use SportsPlanning\Input;
use SportsPlanning\Planning\Filter as PlanningFilter;

class InputOutput extends OutputHelper
{
    private PlanningOutput $planningOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->planningOutput = new PlanningOutput($logger);
    }

    public function output(Input $input/*, string $prefix = null, string $suffix = null, int $colorNr = -1*/): void
    {
        $planningFilter = new PlanningFilter(
            PlanningBase\Type::BatchGames, null, null, null
        );

        $this->planningOutput->outputInputConfig($input->createConfiguration());
        $filteredPlannings = $input->getFilteredPlannings($planningFilter);
        foreach ($filteredPlannings as $filteredPlanning) {
            $prefix = '    ';
            $equalBatchGames = $filteredPlanning->getBatchGamesType() === PlanningBase\BatchGamesType::RangeIsZero ? '*' : ' ';
            $prefix .= substr($filteredPlanning->getState()->value, 0, 1 ) . ' ' . $equalBatchGames . ' ';

            $color = $this->getColor($filteredPlanning->getState());
            $this->planningOutput->output($filteredPlanning, false, $prefix, null, $color);

            $gamesInARowPlannings = $filteredPlanning->getGamesInARowPlannings();
            foreach ($gamesInARowPlannings as $gamesInARowPlanning) {
                $prefix = '    ';
                $prefix .= substr($filteredPlanning->getState()->value, 0, 1 ) . '   ';
                $color = $this->getColor($gamesInARowPlanning->getState());
                $this->planningOutput->output($gamesInARowPlanning, false, $prefix, null, $color);
            }
        }
    }

    public function getColor(PlanningState $state): Color|null {
        $color = null;
        if( $state === PlanningState::Succeeded ) {
            $color = Color::Green;
        } elseif ($state === PlanningState::Failed) {
            $color = Color::Red;
        } else if ($state === PlanningState::TimedOut) {
            $color = Color::Yellow;
        }
        return $color;
    }
//
//    public function outputInputConfig(InputConfiguration $inputConfiguration, string $prefix = null, string $suffix = null): void
//    {
//        $output = $this->getInputConfigurationAsString($inputConfiguration, $prefix, $suffix);
//        $this->logger->info($output);
//    }
//
//    public function getInputConfigurationAsString(InputConfiguration $inputConfiguration,
//                                                  string $prefix = null, string $suffix = null): string
//    {
//        return ($prefix ?? '') . $inputConfiguration->getName() . ($suffix ?? '');
//    }
//
//    /**
//     * @param array<int,array<string,GameCounter>> $planningTotals
//     */
//    protected function outputTotals(array $planningTotals): void
//    {
//        foreach ($planningTotals as $totalsType => $gameCounters) {
//            $name = '';
//            if ($totalsType === ResourceType::Fields->value) {
//                $name = 'fields';
//            } else {
//                if ($totalsType === ResourceType::Referees->value) {
//                    $name = 'referees';
//                } else {
//                    if ($totalsType === ResourceType::RefereePlaces->value) {
//                        $name = 'refereeplaces';
//                    }
//                }
//            }
//            $this->logger->info($this->getPlanningTotalAsString($name, $gameCounters));
//        }
//    }
//
//    /**
//     * @param string $name
//     * @param array<string,GameCounter> $gameCounters
//     * @return string
//     */
//    protected function getPlanningTotalAsString(string $name, array $gameCounters): string
//    {
//        $retVal = "";
//        foreach ($gameCounters as $gameCounter) {
//            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ", ";
//        }
//        return $name . " => " . $retVal;
//    }
}