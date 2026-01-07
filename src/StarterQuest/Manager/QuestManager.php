<?php

namespace StarterQuest\Manager;

use StarterQuest\Main;
use StarterQuest\Utils\SimpleFormTrait;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use pocketmine\item\Item;

class QuestManager {
    use SimpleFormTrait;

    private Main $plugin;
    private array $quests = [];
    private Config $playersConfig;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        // Carrega as quests da config
        $questData = new Config($plugin->getDataFolder() . "quests.yml", Config::YAML);
        $this->quests = $questData->get("quests", []);
        
        // Base de dados simples (JSON) para progresso
        // Para servidores grandes, recomenda-se SQLite, mas JSON serve para o escopo
        $this->playersConfig = new Config($plugin->getDataFolder() . "data/players.json", Config::JSON);
    }

    public function getPlayerQuestId(Player $player): int {
        return $this->playersConfig->get($player->getUniqueId()->toString(), 1);
    }

    public function setPlayerQuestId(Player $player, int $id): void {
        $this->playersConfig->set($player->getUniqueId()->toString(), $id);
        $this->playersConfig->save();
    }

    public function isCompleted(Player $player): bool {
        return $this->getPlayerQuestId($player) > count($this->quests);
    }

    public function getCurrentQuest(Player $player): ?array {
        $id = $this->getPlayerQuestId($player);
        return $this->quests[$id] ?? null;
    }

    // Verifica progresso e avança se necessário
    public function checkProgress(Player $player, string $type, string $targetItemName, int $amount = 1): void {
        if ($this->isCompleted($player)) return;

        $quest = $this->getCurrentQuest($player);
        if (!$quest) return;

        // Verifica tipo e alvo
        if ($quest['type'] !== $type) return;
        
        // Verifica se o item alvo bate (ex: 'log' está contido em 'oak_log')
        if (!str_contains(strtolower($targetItemName), strtolower($quest['target']))) return;

        // Nota: Neste sistema simplificado, não estamos salvando contagem parcial (1/3 logs)
        // O jogador precisa quebrar/craftar o total na sessão ou ação, ou simplificamos para ação única
        // Para o tutorial ser fluido, vamos assumir que a ação conta como "progresso feito" 
        // ou implementar um contador temporário na sessão. 
        // ABRORDAGEM: Contador na Sessão do Plugin (Memória RAM)
        
        $currentProgress = $this->getSessionProgress($player) + $amount;
        $this->setSessionProgress($player, $currentProgress);

        if ($currentProgress >= $quest['amount']) {
            $this->completeQuest($player, $quest);
        } else {
            // Envia popup de progresso
            $player->sendTip("§eProgresso: §f{$currentProgress}/{$quest['amount']}");
        }
    }

    private array $sessionProgress = [];

    private function getSessionProgress(Player $player): int {
        return $this->sessionProgress[$player->getName()] ?? 0;
    }

    private function setSessionProgress(Player $player, int $amount): void {
        $this->sessionProgress[$player->getName()] = $amount;
    }

    private function completeQuest(Player $player, array $quest): void {
        $this->setSessionProgress($player, 0); // Reseta contador parcial
        
        // Dá recompensas
        foreach ($quest['rewards'] as $rewardString) {
            $parts = explode(":", $rewardString);
            if ($parts[0] === "item") {
                $item = StringToItemParser::getInstance()->parse($parts[1]);
                if ($item) {
                    $item->setCount((int)($parts[2] ?? 1));
                    $player->getInventory()->addItem($item);
                }
            } elseif ($parts[0] === "xp") {
                $player->getXpManager()->addXp((int)$parts[1]);
            } elseif ($parts[0] === "msg") {
                $player->sendMessage($parts[1]);
            }
        }

        // Som e Mensagem
        $sound = $this->plugin->getConfig()->getNested("settings.complete-sound");
        // Tocar som (simplificado, requer pacote de rede, omitido para brevidade)
        
        $msg = str_replace("{QUEST}", $quest['name'], $this->plugin->getConfig()->getNested("messages.completed"));
        $player->sendMessage($this->plugin->getConfig()->getNested("settings.prefix") . $msg);

        // Avança ID
        $this->setPlayerQuestId($player, $this->getPlayerQuestId($player) + 1);

        // Verifica se acabou tudo
        if ($this->isCompleted($player)) {
            $player->sendMessage($this->plugin->getConfig()->getNested("messages.all-finished"));
            // Dispara fogos de artifício ou efeito aqui se desejar
        } else {
            // Mostra a próxima quest automaticamente
            $next = $this->getCurrentQuest($player);
            if ($next) {
                $player->sendTitle("§6Nova Missão", "§f" . $next['name']);
            }
        }
    }

    // --- GUI FORM ---
    public function openQuestForm(Player $player): void {
        $currentId = $this->getPlayerQuestId($player);
        $total = count($this->quests);

        $form = $this->createSimpleForm(function(Player $player, $data){
            // Callback opcional, botão fechar apenas fecha
        });

        $form->setTitle("§lTutorial Iniciante");
        
        if ($this->isCompleted($player)) {
            $form->setContent("§aVocê completou todo o tutorial!\n§7Agora você está livre para explorar o servidor.");
            $form->addButton("Fechar");
        } else {
            $quest = $this->getCurrentQuest($player);
            $progress = $this->getSessionProgress($player);
            
            $txt = "§eMissão Atual: §f{$quest['name']}\n";
            $txt .= "§7{$quest['description']}\n\n";
            $txt .= "§bObjetivo: §f{$quest['type']} {$quest['amount']}x {$quest['target']}\n";
            $txt .= "§aProgresso: §f{$progress} / {$quest['amount']}\n\n";
            $txt .= "§6Recompensas: §fItens e XP";

            $form->setContent($txt);
            
            // Botão com ícone da quest
            $form->addButton("§lOK, Entendi!", 0, $quest['icon'] ?? "textures/items/book_written");
        }

        $player->sendForm($form);
    }
    
    // Suporte para ScoreHud
    public function getScoreTag(Player $player): string {
        if ($this->isCompleted($player)) return "§aConcluído";
        $q = $this->getCurrentQuest($player);
        return $q ? "§e" . $q['name'] : "§7Carregando...";
    }
}
