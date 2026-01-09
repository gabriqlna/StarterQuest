<?php

declare(strict_types=1);

namespace StarterQuest\Manager;

use StarterQuest\Main;
use StarterQuest\Utils\SimpleFormTrait; // Comentei para evitar erro se você não tiver o arquivo Utils
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use pocketmine\item\Item;

class QuestManager {
    // use SimpleFormTrait; // Reative se tiver o arquivo do Trait

    private Main $plugin;
    private array $quests = [];
    private Config $playersConfig;
    private array $sessionProgress = []; // Movi para cima para ficar organizado

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        
        // 1. Carrega as quests
        $questData = new Config($plugin->getDataFolder() . "quests.yml", Config::YAML);
        $this->quests = $questData->get("quests", []);
        
        // 2. Cria a subpasta 'data' se ela não existir
        $dataPath = $plugin->getDataFolder() . "data/";
        if(!is_dir($dataPath)){
            @mkdir($dataPath, 0777, true);
        }
        
        // 3. Carrega o arquivo de players
        $this->playersConfig = new Config($dataPath . "players.json", Config::JSON);
    }

    public function getPlayerQuestId(Player $player): int {
        return (int)$this->playersConfig->get($player->getUniqueId()->toString(), 0); // Alterei padrão para 0 (índice de array)
    }

    public function setPlayerQuestId(Player $player, int $id): void {
        $this->playersConfig->set($player->getUniqueId()->toString(), $id);
        $this->playersConfig->save();
        $this->sessionProgress[$player->getName()] = 0; // Reseta progresso ao mudar ID
    }

    public function isCompleted(Player $player): bool {
        // Verifica se o ID atual é maior ou igual ao número total de quests
        return $this->getPlayerQuestId($player) >= count($this->quests);
    }

    public function getCurrentQuest(Player $player): ?array {
        $id = $this->getPlayerQuestId($player);
        return $this->quests[$id] ?? null;
    }

    public function getSessionProgress(Player $player): int {
        return $this->sessionProgress[$player->getName()] ?? 0;
    }

    public function setSessionProgress(Player $player, int $amount): void {
        $this->sessionProgress[$player->getName()] = $amount;
    }

    // --- LÓGICA PRINCIPAL ---

    public function checkProgress(Player $player, string $type, string $targetItemName, int $amount = 1): void {
        if ($this->isCompleted($player)) return;
        
        $quest = $this->getCurrentQuest($player);
        if ($quest === null) return;

        // Verifica se o tipo da ação (break, place, etc) coincide
        if ($quest['type'] !== $type) return;
        
        // Normalização para comparação
        $cleanTarget = strtolower(str_replace(" ", "_", (string)$quest['target']));
        $cleanBlock = strtolower(str_replace(" ", "_", $targetItemName));

        // Verifica se o bloco interagido é o alvo
        if (!str_contains($cleanBlock, $cleanTarget)) return;

        $newProgress = $this->getSessionProgress($player) + $amount;
        $this->setSessionProgress($player, $newProgress);

        // Se atingiu ou passou o objetivo, completa a missão
        if ($newProgress >= (int)$quest['amount']) {
            $this->completeQuest($player, $quest);
        } else {
            // Apenas atualiza o progresso visual
            $player->sendTip("§eProgresso: §f" . $newProgress . " / " . $quest['amount']);
            $this->plugin->getEventListener()->updateScoreboard($player);
        }
    }

    private function completeQuest(Player $player, array $quest): void {
        $this->setSessionProgress($player, 0); 
        
        // Dá recompensas
        if(isset($quest['rewards'])){
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
                    $player->sendMessage(str_replace("_", " ", $parts[1]));
                }
            }
        }

        // Som e Mensagem
        $msg = str_replace("{QUEST}", $quest['name'], $this->plugin->getConfig()->getNested("messages.completed", "§aVocê completou: {QUEST}"));
        $player->sendMessage($msg);
        $player->sendTitle("§aMissão Concluída!", "§7Recompensas recebidas.");

        // Avança ID
        $this->setPlayerQuestId($player, $this->getPlayerQuestId($player) + 1);
        
        // Atualiza Scoreboard (AQUI FALTAVA O PONTO E VÍRGULA)
        $this->plugin->getEventListener()->updateScoreboard($player);

        // Verifica se acabou tudo
        if ($this->isCompleted($player)) {
            $player->sendMessage("§6§lParabéns! §r§aVocê completou todas as missões do tutorial.");
        } else {
            // Mostra a próxima quest
            $next = $this->getCurrentQuest($player);
            if ($next) {
                $player->sendTitle("§6Nova Missão", "§f" . $next['name']);
            }
        }
    }

    // --- GUI FORM ---
    public function openQuestForm(Player $player): void {
        // Implementação simplificada sem Trait para evitar erros de arquivo faltando
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        if ($api === null) return;

        $form = new \jojoe77777\FormAPI\SimpleForm(function(Player $player, $data){
            // Callback vazio, botão apenas fecha
        });

        $form->setTitle("§lTutorial Iniciante");
        
        if ($this->isCompleted($player)) {
            $form->setContent("§aVocê completou todo o tutorial!\n§7Agora você está livre para explorar o servidor.");
            $form->addButton("Fechar");
        } else {
            $quest = $this->getCurrentQuest($player);
            if ($quest !== null) {
                $progress = $this->getSessionProgress($player);
                
                $txt = "§eMissão Atual: §f{$quest['name']}\n";
                $txt .= "§7{$quest['description']}\n\n";
                $txt .= "§bObjetivo: §f{$quest['type']} {$quest['amount']}x {$quest['target']}\n";
                $txt .= "§aProgresso: §f{$progress} / {$quest['amount']}\n\n";
                $txt .= "§6Recompensas: §fItens e XP";

                $form->setContent($txt);
                $form->addButton("§lOK, Entendi!");
            }
        }
        $player->sendForm($form);
    }
    
    // Suporte para ScoreHud
    public function getScoreTag(Player $player): string {
        if ($this->isCompleted($player)) return "§aConcluído";
        $q = $this->getCurrentQuest($player);
        if ($q === null) return "Carregando...";
        
        return $q['name'] . " " . $this->getSessionProgress($player) . "/" . $q['amount'];
    }
}

