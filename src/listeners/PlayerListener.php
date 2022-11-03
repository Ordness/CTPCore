<?php

namespace Ordness\CTP\listeners;

use Ordness\CTP\handlers\GamesHandler;
use Ordness\CTP\objects\Team;
use Ordness\CTP\tasks\CanJoin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;

final class PlayerListener implements Listener
{
    public function onJoin(PlayerJoinEvent $event): void
    {
        $event->getPlayer()->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        $event->getPlayer()->setGamemode(GameMode::ADVENTURE());
        $event->getPlayer()->getInventory()->clearAll();
        $event->getPlayer()->getArmorInventory()->clearAll();
        $event->getPlayer()->getHungerManager()->setFood(20);
        $event->getPlayer()->getEffects()->clear();
        Server::getInstance()->getAsyncPool()->submitTask(new CanJoin($event->getPlayer()->getName()));
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $game = GamesHandler::isInGame($event->getPlayer()->getName());
        if ($game) {
            $team_name = in_array($event->getPlayer()->getName(), $game->getBlue()->getPlayers())
                ? Team::getTeamColorName($game->getBlue()->getColor())
                : Team::getTeamColorName($game->getRed()->getColor());
            $team = substr(ucfirst($team_name), 0, 1);
            $color = Team::colorNameToFormat($team_name);
            $event->setFormat("§7($color{$team}§7) §f{$event->getPlayer()->getName()}§7: {$event->getMessage()}");
        } else $event->cancel();
    }

    public function onExhaust(PlayerExhaustEvent $event): void
    {
        $event->cancel();
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        if ($event->getPlayer()->hasPermission(DefaultPermissions::ROOT_OPERATOR)) return;
        $event->cancel();
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        if ($event->getPlayer()->hasPermission(DefaultPermissions::ROOT_OPERATOR)) return;
        $event->cancel();
    }

    public function onPickup(EntityItemPickupEvent $event): void
    {
        if ($event->getEntity() instanceof Player and $event->getEntity()->hasPermission(DefaultPermissions::ROOT_OPERATOR)) return;
        $event->cancel();
    }

    public function onDamage(EntityDamageEvent $event): void {
        if(!match ($event->getCause()){
            $event::CAUSE_ENTITY_ATTACK => true,
            default => false
        }){
            $event->cancel();
        }
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $event->setDeathMessage("");
        $event->setKeepInventory(true);
        $event->setKeepXp(true);
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $game = GamesHandler::isInGame($event->getPlayer()->getName());
        if (!$game) return;
        if (in_array($event->getPlayer()->getName(), $game->getBlue()->getPlayers())) {
            $game->getBlue()->equip($event->getPlayer()->getName());
            $event->setRespawnPosition($game->getBlue()->getSpawnPoint());
        }
    }
}