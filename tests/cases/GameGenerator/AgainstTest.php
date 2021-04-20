<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportRange;
use SportsPlanning\GameGenerator\GameMode\Against as AgainstGameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\Planning;

class AgainstTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $planning = new Planning($this->createInput([5]), new SportRange(1, 1), 0);

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new AgainstGameGenerator($planning);

        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $gameGenerator->generate($poule, $sports);
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
        self::assertCount(10, $planning->getAgainstGames());
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }
}
