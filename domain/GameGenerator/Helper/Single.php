<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Field;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\GameGenerator\Helper as GameGeneratorHelper;
use SportsPlanning\GameRound\SingleCreator as SingleGameRoundCreator;
use SportsPlanning\GameRound\GameRoundCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\GameRound\SingleGameRound;
use SportsPlanning\Place;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Single implements GameGeneratorHelper
{
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     */
    public function generate(Poule $poule, array $sports, AssignedCounter $assignedCounter): void
    {
        foreach ($sports as $sport) {
            $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof SingleSportVariant)) {
                throw new \Exception('only single-sport-variant accepted', E_ERROR);
            }
            $this->generateGames($poule, $sportVariant, $assignedCounter);
        }
    }

    protected function generateGames(Poule $poule, SingleSportVariant $sportVariant, AssignedCounter $assignedCounter): void
    {
        $totalNrOfGamesPerPlace = $sportVariant->getTotalNrOfGamesPerPlace($poule->getPlaces()->count());
        $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $totalNrOfGamesPerPlace);
        $this->assignPlaceCombinations($assignedCounter, $gameRound);
        $this->gameRoundsToGames($poule, $gameRound);
    }

    protected function getGameRound(
        Poule $poule,
        SingleSportVariant $sportVariant,
        AssignedCounter $assignedCounter,
        int $nrOfGamesPerPlace
    ): SingleGameRound {
        // (new HomeAwayOutput($this->logger))->outputHomeAways($homeAways);
        /** @var GameRoundCreator<SingleGameRound> $gameRoundCreator */
        $gameRoundCreator = new SingleGameRoundCreator($sportVariant, $this->logger);
        return $gameRoundCreator->createGameRound($poule, $assignedCounter, $nrOfGamesPerPlace);
    }

    /**
     * @param Poule $poule
     * @param SingleGameRound $gameRound
     * @throws Exception
     */
    protected function gameRoundsToGames(Poule $poule, SingleGameRound $gameRound): void
    {
        $placeCounterMap = $this->getPlaceCounterMap($poule);
        while ($gameRound !== null) {
            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
                $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
                foreach ($placeCombination->getPlaces() as $place) {
                    $placeCounter = $placeCounterMap[$place->getNumber()];
                    new TogetherGamePlace($game, $place, $placeCounter->increment());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return list<PlaceCombination>
     */
    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
    {
        /** @var array<int, list<Place>> $combinationsTmp */
        $combinationsTmp = $combinations->toArray();
        return array_values(array_map(
            function (array $places): PlaceCombination {
                return new PlaceCombination($places);
            },
            $combinationsTmp
        ));
    }

    protected function assignPlaceCombinations(AssignedCounter $assignedCounter, SingleGameRound $gameRound): void
    {
        $assignedCounter->assignPlaceCombinations($this->gameRoundsToPlaceCombinations($gameRound));
    }

    /**
     * @param SingleGameRound $gameRound
     * @return list<PlaceCombination>
     */
    protected function gameRoundsToPlaceCombinations(SingleGameRound $gameRound): array
    {
        $placeCombinations = $gameRound->getPlaceCombinations();
        while ($gameRound = $gameRound->getNext()) {
            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
                array_push($placeCombinations, $placeCombination);
            }
        }
        return $placeCombinations;
    }

    /**
     * @param Poule $poule
     * @return array<string|int, PlaceCounter>
     */
    protected function getPlaceCounterMap(Poule $poule): array
    {
        $placeCounterMap = [];
        foreach ($poule->getPlaces() as $place) {
            $placeCounterMap[$place->getNumber()] = new PlaceCounter($place);
        }
        return $placeCounterMap;
    }

    protected function getDefaultField(): Field
    {
        if ($this->defaultField === null) {
            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
        }
        return $this->defaultField;
    }
}
