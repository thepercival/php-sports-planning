<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum Type: int
{
    case BatchGames = 1;
    case GamesInARow = 2;
}
