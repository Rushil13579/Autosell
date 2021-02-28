<?php

namespace Rushil13579\Autosell;

use pocketmine\{Server, Player};

use pocketmine\plugin\PluginBase;

use pocketmine\command\{Command, CommandSender};

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\item\Item;

use pocketmine\utils\{Config, TextFormat as C};

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {

  public $cfg;
  public $autosell = [];

  public const PLUGIN_PREFIX = '§3[§bAutoSell§3]';

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);

    $this->saveDefaultConfig();
    $this->cfg = $this->getConfig();
  }

  /**
  *@priority HIGEHST
  **/

  public function onBreak(BlockBreakEvent $event){
    $player = $event->getPlayer();

    if($event->isCancelled()){
      return null;
    }

    $full = null;
    if(isset($this->autosell[$player->getName()])){

      foreach($event->getDrops() as $drop){
        if(!$player->getInventory()->canAddItem($drop)){
          $full = 'true';
        }
      }

      if($full == 'true'){
        $this->autosell($player);
      }
    }
  }

  public function autosell($player){
    $inv = $player->getInventory();
    $list = $this->cfg->get('prices');
    $totalsum = 0;

    foreach($inv->getContents() as $item){
      $sum = 0;
      $itemData = $item->getId() . ':' . $item->getDamage();
      if(isset($list[$itemData])){
        $sum = $sum + $list[$itemData] * $item->getCount();
        $inv->removeItem($item);
        EconomyAPI::getInstance()->addMoney($player, $sum);
        $totalsum = $totalsum + $sum;
      }
    }

    if($totalsum !== 0){
      $player->sendMessage(C::colorize(str_replace(['{prefix}', '{amount}'], [self::PLUGIN_PREFIX, $totalsum], $this->cfg->get('autosell-sold-msg'))));
    }
  }

  public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool {

    switch($cmd->getName()){
      case 'autosell':

      if(!$sender instanceof Player){
        $sender->sendMessage('§cPlease use this command in-game');
        return false;
      }

      if(!$sender->hasPermission('autosell.command')){
        $sender->sendMessage(C::colorize(str_replace('{prefix}', self::PLUGIN_PREFIX, $this->cfg->get('autosell-no-permission-msg'))));
        return false;
      }

      if(isset($this->autosell[$sender->getName()])){
        unset($this->autosell[$sender->getName()]);
        $sender->sendMessage(C::colorize(str_replace('{prefix}', self::PLUGIN_PREFIX, $this->cfg->get('autosell-disabled-msg'))));
      } else {
        $this->autosell[$sender->getName()] = $sender->getName();
        $sender->sendMessage(C::colorize(str_replace('{prefix}', self::PLUGIN_PREFIX, $this->cfg->get('autosell-enabled-msg'))));
      }
    }
    return true;
  }
}
