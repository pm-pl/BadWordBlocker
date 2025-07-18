<?php

/**
 * BadWordBlocker | plugin main class
 */

namespace surva\badwordblocker;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use surva\badwordblocker\filter\FilterManager;
use surva\badwordblocker\form\ImportSelectForm;
use surva\badwordblocker\util\Messages;
use Symfony\Component\Filesystem\Path;

class BadWordBlocker extends PluginBase
{
    /**
     * @var \pocketmine\utils\Config default language config
     */
    private Config $defaultMessages;

    /**
     * @var Config[] available language configs
     */
    private array $translationMessages;

    /**
     * @var \surva\badwordblocker\filter\FilterManager class for managing registered filters and check messages
     */
    private FilterManager $filterManager;

    /**
     * @var mixed[] available sources for lists to import
     */
    private array $availableListSources;

    /**
     * Plugin has been enabled, initial setup
     */
    public function onEnable(): void
    {
        $this->saveDefaultConfig();

        $this->saveResource(Path::join("languages", "en.yml"), true);
        $this->defaultMessages = new Config(Path::join($this->getDataFolder(), "languages", "en.yml"));
        $this->loadLanguageFiles();

        $this->filterManager = new FilterManager($this);

        $this->saveResource("list_sources.yml", true);
        $listSourcesConfig = new Config(Path::join($this->getDataFolder(), "list_sources.yml"));
        $this->availableListSources = $listSourcesConfig->getNested("sources");

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    /**
     * Listen for plugin command
     *
     * @param  \pocketmine\command\CommandSender  $sender
     * @param  \pocketmine\command\Command  $command
     * @param  string  $label
     * @param  string[]  $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (count($args) < 1) {
            return false;
        }

        if ($args[0] === "import") {
            if (!($sender instanceof Player)) {
                return false;
            }

            $messages = new Messages($this, $sender);
            $sender->sendForm(new ImportSelectForm($this, $messages, $this->availableListSources));

            return true;
        }

        return false;
    }

    /**
     * Shorthand to send a translated message to a command sender
     *
     * @param  \pocketmine\command\CommandSender  $sender
     * @param  string  $key
     * @param  string[]  $replaces
     *
     * @return void
     */
    public function sendMessage(CommandSender $sender, string $key, array $replaces = []): void
    {
        $messages = new Messages($this, $sender);

        $sender->sendMessage($messages->getMessage($key, $replaces));
    }

    /**
     * Load all available language files
     *
     * @return void
     */
    private function loadLanguageFiles(): void
    {
        $resources = $this->getResources();
        $this->translationMessages = [];

        foreach ($resources as $resource) {
            $normalizedPath = Path::normalize($resource->getPathname());
            if (!preg_match("/languages\/[a-z]{2}.yml$/", $normalizedPath)) {
                continue;
            }

            preg_match("/^[a-z][a-z]/", $resource->getFilename(), $fileNameRes);

            if (!isset($fileNameRes[0])) {
                continue;
            }

            $langId = $fileNameRes[0];

            $this->saveResource(Path::join("languages", $langId . ".yml"), true);
            $this->translationMessages[$langId] = new Config(
                Path::join($this->getDataFolder(), "languages", $langId . ".yml")
            );
        }
    }

    /**
     * @return \surva\badwordblocker\filter\FilterManager
     */
    public function getFilterManager(): FilterManager
    {
        return $this->filterManager;
    }

    /**
     * @return Config[]
     */
    public function getTranslationMessages(): array
    {
        return $this->translationMessages;
    }

    /**
     * @return \pocketmine\utils\Config
     */
    public function getDefaultMessages(): Config
    {
        return $this->defaultMessages;
    }
}
