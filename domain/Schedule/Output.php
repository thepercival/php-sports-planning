<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Psr\Log\LoggerInterface;

use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Schedule;

class Output extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<Schedule> $schedules
     * @param int|null $sportNumber
     */
    public function output(array $schedules, int $sportNumber = null): void
    {
        foreach ($schedules as $schedule) {
            $name = new Name(array_values($schedule->createSportVariants()->toArray()));
            $this->logger->info('-- schedule => nrOfPlaces: ' . $schedule->getNrOfPlaces() . ' , name: "' . $name . '"');
            foreach ($schedule->getSportSchedules() as $sportSchedule) {
                if ($sportNumber !== null && $sportNumber !== $sportSchedule->getNumber()) {
                    continue;
                }
                $this->logger->info('-- -- sportschedule => sportNr: ' . $sportSchedule->getNumber() . ' , variant: "' . $sportSchedule->createVariant() . '"');
                foreach ($sportSchedule->getGames() as $gameRoundGame) {
                    $this->logger->info('        ' . $gameRoundGame);
                }
            }
        }
    }
}
