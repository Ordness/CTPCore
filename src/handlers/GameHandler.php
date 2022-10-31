<?php

namespace Ordness\CTP\handlers;

use Ordness\CTP\objects\Game;

abstract class GameHandler
{
    public static array $games = [];

    public static function addGame(Game $game): void
    {
        self::$games[$game->getId()] = $game;
    }

    public static function removeGame(Game $game): void
    {
        unset(self::$games[$game->getId()]);
    }

    public static function getGame(int $id): ?Game
    {
        foreach (self::$games as $game) {
            if ($game->getId() === $id) {
                return $game;
            }
        }
        return null;
    }

    public static function getGames(): array
    {
        return self::$games;
    }

}