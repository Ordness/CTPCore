<?php

namespace Ordness\CTP\objects;

use Ordness\CTP\Core;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Binary;
use pocketmine\world\format\io\data\BedrockWorldData;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Webmozart\PathUtil\Path;

final class Map
{
    private Vector3 $blue;
    private Vector3 $red;
    /**
     * @var Point[]
     */
    private array $points;

    public function __construct(private string $name, array $spawnpoint, array...$points)
    {
        $this->blue = new Vector3($spawnpoint["blue"]["x"] ?? 0, $spawnpoint["blue"]["y"] ?? 0, $spawnpoint["blue"]["z"] ?? 0);
        $this->red = new Vector3($spawnpoint["red"]["x"] ?? 0, $spawnpoint["red"]["y"] ?? 0, $spawnpoint["red"]["z"] ?? 0);
        $zones = [];
        foreach ($points as $point) {
            [$x, $y, $z] = explode(":", $point["position"]);
            $zones[] = new Point((int)$point["radius"] ?? 0, new Vector3((int)$x, (int)$y, (int)$z));
        }
        $this->points = $zones;
    }

    /**
     * @return array
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function duplicate(string $id): ?string
    {
        $newName = "ctp-$this->name-$id";
        $this->copyWorld($this->name, $newName);
        return Core::getInstance()->getServer()->getWorldManager()->loadWorld($newName)
            ? $newName
            : null;
    }

    public function copyWorld(string $from, string $name): string
    {
        $server = Core::getInstance()->getServer();
        @mkdir($server->getDataPath() . "/worlds/$name/");
        @mkdir($server->getDataPath() . "/worlds/$name/db/");
        copy($server->getDataPath() . "/worlds/" . $from . "/level.dat", $server->getDataPath() . "/worlds/$name/level.dat");
        $oldWorldPath = $server->getDataPath() . "/worlds/$from/level.dat";
        $newWorldPath = $server->getDataPath() . "/worlds/$name/level.dat";

        $oldWorldNbt = new BedrockWorldData($oldWorldPath);
        $newWorldNbt = new BedrockWorldData($newWorldPath);

        $worldData = $oldWorldNbt->getCompoundTag();
        $newWorldNbt->getCompoundTag()->setString("LevelName", $name);


        $nbt = new LittleEndianNbtSerializer();
        $buffer = $nbt->write(new TreeRoot($worldData));
        file_put_contents(Path::join($newWorldPath), Binary::writeLInt(BedrockWorldData::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);
        $this->copyDir($server->getDataPath() . "/worlds/" . $from . "/db", $server->getDataPath() . "/worlds/$name/db/");
        return $name;
    }

    public function copyDir($from, $to): void
    {
        $to = rtrim($to, "\\/") . "/";
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from)) as $file) {
            if ($file->isFile()) {
                $target = $to . ltrim(substr($file->getRealPath(), strlen($from)), "\\/");
                $dir = dirname($target);
                if (!is_dir($dir)) {
                    mkdir(dirname($target), 0777, true);
                }
                copy($file->getRealPath(), $target);
            }
        }
    }

    public function getRedSpawn(): Vector3
    {
        return $this->red;
    }

    public function getBlueSpawn(): Vector3
    {
        return $this->blue;
    }
}