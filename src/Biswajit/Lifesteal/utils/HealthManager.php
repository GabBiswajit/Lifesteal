<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use Biswajit\Lifesteal\Main;
use Biswajit\Lifesteal\utils\BanManager;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use pocketmine\utils\TextFormat;

class HealthManager {
    /** @var Main */
    private $plugin;
    
    /** @var DataConnector */
    private $database;
    
    /** @var BanManager */
    private $banManager;
    
    /** @var array */
    private $playerHearts = [];
    
    /**
     * HealthManager constructor
     * 
     * @param Main $plugin
     * @param DataConnector $database
     * @param BanManager $banManager
     */
    public function __construct(Main $plugin, DataConnector $database, BanManager $banManager) {
        $this->plugin = $plugin;
        $this->database = $database;
        $this->banManager = $banManager;
    }
    
    /**
     * Load player data from database
     * 
     * @param string $player
     * @param callable $callback
     */
    public function loadPlayerData(string $player, ?callable $callback = null): void {
        $player = strtolower($player);
        
        $this->database->executeSelect("lifesteal.get.player", [
            "player" => $player
        ], function(array $rows) use ($player, $callback) {
            if(count($rows) > 0) {
                // Player exists in database
                $this->playerHearts[$player] = [
                    "hearts" => (int) $rows[0]["hearts"],
                    "kills" => (int) $rows[0]["kills"],
                    "deaths" => (int) $rows[0]["deaths"]
                ];
            } else {
                // New player
                $defaultHearts = $this->plugin->getConfig()->get("default-hearts", 10);
                $this->playerHearts[$player] = [
                    "hearts" => $defaultHearts,
                    "kills" => 0,
                    "deaths" => 0
                ];
                
                // Save to database
                $this->savePlayerData($player);
            }
            
            if($callback !== null) {
                $callback($this->playerHearts[$player]);
            }
        });
    }
    
    /**
     * Save player data to database
     * 
     * @param string $player
     * @param callable $callback
     */
    public function savePlayerData(string $player, ?callable $callback = null): void {
        $player = strtolower($player);
        
        if (!isset($this->playerHearts[$player])) {
            return;
        }

        try {
            $data = $this->playerHearts[$player];
            $this->database->executeChange("lifesteal.update.player", [
                "player" => $player,
                "hearts" => $data["hearts"],
                "kills" => $data["kills"],
                "deaths" => $data["deaths"]
            ], $callback);
        } catch (Exception $e) {
            $this->plugin->getLogger()->warning("Error saving player data: " . $e->getMessage());
        }
        
        $data = $this->playerHearts[$player];
        $this->database->executeChange("lifesteal.update.player", [
            "player" => $player,
            "hearts" => $data["hearts"],
            "kills" => $data["kills"],
            "deaths" => $data["deaths"]
        ], $callback);
    }
    
    /**
     * Get player hearts
     * 
     * @param string|Player $player
     * @return int|null
     */
    public function getHearts($player): ?int {
        $name = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
        
        if(!isset($this->playerHearts[$name])) {
            return null;
        }
        
        return $this->playerHearts[$name]["hearts"];
    }
    
    /**
     * Set player hearts
     * 
     * @param string|Player $player
     * @param int $hearts
     * @param bool $save
     * @return bool
     */
    public function setHearts($player, int $hearts, bool $save = true): bool {
        $name = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
        
        if(!isset($this->playerHearts[$name])) {
            return false;
        }
        
        // Get min and max hearts
        $minHearts = $this->plugin->getConfig()->get("min-hearts", 1);
        $maxHearts = $this->plugin->getConfig()->get("max-hearts", 20);
        
        // Clamp hearts between min and max
        $hearts = max($minHearts, min($maxHearts, $hearts));
        
        // Update hearts
        $this->playerHearts[$name]["hearts"] = $hearts;
        
        // Update player health if online
        $onlinePlayer = $this->plugin->getServer()->getPlayerExact($name);
        if($onlinePlayer !== null) {
            $onlinePlayer->setMaxHealth($hearts * 2);
            $onlinePlayer->setHealth($onlinePlayer->getMaxHealth());
        }
        
        // Save to database
        if($save) {
            $this->savePlayerData($name);
        }
        
        // Check if player should be eliminated
        $eliminationHearts = $this->plugin->getConfig()->get("elimination-hearts", 0);
        if($hearts <= $eliminationHearts) {
            $this->eliminatePlayer($name);
        }
        
        return true;
    }
    
    /**
     * Add hearts to player
     * 
     * @param string|Player $player
     * @param int $hearts
     * @param bool $save
     * @return bool
     */
    public function addHearts($player, int $hearts, bool $save = true): bool {
        $name = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
        
        if(!isset($this->playerHearts[$name])) {
            return false;
        }
        
        $currentHearts = $this->playerHearts[$name]["hearts"];
        return $this->setHearts($name, $currentHearts + $hearts, $save);
    }
    
