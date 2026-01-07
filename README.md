# 🚀 StarterQuest

**StarterQuest** é um plugin essencial para servidores **Survival SMP (Bedrock)** desenvolvido para o **PocketMine-MP (API 5.x)**. Ele foca na retenção de usuários, guiando jogadores iniciantes através de missões sequenciais intuitivas com interface visual (GUI) nativa.

![API Version](https://img.shields.io/badge/API-5.x-blue?style=flat-square)
![PHP Version](https://img.shields.io/badge/PHP-8.1+-orange?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## 🎯 Por que usar o StarterQuest?

O maior desafio de um servidor Survival é o "abandono precoce" (jogadores que entram e saem por não saberem o que fazer). O StarterQuest resolve isso ao:
* **Guiar o Início:** Oferece objetivos claros logo no primeiro login.
* **Interface Visual:** Usa a FormAPI nativa com ícones de blocos e itens do Bedrock.
* **Proteção Inicial:** Mantém o jogador focado no aprendizado, protegendo-o de PvP e danos excessivos enquanto é um novato.
* **Senso de Conquista:** Recompensas imediatas (Itens, XP) incentivam o progresso.

---

## ✨ Funcionalidades Principais

* **📺 GUI Nativa:** Interface limpa sem necessidade de comandos chatos para ver o progresso.
* **📦 Missões Sequenciais:** O jogador só avança para o "Abrigo" após aprender a coletar "Madeira".
* **🛡️ Proteção Dinâmica:** PvP desativado e dano reduzido automaticamente para quem está no tutorial.
* **💾 Persistência por UUID:** O progresso é salvo mesmo se o jogador mudar de nome (Nick).
* **⚡ Alta Performance:** Monitoramento baseado em eventos nativos do PocketMine, sem tasks em loop.
* **📊 Integração com ScoreHud:** Suporte nativo para exibir a missão atual na scoreboard.

---

## 🛠️ Comandos e Permissões

| Comando | Descrição | Permissão | Padrão |
| :--- | :--- | :--- | :--- |
| `/starter` | Abre a interface de missões atuais. | `starterquest.use` | Todos |
| `/starterquest reload` | Recarrega as configurações e quests. | `starterquest.admin` | OP |

---
### 🛡️ Configurações de Proteção (`config.yml`)

```yaml
settings:
  protection:
    enabled: true
    no-pvp: true        # Impede iniciantes de atacar ou serem atacados
    reduced-damage: true # Reduz 50% do dano de queda e mobs
```
---
### 📂 Estrutura de Dados e Performance

O **StarterQuest** utiliza um sistema híbrido de armazenamento para garantir que o servidor mantenha o desempenho máximo, mesmo com muitos jogadores simultâneos.

* **Persistência (Disco):** * Os dados permanentes são armazenados em: `plugin_data/StarterQuest/data/players.json`.
    * Este arquivo guarda o **ID da missão atual** do jogador vinculado ao seu **UUID**.
    * O salvamento em disco ocorre apenas em eventos críticos (conclusão de missão ou logout), evitando sobrecarga de I/O.

* **Volatilidade (Cache de Memória):**
    * O progresso parcial das missões (ex: "quebrou 2 de 5 blocos") é mantido inteiramente em **cache de memória RAM**.
    * **Vantagem:** Isso garante que não ocorra lag de escrita no disco durante o gameplay, permitindo que o plugin monitore centenas de ações por segundo de forma instantânea.

---

> **Nota técnica:** Em caso de reinicialização forçada do servidor, o jogador retoma da missão onde parou, devendo apenas reiniciar a contagem parcial daquela missão específica.

---

### 🚀 Instalação

Siga os passos abaixo para instalar o plugin corretamente em seu servidor:

1. **Obtenha o arquivo:** Baixe o arquivo `.phar` pré-compilado ou compile a pasta `src`.
2. **Upload:** Coloque o arquivo `.phar` (ou a pasta do plugin) dentro do diretório `plugins/` do seu servidor PocketMine-MP.
3. **Reinicialização:** Reinicie o servidor completamente para que as pastas de dados e arquivos de configuração sejam gerados.
4. **Customização (Opcional):** Edite o arquivo `quests.yml` para adaptar as missões, objetivos e recompensas ao tema e à economia do seu servidor.

---

### 📊 Integração com ScoreHud

O **StarterQuest** oferece suporte nativo para exibir o progresso do jogador em tempo real na Scoreboard através do plugin **ScoreHud**.

Para exibir o nome da missão atual do jogador, utilize a seguinte tag em sua configuração de skin do ScoreHud:

> **Tag:** `{starterquest_progress}`



* **Exemplo de uso:**
  `§fMissão: §a{starterquest_progress}`

---

## ⚙️ Configuração

O plugin utiliza dois arquivos de configuração principais para máxima flexibilidade:

### Quests Customizáveis (`quests.yml`)
Você pode criar quantas missões quiser. Tipos suportados: `break`, `place`, `craft`, `sleep`.

```yaml
quests:
  1:
    name: "Lenhador"
    description: "Colete 3 madeiras."
    type: "break"
    target: "log"
    amount: 3
    icon: "textures/blocks/log_oak"
    rewards:
      - "item:apple:5"
      - "xp:100"
