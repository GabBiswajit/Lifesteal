<?php

namespace Biswajit\Lifesteal;

use Biswajit\Lifesteal\item\Heart;
use pocketmine\utils\SingletonTrait;
use pocketmine\player\Player;
use Biswajit\Lifesteal\Main;

class API
{
  
  use SingletonTrait;

    /** @var Main */
    private $source;
   
  public function init(Main $source)
  {
    $this->source = $source;
  }

  public function RegisterPlayer(Player $player): void
  {
     $playerName = $player->getName();
     Main::getInstance()->getPlayerData()->setNested("$playerName.Health", 20);
     Main::getInstance()->getPlayerData()->setNested("$playerName.MaxHealth", 20);
     Main::getInstance()->getPlayerData()->save();
  }

 public function savePlayer(Player $player): void
  {
    $playerName = $player->getName();
    Main::getInstance()->getPlayerData()->setNested("$playerName.Health", $player->getHealth());
    Main::getInstance()->getPlayerData()->setNested("$playerName.MaxHealth", $player->getMaxHealth());
    Main::getInstance()->getPlayerData()->save();
  }

   public function getSource(): Main
  {
    return $this->source;
  }
}