    /**
     * Remove hearts from player
     * 
     * @param string|Player $player
     * @param int $hearts
     * @param bool $save
     * @return bool
     */
    public function removeHearts($player, int $hearts, bool $save = true): bool {
        $name = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
        
        if(!isset($this->playerHearts[$name])) {
            return false;
        }
        
        $currentHearts = $this->playerHearts[$name]["hearts"];
        return $this->setHearts($name, $currentHearts - $hearts, $save);
    }
    
    /**
     * Eliminate player (ban or kick)
     * 
     * @param string $player
     */
    public function eliminatePlayer(string $player): void {
        $player = strtolower($player);
        $config = $this->plugin->getConfig();
        
        // Check if elimination is enabled
        if(!$config->get("elimination-enabled", true)) {
            return;
        }
        
        // Get elimination type
        $eliminationType = $config->get("elimination-type", "kick");
        
        // Get elimination message
        $message = $config->get("elimination-message", "§cYou have been eliminated from the game!");
        
        // Get broadcast message
        $broadcast = $config->get("elimination-broadcast", "§c{player} has been eliminated from the game!");
        $broadcast = str_replace("{player}", $player, $broadcast);
        
        // Broadcast elimination
        $this->plugin->getServer()->broadcastMessage($broadcast);
        
        // Check if player should be banned
        if($eliminationType === "ban" && $config->getNested("ban.enabled", true)) {
            // Get ban duration
            $banDuration = $config->getNested("ban.duration", 7);
            
            // Ban player
            $this->banManager->banPlayer($player, $banDuration, "Eliminated - Lost all hearts");
            
            // Reset hearts if configured
            if($config->getNested("ban.reset-hearts-on-ban", true)) {
                $defaultHearts = $config->get("default-hearts", 10);
                $this->setHearts($player, $defaultHearts);
            }
        } elseif($eliminationType === "kick") {
            // Kick player if online
            $onlinePlayer = $this->plugin->getServer()->getPlayerExact($player);
            if($onlinePlayer !== null) {
                $onlinePlayer->kick($message);
            }
        }
    }
    
    /**
     * Reset player data
     * 
     * @param string $player
     */
    public function resetPlayer(string $player): void {
        $player = strtolower($player);
        
        // Get default hearts
        $defaultHearts = $this->plugin->getConfig()->get("default-hearts", 10);
        
        // Reset player data
        $this->playerHearts[$player] = [
            "hearts" => $defaultHearts,
            "kills" => 0,
            "deaths" => 0
        ];
        
        // Save to database
        $this->savePlayerData($player);
    }
    
    /**
     * Handle player kill
     * 
     * @param Player $killer
     * @param Player $victim
     */
    public function handleKill(Player $killer, Player $victim): void {
        $killerName = strtolower($killer->getName());
        $victimName = strtolower($victim->getName());
        
        // Make sure both players are loaded
        if(!isset($this->playerHearts[$killerName]) || !isset($this->playerHearts[$victimName])) {
            return;
        }
        
        // Get hearts per kill
        $heartsPerKill = $this->plugin->getConfig()->get("hearts-per-kill", 1);
        
        // Update killer stats
        $this->playerHearts[$killerName]["kills"]++;
        $this->addHearts($killer, $heartsPerKill, false);
        
        // Update victim stats
        $this->playerHearts[$victimName]["deaths"]++;
        $this->removeHearts($victim, $heartsPerKill, false);
        
        // Save both players
        $this->savePlayerData($killerName);
        $this->savePlayerData($victimName);
        
        // Send messages
        $killer->sendMessage(TextFormat::GREEN . "You gained " . $heartsPerKill . " heart(s) for killing " . $victim->getName());
    }
    
    /**
     * Apply health to player
     * 
     * @param Player $player
     */
    public function applyHealth(Player $player): void {
        $name = strtolower($player->getName());
        
        if(!isset($this->playerHearts[$name])) {
            $this->loadPlayerData($name, function() use ($player, $name) {
                $this->applyHealthNow($player);
            });
        } else {
            $this->applyHealthNow($player);
        }
    }
    
    /**
     * Apply health immediately
     * 
     * @param Player $player
     */
    private function applyHealthNow(Player $player): void {
        $name = strtolower($player->getName());
        
        if(!isset($this->playerHearts[$name])) {
            return;
        }
        
        $hearts = $this->playerHearts[$name]["hearts"];
        $player->setMaxHealth($hearts * 2);
        $player->setHealth($player->getMaxHealth());
        
        // Announce hearts on join if enabled
        if($this->plugin->getConfig()->get("announce-hearts-on-join", true)) {
            $player->sendMessage(TextFormat::YELLOW . "Your current hearts: " . TextFormat::RED . $hearts);
        }
    }
}