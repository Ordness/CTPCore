<?php

namespace Ordness\CTP\objects;

use pocketmine\color\Color;

final class Team
{
    /**
     * @var string[]
     */
    private array $players;

    public function __construct(private Color $color, string ...$players)
    {
        $this->players = $players;
    }

    /**
     * @return Color
     */
    public function getColor(): Color
    {
        return $this->color;
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function addPlayer(string $player): void {
        $this->players[] = $player;
    }
}