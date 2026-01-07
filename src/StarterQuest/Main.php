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

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveDefaultConfig();
        $this->saveResource("quests.yml");

        $this->questManager = new QuestManager($this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        
        $this->getLogger()->info("StarterQuest ativado com sucesso!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "starter") {
            if (!$sender instanceof Player) return false;
            $this->questManager->openQuestForm($sender);
            return true;
        }
        return false;
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    public function getQuestManager(): QuestManager {
        return $this->questManager;
    }
}
