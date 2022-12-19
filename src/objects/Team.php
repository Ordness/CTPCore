<?php

namespace Ordness\CTP\objects;

use Ordness\CTP\Core;
use pocketmine\color\Color;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\world\Position;
use Voltage\Api\module\BossBar;

final class Team
{
    /**
     * @var string[]
     */
    private array $players;
    private ?Position $spawnPoint = null;
    private BossBar $bossBar;
    private int $score = 0;

    public function __construct(private Color $color, string ...$players)
    {
        $color_name = self::getTeamColorName($this->color);
        $this->bossBar = new BossBar("§fEquipe " . self::colorNameToFormat($color_name) . ucfirst($color_name));
        $this->bossBar->setColorToAll(self::colorNameToBossBar($color_name));
        $this->bossBar->setPercentageToAll(0);
        $this->players = $players;
    }

    public static function getTeamColorName(Color $color): string
    {
        return match ($color->toRGBA()) {
            (new Color(0, 0, 255))->toRGBA() => "bleue",
            (new Color(255, 0, 0))->toRGBA() => "rouge",
            default => "unknown"
        };
    }

    public static function colorNameToFormat(string $color_name): string
    {
        return match ($color_name) {
            "bleue" => "§b",
            "rouge" => "§c",
            default => "§f"
        };
    }

    public static function colorNameToBossBar(string $color_name): string
    {
        return match ($color_name) {
            "bleue" => BossBarColor::BLUE,
            "rouge" => BossBarColor::RED,
            default => BossBarColor::WHITE
        };
    }

    /**
     * @return BossBar
     */
    public function getBossBar(): BossBar
    {
        return $this->bossBar;
    }

    /**
     * @return Color
     */
    public function getColor(): Color
    {
        return $this->color;
    }

    /**
     * @return string[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param Position $spawnPoint
     */
    public function setSpawnPoint(Position $spawnPoint): void
    {
        $this->spawnPoint = $spawnPoint;
    }

    /**
     * @return Position|null
     */
    public function getSpawnPoint(): ?Position
    {
        return $this->spawnPoint;
    }

    public function addPlayer(string $player): void
    {
        $this->players[] = $player;
    }

    /**
     * @param string[] $players
     * @return void
     */
    public function teleportToSpawn(string...$players): void
    {
        foreach ($players as $name) {
            $player = Core::getInstance()->getServer()->getPlayerByPrefix($name);
            $player->teleport($this->spawnPoint);
        }
    }

    /**
     * @param string[] $players
     * @return void
     */
    public function equip(string...$players): void
    {
        foreach ($players as $name) {
            $armor = [
                0 => VanillaItems::LEATHER_CAP()->setCustomColor($this->color),
                1 => VanillaItems::LEATHER_TUNIC()->setCustomColor($this->color),
                2 => VanillaItems::LEATHER_PANTS()->setCustomColor($this->color),
                3 => VanillaItems::LEATHER_BOOTS()->setCustomColor($this->color),
            ];
            $items = [
                0 => VanillaItems::WOODEN_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                1 => VanillaItems::GOLDEN_APPLE()->setCount(16)
            ];
            $player = Core::getInstance()->getServer()->getPlayerByPrefix($name);
            $player->getArmorInventory()->clearAll();
            $player->getArmorInventory()->setContents($armor);
            $player->getInventory()->clearAll();
            $player->getInventory()->setContents($items);
            $player->getEffects()->clear();
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0));
        }
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    public function updateBossBar(): void
    {
        $color_name = self::getTeamColorName($this->color);
        $this->getBossBar()->setTitleToAll("§fEquipe " . self::colorNameToFormat($color_name) . ucfirst($color_name) . " §f- " . self::colorNameToFormat($color_name) . $this->score . "§7/100 §fpoints");
        $this->getBossBar()->setPercentageToAll($this->getScore() / 100);
        $this->getBossBar()->showToAll();
        $this->getBossBar()->sendToAll();
    }

    public function __toString(): string
    {
        return "Team{color=" . $this::getTeamColorName($this->color) . ", players=" . implode(", ", $this->players) . "}";
    }
}