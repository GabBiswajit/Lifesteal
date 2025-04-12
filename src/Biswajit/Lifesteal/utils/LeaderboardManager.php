<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use Biswajit\Lifesteal\Main;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;

class LeaderboardManager {
    /** @var Main */
    private $plugin;
    
    /** @var DataConnector */
    private $database;
    
    /** @var array */
    private $cachedLeaderboards = [];
    
    /** @var int */
    private $cacheTime = 0;
    
    /** @var int */
    private $cacheDuration = 300; // 5 minutes cache
    
    public function __construct(Main $plugin, DataConnector $database) {
        $this->plugin = $plugin;
        $this->database = $database;
        $this->registerQueries();
    }
    
    private function registerQueries(): void {
        $this->database->executeGeneric("lifesteal.leaderboard.init.hearts");
        $this->database->executeGeneric("lifesteal.leaderboard.init.kills");
        $this->database->executeGeneric("lifesteal.leaderboard.init.kdr");
    }

    public function getLeaderboard(string $type, callable $callback, int $limit = 10): void {
        if ($this->isCacheValid($type)) {
            $callback($this->cachedLeaderboards[$type]);
            return;
        }

        switch ($type) {
            case "hearts":
                $this->database->executeSelect("lifesteal.leaderboard.get.hearts", [
                    "limit" => $limit
                ], function(array $rows) use ($callback, $type) {
                    $this->cacheLeaderboard($type, $rows);
                    $callback($rows);
                });
                break;

            case "kills":
                $this->database->executeSelect("lifesteal.leaderboard.get.kills", [
                    "limit" => $limit
                ], function(array $rows) use ($callback, $type) {
                    $this->cacheLeaderboard($type, $rows);
                    $callback($rows);
                });
                break;

            case "kdr":
                $this->database->executeSelect("lifesteal.leaderboard.get.kdr", [
                    "limit" => $limit
                ], function(array $rows) use ($callback, $type) {
                    $this->cacheLeaderboard($type, $rows);
                    $callback($rows);
                });
                break;

            default:
                $callback([]);
                break;
        }
    }

    private function cacheLeaderboard(string $type, array $data): void {
        $this->cachedLeaderboards[$type] = $data;
        $this->cacheTime = time();
    }

    private function isCacheValid(string $type): bool {
        return isset($this->cachedLeaderboards[$type]) &&
               time() - $this->cacheTime < $this->cacheDuration;
    }

    public function formatLeaderboard(string $type, array $data): string {
        $config = $this->plugin->getConfig();
        $title = $config->getNested("leaderboard.titles.$type", ucfirst($type) . " Leaderboard");

        $message = TextFormat::GREEN . "=== " . $title . " ===\n";

        if (count($data) === 0) {
            $message .= TextFormat::RED . "No data available!";
            return $message;
        }

        foreach ($data as $index => $entry) {
            $position = $index + 1;
            $name = $entry["player"];
            $value = $entry["value"];

            switch ($type) {
                case "hearts":
                    $valueText = $value . " " . ($value === 1 ? "heart" : "hearts");
                    break;

                case "kills":
                    $valueText = $value . " " . ($value === 1 ? "kill" : "kills");
                    break;

                case "kdr":
                    $valueText = number_format($value, 2) . " K/D";
                    break;

                default:
                    $valueText = $value;
                    break;
            }

            switch ($position) {
                case 1:
                    $positionMarker = TextFormat::GOLD . "1st" . TextFormat::WHITE;
                    break;
                case 2:
                    $positionMarker = TextFormat::GRAY . "2nd" . TextFormat::WHITE;
                    break;
                case 3:
                    $positionMarker = TextFormat::DARK_RED . "3rd" . TextFormat::WHITE;
                    break;
                default:
                    $positionMarker = TextFormat::WHITE . $position . "th";
                    break;
            }

            $message .= "\n" . $positionMarker . ". " . TextFormat::AQUA . $name . TextFormat::WHITE . " - " . TextFormat::YELLOW . $valueText;
        }

        return $message;
    }

    public function showLeaderboard(Player $player, string $type, int $limit = 10): void {
        $this->getLeaderboard($type, function(array $data) use ($player, $type) {
            $message = $this->formatLeaderboard($type, $data);
            $player->sendMessage($message);
        }, $limit);
    }
}
