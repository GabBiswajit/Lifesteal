<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\listeners;

use Biswajit\Lifesteal\Main;
use Biswajit\Lifesteal\utils\HeartItem;
use Biswajit\Lifesteal\utils\RevivalItem;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class PlayerListener implements Listener {

    private Main $plugin;

    /**
     * PlayerListener constructor.
     *
     * @param Main $plugin Main plugin instance.
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Handles player login and ban checks.
     *
     * @param PlayerLoginEvent $event
     * @priority HIGHEST
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        $banManager = $this->plugin->getDatabase()->getBanManager();
        if ($banManager->isBanned($name)) {
            $event->cancel();
            $event->setKickMessage($banManager->getBanMessage($name));
        }
    }

    /**
     * Loads player data and applies saved health.
     *
     * @param PlayerJoinEvent $event
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $healthManager = $this->plugin->getDatabase()->getHealthManager();

        $healthManager->loadPlayerData(strtolower($player->getName()), function () use ($player, $healthManager): void {
            $healthManager->applyHealth($player);
        });
    }

    /**
     * Handles player death and adjusts hearts accordingly.
     *
     * @param PlayerDeathEvent $event
     * @priority MONITOR
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();

        $healthManager = $this->plugin->getDatabase()->getHealthManager();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                $healthManager->handleKill($damager, $player);
            }
        } else {
            $healthManager->removeHearts($player, 1);
        }
    }

    /**
     * Handles the usage of heart and revival items.
     *
     * @param PlayerItemUseEvent $event
     * @priority MONITOR
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $healthManager = $this->plugin->getDatabase()->getHealthManager();

        if (HeartItem::isHeartItem($item)) {
            $event->cancel();
            $player->getInventory()->removeItem($item->setCount(1));
            $healthManager->addHearts($player, 1);

            $message = $this->plugin->getConfig()->getNested("heart-item.consume-message", "§aYou gained 1 heart!");
            $player->sendMessage($message);

        } elseif (RevivalItem::isRevivalItem($item)) {
            $event->cancel();

            if (!$player->hasPermission("lifesteal.admin")) {
                $player->sendMessage(TextFormat::RED . "You don't have permission to use this item!");
                return;
            }

            $this->plugin->getFormManager()->showReviveForm($player);
        }
    }
}
