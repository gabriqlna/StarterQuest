<?php

declare(strict_types=1);

namespace StarterQuest\Addon;

use Ifera\ScoreHud\addon\Addon;
use Ifera\ScoreHud\event\PlayerTagUpdateEvent;
use pocketmine\player\Player;
use StarterQuest\Main;

class StarterQuestAddon extends Addon {

    public function getProcessedTags(Player $player): array {
        $manager = Main::getInstance()->getQuestManager();
        return [
            "{starterquest_progress}" => $manager->getScoreTag($player)
        ];
    }
}

