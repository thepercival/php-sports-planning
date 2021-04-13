<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\GameAmountVariant as SportGameAmountVariant;
use SportsPlanning\GameGenerator\Against as AgainstGameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariants = [new SportGameAmountVariant(GameMode::AGAINST, 2, 2, 1)];
        $planning = $this->createPlanning(
            $this->createInputNew([5], $sportVariants)
        );

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new AgainstGameGenerator();

        $poule = $planning->getPoule(1);
        $sports = array_values($planning->getSports()->toArray());
        $games = $gameGenerator->generate($poule, $sports);
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
