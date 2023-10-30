<?php

declare(strict_types=1);

namespace SportsPlanning\Resource;

enum ResourceType: int
{
    case Fields = 1;
    case Referees = 2;
    case RefereePlaces = 4;
}