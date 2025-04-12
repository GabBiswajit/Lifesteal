<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use Biswajit\Lifesteal\Main;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\form\Form;

class FormManager {
    /** @var Main */
    private $plugin;
    
    /**
     * FormManager constructor
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Show revival form to player
     * 
     * @param Player $player
     */
    public function showReviveForm(Player $player): void {
        // Get banned players
        $bannedPlayers = [];
        $banManager = $this->plugin->getDatabase()->getBanManager();
        
        // Loop through online and offline players
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
            $name = strtolower($p->getName());
            if($banManager->isBanned($name)) {
                $bannedPlayers[] = $p->getName();
            }
        }
        
        // Get banned players from banned-players.txt if available
        $bannedPlayersFile = $this->plugin->getServer()->getDataPath() . "banned-players.txt";
        if(file_exists($bannedPlayersFile)) {
            $content = file_get_contents($bannedPlayersFile);
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                $line = trim($line);
                if(!empty($line) && !in_array($line, $bannedPlayers) && $banManager->isBanned(strtolower($line))) {
                    $bannedPlayers[] = $line;
                }
            }
        }
        
        // Sort banned players
        sort($bannedPlayers);
        
        // No banned players
        if(count($bannedPlayers) === 0) {
            $player->sendMessage(TextFormat::RED . "There are no banned players to revive!");
            return;
        }
        
        // Create form
        $form = new class($this->plugin, $bannedPlayers) implements Form {
            /** @var Main */
            private $plugin;
            
            /** @var array */
            private $bannedPlayers;
            
            /**
             * Constructor
             * 
             * @param Main $plugin
             * @param array $bannedPlayers
             */
            public function __construct(Main $plugin, array $bannedPlayers) {
                $this->plugin = $plugin;
                $this->bannedPlayers = $bannedPlayers;
            }
            
            /**
             * Serialize form data
             * 
             * @return array
             */
            public function jsonSerialize(): array {
                $buttons = [];
                foreach($this->bannedPlayers as $player) {
                    $buttons[] = [
                        "text" => $player
                    ];
                }
                
                return [
                    "type" => "form",
                    "title" => "Revive Player",
                    "content" => "Select a player to revive:",
                    "buttons" => $buttons
                ];
            }
            
            /**
             * Handle form response
             * 
             * @param Player $player
             * @param mixed $data
             */
            public function handleResponse(Player $player, $data): void {
                if($data === null) {
                    return;
                }
                
                // Get selected player
                $selectedPlayer = $this->bannedPlayers[$data];
                
                // Remove revival item
                $inventory = $player->getInventory();
                $itemInHand = $player->getInventory()->getItemInHand();
                if(RevivalItem::isRevivalItem($itemInHand)) {
                    $inventory->removeItem($itemInHand->setCount(1));
                }
                
                // Unban player
                $banManager = $this->plugin->getDatabase()->getBanManager();
                if($banManager->unbanPlayer(strtolower($selectedPlayer))) {
                    // Success message
                    $message = $this->plugin->getConfig()->getNested("revival.success-message", "§aYou have successfully revived {player}!");
                    $message = str_replace("{player}", $selectedPlayer, $message);
                    $player->sendMessage($message);
                    
                    // Broadcast message
                    $this->plugin->getServer()->broadcastMessage(TextFormat::GREEN . $selectedPlayer . " has been revived by " . $player->getName() . "!");
                    
                    // Reset player if configured
                    if($this->plugin->getConfig()->getNested("ban.reset-hearts-on-ban", true)) {
                        $healthManager = $this->plugin->getDatabase()->getHealthManager();
                        $healthManager->resetPlayer(strtolower($selectedPlayer));
                    }
                } else {
                    $player->sendMessage(TextFormat::RED . "Failed to revive " . $selectedPlayer . "!");
                }
            }
        };
        
        // Send form
        $player->sendForm($form);
    }
}