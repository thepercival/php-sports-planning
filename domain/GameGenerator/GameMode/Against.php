<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator\GameMode;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\AgainstSerie;
use SportsPlanning\Combinations\GameRound;
use SportsPlanning\Combinations\GameRoundCreator;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Field;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\AgainstSerie\OneVersusOne as OneVersusOneSerie;
use SportsPlanning\Combinations\AgainstSerie\OneVersusTwo as OneVersusTwoSerie;
use SportsPlanning\Combinations\AgainstSerie\TwoVersusTwo as TwoVersusTwoSerie;
use SportsPlanning\GameGenerator\GameMode as GameModeGameGenerator;

class Against implements GameModeGameGenerator
{
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     */
    public function generate(Poule $poule, array $sports): void
    {
        foreach ($sports as $sport) {
            $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new Exception('only against-sport-variant accepted', E_ERROR);
            }

            $totalNrOfGamesPerPlace = $sportVariant->getTotalNrOfGamesPerPlace($poule->getPlaces()->count());
            $nrOfGamesPerPlaceOneH2H = $sportVariant->getNrOfGamesPerPlaceOneH2H($poule->getPlaces()->count());
            $swap = false;
            while ($totalNrOfGamesPerPlace > 0) {
                if ($totalNrOfGamesPerPlace > $nrOfGamesPerPlaceOneH2H) {
                    $nrOfGamesPerPlace = $nrOfGamesPerPlaceOneH2H;
                } else {
                    $nrOfGamesPerPlace = $totalNrOfGamesPerPlace;
                }
                $totalNrOfGamesPerPlace -= $nrOfGamesPerPlaceOneH2H;

                $gameRound = $this->getGameRound($poule, $sportVariant, $nrOfGamesPerPlace);
                $this->gameRoundsToGames($poule, $gameRound, $swap);
                $swap = !$swap;
            }
        }
    }

    protected function getGameRound(Poule $poule, AgainstSportVariant $sportVariant, int $nrOfGamesPerPlace): GameRound
    {
        $homeAwayCreator = new HomeAwayCreator($sportVariant);
        $homeAways = $homeAwayCreator->createForOneH2H($poule);
        $gameRoundCreator = new GameRoundCreator($sportVariant, $this->logger);
        return $gameRoundCreator->createGameRound($poule, $homeAways, $nrOfGamesPerPlace);
    }

//    /**
//     * @param Poule $poule
//     * @param AgainstSportVariant $sportVariant
//     * @param AgainstSerie $againstSerie
//     * @return list<AgainstHomeAway>
//     */
//    protected function getHomeAwaysHelper(
//        Poule $poule,
//        AgainstSportVariant $sportVariant,
//        AgainstSerie $againstSerie
//    ): array {
//        $homeAways = [];
//
//        $nrOfHomeAways = $sportVariant->getTotalNrOfGames($poule->getPlaces()->count());
//        $itNr = 1;
//        while ($nrOfHomeAways > 0) {
//            // $swapHomeAways = $itNr++ % 2 === 0;
//            $serieHomeAways = $againstSerie->getHomeAways($nrOfHomeAways/*, $swapHomeAways*/);
//            $homeAways = array_merge($homeAways, $serieHomeAways);
//            $nrOfHomeAways -= count($serieHomeAways);
//        }
//        return $homeAways;
//    }

//    /**
//     * @param GameRound $gameRound
//     * @return list<AgainstHomeAway>
//     */
//    protected function gameRoundToHomeAways(GameRound $gameRound): array
//    {
//        $homeAways = $gameRound->getHomeAways();
//        while ($gameRound = $gameRound->getNext()) {
//            foreach ($gameRound->getHomeAways() as $homeAway) {
//                array_push($homeAways, $homeAway);
//            }
//        }
//        return $homeAways;
//    }

    /**
     * @param list<Sport> $sports
     * @param int $nrOfH2H
     * @return list<Sport>
     */
    protected function filterSports(array $sports, int $nrOfH2H): array
    {
        return array_values(array_filter($sports, function (Sport $sport) use ($nrOfH2H): bool {
            return $sport->getNrOfH2H() >= $nrOfH2H;
        }));
    }

    public function getSwappedSide(int $side): int
    {
        return $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
    }

    /**
     * @param Poule $poule
     * @param GameRound $gameRound
     * @param bool $swap
     * @throws Exception
     */
    protected function gameRoundsToGames(Poule $poule, GameRound $gameRound, bool $swap): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways($swap) as $homeAway) {
                $game = new AgainstGame($this->planning, $poule, $this->getDefaultField(), 0);
                foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                    foreach ($homeAway->get($side)->getPlaces() as $place) {
                        // $validSide = (($h2HNr % 2) === 1) ? $side : $this->getSwappedSide($side);
                        new AgainstGamePlace($game, $place, $side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
//        $nextPartial = $partial->getNext();
//        if ($nextPartial !== null) {
//            $this->partialToGames($poule, $nextPartial, $h2HNr);
//        }
    }

    protected function getDefaultField(): Field
    {
        if ($this->defaultField === null) {
            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
        }
        return $this->defaultField;
    }
}
