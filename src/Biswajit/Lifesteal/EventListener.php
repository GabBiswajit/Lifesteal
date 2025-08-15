<?php

namespace Biswajit\Lifesteal;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent; 
use pocketmine\event\player\PlayerDeathEvent;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\event\player\PlayerItemUseEvent;

class EventListener implements Listener
{
    /* @var array */
    private $protectedPlayers = [];


   public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $this->protectedPlayers[$player->getName()] = time() + Main::getInstance()->getConfig()->get("Protected-Time");
        $player->sendMessage("§l§cYour Are Protected For " . Main::getInstance()->getConfig()->get("Protected-Time") . " Seconds");

        if (Main::getInstance()->getPlayerData()->exists($playerName)) {
           $heart = Main::getInstance()->getPlayerData()->getNested("$playerName.Health");
           $maxHealth = Main::getInstance()->getPlayerData()->getNested("$playerName.MaxHealth");
           $player->setMaxHealth($maxHealth);
           $player->setHealth($heart);
        } else {
           API::getInstance()->RegisterPlayer($player);
        }
    }
 
   public function onLeft(PlayerQuitEvent $event) {
       $player = $event->getPlayer();
       $playerName = $player->getName();

    if (isset($this->protectedPlayers[$playerName])) {
         unset($this->protectedPlayers[$playerName]);
     }
       API::getInstance()->savePlayer($player);
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
		$player->setMaxHealth($player->getMaxHealth() - 2);
        API::getInstance()->savePlayer($player);
		if ($cause instanceof EntityDamageByEntityEvent) {
                $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                $item = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
                $item->setCount(1);
                $entity->getWorld()->dropItem($entity->getPosition(), $item);
            }
        }

          if($player->getMaxHealth() <= Main::getInstance()->getConfig()->get("ban-on-hearts")){
	       if($player->kick("§cYou lost all your hearts")) {
            API::getInstance()->savePlayer($player);
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
     $maxHeath = Main::getInstance()->getConfig()->get("max_health");
     $heath = (int) ($maxHeath + $maxHeath);
     if ($player->getMaxHealth() >= $heath) {
         $player->sendMessage("You have reached the maximum health limit.");
         return;
     }
     $player->setMaxHealth($player->getMaxHealth() + 2);
     API::getInstance()->savePlayer($player);
     $player->getInventory()->removeItem($itemHeart);
     }
  }
}