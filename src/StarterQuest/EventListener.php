<?php

namespace StarterQuest;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        // Se for novato (quest id 1), abre o menu automaticamente
        if ($this->plugin->getQuestManager()->getPlayerQuestId($player) === 1) {
            // Delay pequeno para garantir carregamento
            $this->plugin->getScheduler()->scheduleDelayedTask(new \pocketmine\scheduler\ClosureTask(
                fn() => $this->plugin->getQuestManager()->openQuestForm($player)
            ), 40);
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        if ($event->isCancelled()) return;
        $player = $event->getPlayer();
        
        // CORREÇÃO: Usamos o ID interno do bloco para ser mais preciso
        $block = $event->getBlock();
        $blockName = $block->getName(); 
        $blockTypeId = $block->getTypeId(); // Método mais seguro em APIs recentes

        // Log de debug opcional (pode remover depois): 
        // $player->sendTip("Bloco quebrado: " . $blockName);

        $this->plugin->getQuestManager()->checkProgress($player, "break", $blockName, 1);
    }

    public function onPlace(BlockPlaceEvent $event): void {
        if ($event->isCancelled()) return;
        $player = $event->getPlayer();

        // CORREÇÃO: No PM5, usamos a transação para pegar os blocos colocados
        foreach($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]){
            $blockName = $block->getName();
            $this->plugin->getQuestManager()->checkProgress($player, "place", $blockName, 1);
        }
    }


    public function onCraft(CraftItemEvent $event): void {
        if ($event->isCancelled()) return;
        $player = $event->getPlayer();
        
        foreach ($event->getOutputs() as $item) {
            $name = $item->getVanillaName(); // Ex: Crafting Table
            $count = $item->getCount();
            $this->plugin->getQuestManager()->checkProgress($player, "craft", $name, $count);
        }
    }

    public function onSleep(PlayerBedEnterEvent $event): void {
        if ($event->isCancelled()) return;
        $this->plugin->getQuestManager()->checkProgress($event->getPlayer(), "sleep", "bed", 1);
    }

    // --- PROTEÇÕES ---

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;

        if (!$this->plugin->getConfig()->getNested("settings.protection.enabled")) return;

        // Se o jogador ainda não completou o tutorial
        if (!$this->plugin->getQuestManager()->isCompleted($entity)) {
            // PvP Proteção
            if ($event instanceof EntityDamageByEntityEvent) {
                $attacker = $event->getDamager();
                if ($attacker instanceof Player) {
                    $event->cancel();
                    $attacker->sendMessage($this->plugin->getConfig()->getNested("messages.protection-hit"));
                    return;
                }
            }

            // Redução de dano PvE
            if ($this->plugin->getConfig()->getNested("settings.protection.reduced-damage")) {
                $event->setBaseDamage($event->getFinalDamage() * 0.5); // 50% menos dano
            }
        }
    }
    
    // Impede o novato de bater em outros também
    public function onAttack(EntityDamageByEntityEvent $event): void {
        $attacker = $event->getDamager();
        if ($attacker instanceof Player && !$this->plugin->getQuestManager()->isCompleted($attacker)) {
            if ($event->getEntity() instanceof Player && $this->plugin->getConfig()->getNested("settings.protection.no-pvp")) {
                $event->cancel();
                $attacker->sendMessage($this->plugin->getConfig()->getNested("messages.protection-active"));
            }
        }
    }
}

