<?php

namespace StarterQuest;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use StarterQuest\Manager\QuestManager;

class Main extends PluginBase {

     private static self $instance;
    private QuestManager $questManager;
    private EventListener $eventListener; // Adicionamos esta linha

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveDefaultConfig();
        $this->saveResource("quests.yml");

        // 1. Criamos o Manager primeiro
        $this->questManager = new QuestManager($this);

        // 2. Guardamos o EventListener em uma variável antes de registrar
        $this->eventListener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);

        $this->getLogger()->info("StarterQuest ativado com sucesso!");
    }

    // Adicione esta função para o QuestManager conseguir chamar o EventListener
    public function getEventListener(): EventListener {
        return $this->eventListener;
    }

    public function getQuestManager(): QuestManager {
        return $this->questManager;
    }

    public static function getInstance(): self {
        return self::$instance;
    }


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "starter") {
            if (!$sender instanceof Player) return false;
            $this->questManager->openQuestForm($sender);
            return true;
        }
        return false;
    }
}
