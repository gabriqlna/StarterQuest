<?php

namespace StarterQuest\Utils;

use pocketmine\form\Form;
use pocketmine\player\Player;

trait SimpleFormTrait {

    public function createSimpleForm(callable $handler): Form {
        return new class($handler) implements Form {
            private $data = ["type" => "form", "title" => "", "content" => "", "buttons" => []];
            private $handler;

            public function __construct(callable $handler) { $this->handler = $handler; }
            
            public function setTitle(string $title): void { $this->data["title"] = $title; }
            public function setContent(string $content): void { $this->data["content"] = $content; }
            
            public function addButton(string $text, int $imageType = -1, string $imagePath = ""): void {
                $btn = ["text" => $text];
                if($imageType !== -1) {
                    $btn["image"] = ["type" => $imageType === 0 ? "path" : "url", "data" => $imagePath];
                }
                $this->data["buttons"][] = $btn;
            }

            public function jsonSerialize(): mixed { return $this->data; }
            
            public function handleResponse(Player $player, mixed $data): void {
                ($this->handler)($player, $data);
            }
        };
    }
}
