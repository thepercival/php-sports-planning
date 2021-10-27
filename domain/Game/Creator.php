<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Sport;

class Creator
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     */
    public function createGames(Planning $planning, array $schedules): void
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            foreach ($planning->getInput()->getSports() as $sport) {
                $sportSchedule = $this->getSportSchedule($schedules, $poule, $sport);
                $this->createSportGames($planning, $poule, $sport, $sportSchedule);
            }
        }
    }

    /**
     * @param list<Schedule> $schedules
     * @param Poule $poule
     * @param Sport $sport
     * @return SportSchedule
     */
    protected function getSportSchedule(array $schedules, Poule $poule, Sport $sport): SportSchedule
    {
        $nrOfPlaces = $poule->getPlaces()->count();
        foreach ($schedules as $schedule) {
            if ($schedule->getNrOfPlaces() !== $nrOfPlaces) {
                continue;
            }
            foreach ($schedule->getSportSchedules() as $sportSchedule) {
                if ($sportSchedule->getNumber() === $sport->getNumber()) {
                    return $sportSchedule;
                }
            }
        }
        throw new \Exception('could not find sport-gameround-schedule for nfOfPlace: ' . $nrOfPlaces . ', and sport: "' . $sport->createVariant() . '"', E_ERROR);
    }

    protected function createSportGames(
        Planning $planning,
        Poule $poule,
        Sport $sport,
        SportSchedule $sportSchedule
    ): void {
        $sportVariant = $sport->createVariant();
        $defaultField = $sport->getField(1);
        foreach ($sportSchedule->getGames() as $gameRoundGame) {
            if ($sportVariant instanceof AgainstSportVariant) {
                $game = new AgainstGame($planning, $poule, $defaultField, $gameRoundGame->getGameRoundNumber());
                foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                    $sidePlaces = $gameRoundGame->getSidePlaces($poule, $side);
                    foreach ($sidePlaces as $place) {
                        new AgainstGamePlace($game, $place, $side);
                    }
                }
            } else {
                $game = new TogetherGame($planning, $poule, $defaultField);
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $place = $poule->getPlace($gameRoundGamePlace->getNumber());
                    new TogetherGamePlace($game, $place, $gameRoundGamePlace->getGameRoundNumber());
                }
            }
        }
    }
}
