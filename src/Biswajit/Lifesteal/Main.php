<?php

declare(strict_types = 1);

namespace Biswajit\Lifesteal;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\event\Cancellable;
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
private $protectedPlayers = [];

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
                $dataFolder = $this->getDataFolder();
                $configFilePath = $dataFolder . 'config.yml'; 
                $config = yaml_parse_file($configFilePath);
                $recipe1 = $config['RecipeItem1'];
                $recipe2 = $config['RecipeItem2'];
                $recipe3 = $config['RecipeItem3'];
				$this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(
					[
						"CBC",
						"BAB",
						"CBC"
					],
					[
						"A" => new ExactRecipeIngredient(VanillaItems::$recipe1()),
						"B" => new ExactRecipeIngredient(VanillaItems::$recipe2()),
						"C" => new ExactRecipeIngredient(VanillaItems::$recipe3())
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
        if (!isset($args[0])) {
                $sender->sendMessage("§e/withdrawal <amount>");
                return false;
            }
        $amount = (int) $args[0] + (int) $args[0];
        $health = $sender->getHealth();
        if($health < $amount) {
         $sender->sendMessage("§cYou Don't Have $amount Heath Yo Withdrawal !!");
                return false;
            }
        $heart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
        $heart->setCount($args[0]);
        if (!$sender->getInventory()->canAddItem($heart)) {
            $sender->sendMessage("§cYour Inventory is Full. Please Empty it!");
            return false;
        }
	$health = $sender->getHealth();
        if ($health <= 4) {
        $sender->sendMessage("§7You Can't Withdrawal More §r §cHeart");
	}else{
        $amount = ($args[0] + $args[0]);
        $playerhealth = (int) $sender->getMaxHealth() - $amount;
        $sender->setMaxHealth($playerhealth);
        $sender->getInventory()->addItem($heart);
        $sender->sendMessage("§l§aYou have successfully withdrawn a heart.");
	}
    }
	return false;
}
    
     public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $this->protectedPlayers[$player->getName()] = time() + $this->config->get("Protected-Time");
        $player->sendMessage("§l§cYour Are Protected For " . $this->config->get("Protected-Time") . " Seconds");
	     
        if ($this->playerData->exists($playerName)) {
           $heart = (int) $this->playerData->get($playerName);
           $player->setMaxHealth($heart);
           $player->setHealth($heart);
        } else {
            $defaultHeart = 20;
            $this->playerData->set($playerName, $defaultHeart);
            $this->playerData->save();
            $player->setMaxHealth($defaultHeart);
            $player->setHealth($defaultHeart);
        }
    }

    
    public function onDamage(EntityDamageEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof Player) {
        if (isset($this->protectedPlayers[$entity->getName()])) {
            $currentTime = time();
            $protectionEndTime = $this->protectedPlayers[$entity->getName()];
            if ($currentTime < $protectionEndTime) {
                $event->cancel();
            } else {
                unset($this->protectedPlayers[$entity->getName()]);
            }
         }
      }
   }
	/**
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event): void{
		$player = $event->getPlayer();
                $entity = $event->getEntity();
                $cause = $entity->getLastDamageCause();
		$lossheart = $this->config->get("Loss Heart");
		$heart = (int) ($lossheart + $lossheart);
		$player->setMaxHealth($player->getMaxHealth() - $heart);
                $this->SaveHeart($player, $heart);
		if ($cause instanceof EntityDamageByEntityEvent) {
                $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                $item = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
                $item->setCount(1);
                $entity->getWorld()->dropItem($entity->getPosition(), $item);
            }
        }
		
          if($player->getMaxHealth() === $this->config->get("Ban On Hearts")){
	      if($player->kick("§cYou lost all your hearts")) {
                $this->playerData->set($player->getName(), 20);
                $this->playerData->save();
	        $player->getServer()->getNameBans()->addBan($player->getName(), 'Lost all hearts');
			}
		}
	} 
	
    public function onItemUse(PlayerItemUseEvent $event) {
       $player = $event->getPlayer();
       $item = $event->getItem();
       $itemHeart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
       $itemHeart->setCount(1);
    if ($item->equals($itemHeart)) {
    $maxHeath = $this->getConfig()->get("max_health");
    $heath = (int) ($maxHeath + $maxHeath);
    if ($player->getMaxHealth() >= $heath) {
        $player->sendMessage("You have reached the maximum health limit.");
        return;
    }
    $addheart = $this->config->get("Heart");
    $heart = (int) ($addheart + $addheart);
    $player->setMaxHealth($player->getMaxHealth() + $heart);
    $this->SaveHeart($player, $heart);
    $player->getInventory()->removeItem($itemHeart);
     } 
    }
	
   public function SaveHeart($player, $amount) {
        $playerName = $player->getName();
        $health = $this->playerData->get($playerName);
        $heart = ($health + $amount);
        $this->playerData->set($playerName, $heart);
        $this->playerData->save();
     }
   }
