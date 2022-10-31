<?php

namespace Ordness\CTP\objects;

final class Game
{
    private ?Map $map;

    public function __construct(private int $id, private Team $blue, private Team $red)
    {
    }

    /**
     * @return Map|null
     */
    public function getMap(): ?Map
    {
        return $this->map;
    }

    /**
     * @return Team
     */
    public function getBlue(): Team
    {
        return $this->blue;
    }

    /**
     * @return Team
     */
    public function getRed(): Team
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function addPlayer(int $team_color, string $username): void
    {
        if ($team_color === $this->blue->getColor()->toRGBA()) {
            $this->blue->addPlayer($username);
        } else {
            $this->red->addPlayer($username);
        }
    }

}