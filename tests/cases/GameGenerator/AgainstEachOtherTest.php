<?php

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\GameGenerator\AgainstEachOther as AgainstEachOtherGameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstEachOtherTest extends TestCase
{
    use PlanningCreator;

    public function testSimple()
    {
        $sportConfigs = [new SportConfig( new SportBase(2), 2, 1 )];
        $planning = $this->createPlanning(
            $this->createInput( [5], SportConfig::GAMEMODE_AGAINSTEACHOTHER, $sportConfigs )
        );

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new AgainstEachOtherGameGenerator();

        $poule = $planning->getPoule(1);
        $games = $gameGenerator->generate( $poule, $sportConfigs );
//        foreach( $games as $game ) {
//            $output = "";
//            $getPlacesDescription = function(PlaceCombination $placeCombination): string {
//                $description = "";
//                foreach( $placeCombination->getPlaces() as $place ) {
//                    $description .= $place->getNumber() . ",";
//                }
//                return $description;
//            };
//            $home = "home: " . $getPlacesDescription($game->getHome());
//            $away = "away: " . $getPlacesDescription($game->getAway());
//            echo $output . $home . $away .PHP_EOL;
//
//        }

        // $maxNrOfGamesSim = $calculator->getMaxNrOfGames( $pouleStructure, $sportConfigs, false );
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
        self::assertCount(10, $games);
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }
}
