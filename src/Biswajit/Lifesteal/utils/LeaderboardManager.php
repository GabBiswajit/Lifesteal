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
    
    /**
     * LeaderboardManager constructor
     * 
     * @param Main $plugin
     * @param DataConnector $database
     */
    public function __construct(Main $plugin, DataConnector $database) {
        $this->plugin = $plugin;
        $this->database = $database;
        
        // Register SQL queries
        $this->registerQueries();
    }
    
    /**
     * Register custom SQL queries for leaderboards
     */
    private function registerQueries(): void {
        // hearts leaderboard
        $this->database->executeGeneric("lifesteal.leaderboard.init.hearts");
        
        // kills leaderboard
        $this->database->executeGeneric("lifesteal.leaderboard.init.kills");
        
        // kdr leaderboard (kill/death ratio)
        $this->database->executeGeneric("lifesteal.leaderboard.init.kdr");
    }
    
    /**
     * Get leaderboard data
     * 
     * @param string $type Type of leaderboard (hearts, kills, kdr)
     * @param int $limit Number of entries to retrieve
     * @param callable $callback Callback function to run with the results
     */
    public function getLeaderboard(string $type, int $limit = 10, callable $callback): void {
        // Check if cache is available and not expired
        if($this->isCacheValid($type)) {
            $callback($this->cachedLeaderboards[$type]);
            return;
        }
        
        // Get leaderboard from database
        switch($type) {
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
    
    /**
     * Cache leaderboard data
     * 
     * @param string $type Type of leaderboard
     * @param array $data Leaderboard data
     */
    private function cacheLeaderboard(string $type, array $data): void {
        $this->cachedLeaderboards[$type] = $data;
        $this->cacheTime = time();
    }
    
    /**
     * Check if cache is valid
     * 
     * @param string $type Type of leaderboard
     * @return bool
     */
    private function isCacheValid(string $type): bool {
        return isset($this->cachedLeaderboards[$type]) && 
               time() - $this->cacheTime < $this->cacheDuration;
    }
    
    /**
     * Format leaderboard message for player
     * 
     * @param string $type Type of leaderboard
     * @param array $data Leaderboard data
     * @return string
     */
    public function formatLeaderboard(string $type, array $data): string {
        $config = $this->plugin->getConfig();
        $title = $config->getNested("leaderboard.titles.$type", ucfirst($type) . " Leaderboard");
        
        $message = TextFormat::GREEN . "=== " . $title . " ===\n";
        
        if(count($data) === 0) {
            $message .= TextFormat::RED . "No data available!";
            return $message;
        }
        
        foreach($data as $index => $entry) {
            $position = $index + 1;
            $name = $entry["player"];
            $value = $entry["value"];
            
            // Format based on type
            switch($type) {
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
            
            // Format position markers
            switch($position) {
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
    
    /**
     * Show leaderboard to player
     * 
     * @param Player $player
     * @param string $type Type of leaderboard
     * @param int $limit Number of entries to retrieve
     */
    public function showLeaderboard(Player $player, string $type, int $limit = 10): void {
        $this->getLeaderboard($type, $limit, function(array $data) use ($player, $type) {
            $message = $this->formatLeaderboard($type, $data);
            $player->sendMessage($message);
        });
    }
}