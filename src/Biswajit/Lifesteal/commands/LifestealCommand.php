<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\commands;

use Biswajit\Lifesteal\Main;
use Biswajit\Lifesteal\utils\HeartItem;
use Biswajit\Lifesteal\utils\RevivalItem;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginOwned;

class LifestealCommand extends Command implements PluginOwned {
    /** @var Main */
    private $plugin;
    
    /**
     * LifestealCommand constructor
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        parent::__construct("lifesteal", "Lifesteal main command", "/lifesteal <help|sethearts|gethearts|resethearts|withdraw|revive|unban|leaderboard|reload>");
        $this->setPermission("lifesteal.command");
        $this->plugin = $plugin;
    }
    
    /**
     * Execute command
     * 
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if(!isset($args[0])) {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        
        switch(strtolower($args[0])) {
            case "help":
                $this->sendHelp($sender);
                return true;
                
            case "leaderboard":
            case "top":
            case "lb":
                if(!$sender->hasPermission("lifesteal.command.leaderboard")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                // Default type is hearts
                $type = isset($args[1]) ? strtolower($args[1]) : "hearts";
                
                // Set type based on argument
                if(!in_array($type, ["hearts", "kills", "kdr"])) {
                    $sender->sendMessage(TextFormat::RED . "Invalid leaderboard type! Available types: hearts, kills, kdr");
                    return false;
                }
                
                // Default limit is 10
                $limit = isset($args[2]) ? (int) $args[2] : 10;
                if($limit < 1) $limit = 10;
                if($limit > 50) $limit = 50;
                
                // Show leaderboard
                $this->plugin->getDatabase()->getLeaderboardManager()->showLeaderboard($sender, $type, $limit);
                return true;
                
            case "sethearts":
                if(!$sender->hasPermission("lifesteal.command.sethearts")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                if(!isset($args[1]) || !isset($args[2])) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /lifesteal sethearts <player> <hearts>");
                    return false;
                }
                
                $playerName = $args[1];
                $hearts = (int) $args[2];
                
                $this->plugin->getDatabase()->getHealthManager()->setHearts($playerName, $hearts);
                $sender->sendMessage(TextFormat::GREEN . "Set " . $playerName . "'s hearts to " . $hearts);
                return true;
                
            case "gethearts":
                if(!$sender->hasPermission("lifesteal.command.gethearts")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                $playerName = isset($args[1]) ? $args[1] : ($sender instanceof Player ? $sender->getName() : null);
                
                if($playerName === null) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /lifesteal gethearts <player>");
                    return false;
                }
                
                $hearts = $this->plugin->getDatabase()->getHealthManager()->getHearts($playerName);
                
                if($hearts === null) {
                    $sender->sendMessage(TextFormat::RED . "Player not found!");
                    return false;
                }
                
                $sender->sendMessage(TextFormat::GREEN . $playerName . "'s hearts: " . TextFormat::RED . $hearts);
                return true;
                
            case "resethearts":
                if(!$sender->hasPermission("lifesteal.command.resethearts")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                if(!isset($args[1])) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /lifesteal resethearts <player>");
                    return false;
                }
                
                $playerName = $args[1];
                
                $this->plugin->getDatabase()->getHealthManager()->resetPlayer($playerName);
                $sender->sendMessage(TextFormat::GREEN . "Reset " . $playerName . "'s hearts to default");
                return true;
                
            case "withdraw":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
                    return false;
                }
                
                if(!$sender->hasPermission("lifesteal.command.withdraw")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                $amount = isset($args[1]) ? (int) $args[1] : 1;
                if($amount < 1) {
                    $amount = 1;
                }
                
                $config = $this->plugin->getConfig();
                $withdrawCost = $config->getNested("heart-item.withdraw-cost", 1);
                $minHearts = $config->getNested("heart-item.withdraw-min-hearts", 2);
                $totalCost = $withdrawCost * $amount;
                
                $healthManager = $this->plugin->getDatabase()->getHealthManager();
                $currentHearts = $healthManager->getHearts($sender);
                
                if($currentHearts === null) {
                    $sender->sendMessage(TextFormat::RED . "Your data hasn't been loaded yet. Please try again!");
                    return false;
                }
                
                if($currentHearts - $totalCost < $minHearts) {
                    $sender->sendMessage(TextFormat::RED . "You don't have enough hearts! You need at least " . ($minHearts + $totalCost) . " hearts.");
                    return false;
                }
                
                // Create heart item
                $heartName = $config->getNested("heart-item.name", "§c§lHeart");
                $heartLore = $config->getNested("heart-item.lore", ["§7Right-click to gain 1 heart"]);
                $heartItem = HeartItem::create($heartName, $heartLore, $amount);
                
                // Give player heart item
                $sender->getInventory()->addItem($heartItem);
                
                // Remove hearts
                $healthManager->removeHearts($sender, $totalCost);
                
                $sender->sendMessage(TextFormat::GREEN . "You withdrew " . $amount . " heart(s) for " . $totalCost . " of your own hearts!");
                return true;
                
            case "revive":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
                    return false;
                }
                
                if(!$sender->hasPermission("lifesteal.admin")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                // Give player revival item
                $revivalName = $this->plugin->getConfig()->getNested("revival.item-name", "§6§lRevival Bacon");
                $revivalLore = $this->plugin->getConfig()->getNested("revival.item-lore", ["§7Use this item to revive a banned player"]);
                $revivalItem = RevivalItem::create($revivalName, $revivalLore);
                
                $sender->getInventory()->addItem($revivalItem);
                $sender->sendMessage(TextFormat::GREEN . "You received a revival item!");
                return true;
                
            case "unban":
                if(!$sender->hasPermission("lifesteal.admin")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                if(!isset($args[1])) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /lifesteal unban <player>");
                    return false;
                }
                
                $playerName = $args[1];
                
                $banManager = $this->plugin->getDatabase()->getBanManager();
                if($banManager->unbanPlayer($playerName)) {
                    $sender->sendMessage(TextFormat::GREEN . "Successfully unbanned " . $playerName);
                    
                    // Reset player if configured
                    if($this->plugin->getConfig()->getNested("ban.reset-hearts-on-ban", true)) {
                        $this->plugin->getDatabase()->getHealthManager()->resetPlayer($playerName);
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player is not banned or doesn't exist!");
                }
                return true;
                
            case "reload":
                if(!$sender->hasPermission("lifesteal.command.reload")) {
                    $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
                    return false;
                }
                
                $this->plugin->reloadConfig();
                $sender->sendMessage(TextFormat::GREEN . "Lifesteal configuration reloaded!");
                return true;
                
            default:
                $sender->sendMessage(TextFormat::RED . "Unknown subcommand. Use /lifesteal help for a list of commands.");
                return false;
        }
    }
    
    /**
     * Send help to sender
     * 
     * @param CommandSender $sender
     */
    private function sendHelp(CommandSender $sender): void {
        $sender->sendMessage(TextFormat::GREEN . "=== Lifesteal Commands ===");
        
        if($sender->hasPermission("lifesteal.command.sethearts")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal sethearts <player> <hearts>" . TextFormat::WHITE . " - Set a player's hearts");
        }
        
        if($sender->hasPermission("lifesteal.command.gethearts")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal gethearts [player]" . TextFormat::WHITE . " - Get a player's hearts");
        }
        
        if($sender->hasPermission("lifesteal.command.resethearts")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal resethearts <player>" . TextFormat::WHITE . " - Reset a player's hearts");
        }
        
        if($sender->hasPermission("lifesteal.command.withdraw")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal withdraw [amount]" . TextFormat::WHITE . " - Withdraw hearts into items");
        }
        
        if($sender->hasPermission("lifesteal.command.leaderboard")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal leaderboard [type] [limit]" . TextFormat::WHITE . " - View leaderboards");
            $sender->sendMessage(TextFormat::GRAY . "Leaderboard types: hearts, kills, kdr");
        }
        
        if($sender->hasPermission("lifesteal.admin")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal revive" . TextFormat::WHITE . " - Get a revival item");
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal unban <player>" . TextFormat::WHITE . " - Unban a player");
        }
        
        if($sender->hasPermission("lifesteal.command.reload")) {
            $sender->sendMessage(TextFormat::YELLOW . "/lifesteal reload" . TextFormat::WHITE . " - Reload configuration");
        }
        
        $sender->sendMessage(TextFormat::YELLOW . "/lifesteal help" . TextFormat::WHITE . " - Show this help message");
    }
    
    /**
     * Get owning plugin
     * 
     * @return Main
     */
    public function getOwningPlugin(): Main {
        return $this->plugin;
    }
}
