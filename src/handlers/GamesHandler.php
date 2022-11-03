<?php

namespace Ordness\CTP\handlers;

use Ordness\CTP\Core;
use Ordness\CTP\objects\Game;

abstract class GamesHandler
{
    public static array $games = [];

    public static function addGame(Game $game): void
    {
        self::$games[$game->getId()] = $game;
        Core::getInstance()->getLogger()->info("[GAMES] Game {$game->getId()} added !");
    }

    public static function removeGame(Game $game): void
    {
        unset(self::$games[$game->getId()]);
        Core::getInstance()->getLogger()->info("[GAMES] Game {$game->getId()} removed !");
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

    public static function isInGame(string $player): ?Game
    {
        foreach (self::getGames() as $game) {
            if (in_array($player, $game->getPlayers())) {
                return $game;
            }
        }
        return null;
    }

    /**
     * @return Game[]
     */
    public static function getGames(): array
    {
        return self::$games;
    }

}