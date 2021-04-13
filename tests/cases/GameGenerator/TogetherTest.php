<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\GameAmountVariant;
use SportsPlanning\GameGenerator\Together as TogetherGameeGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class TogetherTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariants = [new GameAmountVariant(GameMode::TOGETHER, 2, 2, 3)];
        $planning = $this->createPlanning(
            $this->createInputNew([7], $sportVariants)
        );

//        $getPlacesDescription = function (array $togetherGamePlaces): string {
//            $description = "";
//            foreach ($togetherGamePlaces as $togetherGamePlace) {
//                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
//            }
//            return $description;
//        };

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new TogetherGameeGenerator();

        $poule = $planning->getPoule(1);
        $sports = array_values($planning->getSports()->toArray());
        $games = $gameGenerator->generate($poule, $sports);
//        foreach( $games as $game ) {
//            $output = "";
//            $places = "places: " . $getPlacesDescription($game->getPlaces()->toArray());
//            echo $output . $places .PHP_EOL;
//
//        }

        // $maxNrOfGamesSim = $calculator->getMaxNrOfGames( $pouleStructure, $sportConfigs, false );
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
        self::assertCount(11, $games);
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }
}
