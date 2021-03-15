<?php

namespace SportsPlanning\Tests\GameGenerator;

use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\Game\Calculator;
use SportsPlanning\GameGenerator\Together as TogetherGameeGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class TogetherTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator;

    public function testSimple()
    {
        $sportConfigs = [new SportConfig(new SportBase(GameMode::TOGETHER, 2), 2, 3)];
        $planning = $this->createPlanning(
            $this->createInputNew([7], $sportConfigs)
        );

        $getPlacesDescription = function (array $togetherGamePlaces): string {
            $description = "";
            foreach ($togetherGamePlaces as $togetherGamePlace) {
                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
            }
            return $description;
        };

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new TogetherGameeGenerator();

        $poule = $planning->getPoule(1);
        $games = $gameGenerator->generate($poule, $planning->getSportAndConfigs());
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
