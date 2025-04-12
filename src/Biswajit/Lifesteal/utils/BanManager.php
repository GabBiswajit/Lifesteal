<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use Biswajit\Lifesteal\Main;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;

class BanManager {
    /** @var Main */
    private $plugin;
    
    /** @var DataConnector */
    private $database;
    
    /** @var array */
    private $banCache = [];
    
    /**
     * BanManager constructor
     * 
     * @param Main $plugin
     * @param DataConnector $database
     */
    public function __construct(Main $plugin, DataConnector $database) {
        $this->plugin = $plugin;
        $this->database = $database;
        
        // Load all bans to cache
        $this->loadBans();
        
        // Set up scheduled task to clean expired bans (every minute)
        $plugin->getScheduler()->scheduleRepeatingTask(new \pocketmine\scheduler\ClosureTask(
            function(): void {
                $this->cleanExpiredBans();
            }
        ), 20 * 60); // 60 seconds
    }
    
    /**
     * Load all bans from database
     */
    public function loadBans(): void {
        $this->banCache = [];
        
        $this->database->executeSelect("lifesteal.get.all_bans", [], 
            function(array $rows): void {
                foreach($rows as $row) {
                    $this->banCache[strtolower($row["player"])] = [
                        "expiry" => (int) $row["expiry"],
                        "reason" => $row["reason"]
                    ];
                }
            }
        );
    }
    
    /**
     * Clean expired bans
     */
    private function cleanExpiredBans(): void {
        $currentTime = time();
        
        // Clean from database
        $this->database->executeChange("lifesteal.delete.expired_bans", [
            "time" => $currentTime
        ]);
        
        // Clean from cache
        foreach($this->banCache as $player => $data) {
            if($data["expiry"] > 0 && $data["expiry"] < $currentTime) {
                unset($this->banCache[$player]);
            }
        }
    }
    
    /**
     * Ban a player
     * 
     * @param string $player
     * @param int $duration Duration in days (0 = permanent)
     * @param string $reason
     */
    public function banPlayer(string $player, int $duration, string $reason): void {
        $player = strtolower($player);
        $expiry = $duration > 0 ? time() + ($duration * 24 * 60 * 60) : 0; // 0 = permanent
        
        // Add to database
        $this->database->executeChange("lifesteal.update.ban", [
            "player" => $player,
            "expiry" => $expiry,
            "reason" => $reason
        ]);
        
        // Add to cache
        $this->banCache[$player] = [
            "expiry" => $expiry,
            "reason" => $reason
        ];
        
        // Kick the player if online
        $onlinePlayer = $this->plugin->getServer()->getPlayerExact($player);
        if($onlinePlayer !== null) {
            $onlinePlayer->kick($this->getBanMessage($player));
        }
    }
    
    /**
     * Unban a player
     * 
     * @param string $player
     * @return bool
     */
    public function unbanPlayer(string $player): bool {
        $player = strtolower($player);
        
        if(!isset($this->banCache[$player])) {
            return false;
        }
        
        // Remove from database
        $this->database->executeChange("lifesteal.delete.ban", [
            "player" => $player
        ]);
        
        // Remove from cache
        unset($this->banCache[$player]);
        
        return true;
    }
    
    /**
     * Check if player is banned
     * 
     * @param string $player
     * @return bool
     */
    public function isBanned(string $player): bool {
        $player = strtolower($player);
        
        if(!isset($this->banCache[$player])) {
            return false;
        }
        
        $ban = $this->banCache[$player];
        
        // Check if ban has expired
        if($ban["expiry"] > 0 && $ban["expiry"] < time()) {
            $this->unbanPlayer($player);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get ban expiry time
     * 
     * @param string $player
     * @return int|null
     */
    public function getBanExpiry(string $player): ?int {
        $player = strtolower($player);
        
        if(!isset($this->banCache[$player])) {
            return null;
        }
        
        return $this->banCache[$player]["expiry"];
    }
    
    /**
     * Get ban reason
     * 
     * @param string $player
     * @return string|null
     */
    public function getBanReason(string $player): ?string {
        $player = strtolower($player);
        
        if(!isset($this->banCache[$player])) {
            return null;
        }
        
        return $this->banCache[$player]["reason"];
    }
    
    /**
     * Get formatted ban message
     * 
     * @param string $player
     * @return string
     */
    public function getBanMessage(string $player): string {
        $player = strtolower($player);
        
        if(!isset($this->banCache[$player])) {
            return "You are not banned";
        }
        
        $ban = $this->banCache[$player];
        $reason = $ban["reason"];
        $expiry = $ban["expiry"];
        
        $config = $this->plugin->getConfig();
        $message = $config->getNested("ban.message", "§cYou have been banned due to losing all your hearts!");
        
        // Replace {time} with formatted time
        if($expiry > 0) {
            $remaining = $expiry - time();
            $timeFormat = $this->formatTimeRemaining($remaining);
            $message = str_replace("{time}", $timeFormat, $message);
        } else {
            $message = str_replace("{time}", "permanent", $message);
        }
        
        return $message;
    }
    
    /**
     * Format time remaining
     * 
     * @param int $seconds
     * @return string
     */
    private function formatTimeRemaining(int $seconds): string {
        $config = $this->plugin->getConfig();
        $format = $config->getNested("ban.time-format", "{days}d {hours}h {minutes}m {seconds}s");
        
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        return str_replace(
            ["{days}", "{hours}", "{minutes}", "{seconds}"],
            [$days, $hours, $minutes, $seconds],
            $format
        );
    }
}
