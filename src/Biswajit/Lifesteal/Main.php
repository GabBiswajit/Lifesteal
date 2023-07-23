<?php

declare(strict_types = 1);

namespace Biswajit\Lifesteal;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\command\CommandSender;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use customiesdevs\customies\item\CustomiesItemFactory;
use Biswajit\Lifesteal\item\Heart;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\scheduler\ClosureTask;
use Symfony\Component\Filesystem\Path;
use function array_merge;

final class Main extends PluginBase implements Listener{

/** @var Config */
private $playerData;
private $config;
	
	public function onEnable(): void{
        $this->playerData = new Config($this->getDataFolder() . "playerdata.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	    $this->saveResource("config.yml");
	    $version = $this->getDescription()->getVersion();
        $configVer = $this->getConfig()->get("version");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
    
       $this->saveResource("Lifesteal.mcpack");
		$rpManager = $this->getServer()->getResourcePackManager();
		$rpManager->setResourceStack(array_merge($rpManager->getResourceStack(), [new ZippedResourcePack(Path::join($this->getDataFolder(), "Lifesteal.mcpack"))]));
		(new \ReflectionProperty($rpManager, "serverForceResources"))->setValue($rpManager, true);

		CustomiesItemFactory::getInstance()->registerItem(heart::class, "lifesteal:heart", "heart");

		if($this->getConfig()->get("register-recipes", true)){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
				$heart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
				$this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(
					[
						"CBC",
						"BAB",
						"CBC"
					],
					[
						"A" => new ExactRecipeIngredient(VanillaItems::HEART_OF_THE_SEA()),
						"B" => new ExactRecipeIngredient(VanillaItems::PRISMARINE_SHARD()),
						"C" => new ExactRecipeIngredient(VanillaItems::GOLD_INGOT())
					],
					[$heart]
				));
			}), 2);
		}
        if(version_compare($version, $configVer, "<>")) {
            $this->getLogger()->warning("Plugin version does not match config version. Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
     }
     
     
public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if ($command->getName() === "withdrawal") {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return true;
        }
        $heart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
        if (!$sender->getInventory()->canAddItem($heart)) {
            $sender->sendMessage("§cYour Inventory is Full. Please Empty it!");
            return false;
        }
        $sender->setMaxHealth($sender->getMaxHealth() - 2);
        $sender->getInventory()->addItem($heart);
        $sender->sendMessage("§l§aYou have successfully withdrawn a heart.");
    }
    return false;
}
    
     public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $playerName = $player->getName();

        if ($this->playerData->exists($playerName)) {
           $heart = $this->playerData->get($playerName);
           $heart = (int) $heart; // Convert $heart to an integer
           $player->setMaxHealth($heart);
           $player->setHealth($heart);
        } else {
            // Set default heart value for new players
            $defaultHeart = 20; // Change this to your desired default heart value
            $this->playerData->set($playerName, $defaultHeart);
            $this->playerData->save();
            $player->setMaxHealth($defaultHeart);
            $player->setHealth($defaultHeart);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $heart = $player->getHealth();

        // Save the player's heart value when they leave
        $this->playerData->set($playerName, $heart);
        $this->playerData->save();
    }
 

	/**
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event): void{
		$player = $event->getPlayer();
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();
		$player->setMaxHealth($player->getMaxHealth() - $this->config->get("Loss Heart"));
        
		if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                $item = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
                $item->setCount(1); // Set the quantity of the dropped item
                $entity->getWorld()->dropItem($entity->getPosition(), $item);
            }
        }
		
		if($player->getMaxHealth() === $this->config->get("Ban On Hearts")){
			if($player->kick('You lost all your healths')){
				$player->getServer()->getNameBans()->addBan($player->getName(), 'Lost all healths');
			}
		}
	} 
public function onItemUse(PlayerItemUseEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $item = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
        $item->setCount(1);
        $player->setMaxHealth($player->getMaxHealth() + $this->config->get("Heart"));
        $player->getInventory()->removeItem($item);
	}
}
