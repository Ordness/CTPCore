<?php

namespace Ordness\CTP\listeners;

use Ordness\CTP\tasks\CanJoin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;

final class PlayerListener implements Listener
{
    public function onJoin(PlayerJoinEvent $event): void
    {
        Server::getInstance()->getAsyncPool()->submitTask(new CanJoin($event->getPlayer()->getName()));
    }
}