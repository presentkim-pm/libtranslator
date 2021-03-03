<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection SpellCheckingInspection
 * @noinspection PhpDocSignatureInspection
 */

declare(strict_types=1);

namespace kim\present\lib\translator;

use kim\present\converter\locale\LocaleConverter;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use RuntimeException;

use function array_keys;
use function array_merge;
use function explode;
use function is_dir;
use function method_exists;
use function preg_match;
use function scandir;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;

class Translator{
    /** Owner plugin */
    protected PluginBase $plugin;

    /** Locale name (ISO_639-3 code) */
    protected string $defaultLocale;

    /** @var Language[] Language instances */
    protected array $languages = [];

    public function __construct(PluginBase $owningPlugin){
        $this->plugin = $owningPlugin;

        $this->loadAllLocale();
        $this->defaultLocale = Server::getInstance()->getLanguage()->getLang();
    }

    /**
     * @param string      $str original string
     * @param mixed[]     $params translate parameters
     * @param string|null $locale translate language locale. if null, translate by default language
     *
     * @return string the translated string
     */
    public function translate(string $str, array $params = [], ?string $locale = null) : string{
        $params = array_merge($params, DefautParams::getAll());
        $lang = $this->getLanguage($locale);
        if($lang !== null){
            if(strpos($str, "%") === false){
                $str = $lang->get($str);
            }else{
                $parts = explode("%", $str);
                $str = "";
                $lastTranslated = false;
                foreach($parts as $_ => $part){
                    $new = $lang->get($part);
                    if(strlen($str) > 0 && $part === $new && !$lastTranslated){
                        $str .= "%";
                    }
                    $lastTranslated = $part !== $new;

                    $str .= $new;
                }
            }
        }
        foreach($params as $i => $param){
            $str = str_replace("{%$i}", (string) $param, $str);
        }
        return $str;
    }

    /**
     * @param string             $str original string
     * @param mixed[]            $params translate parameters
     * @param CommandSender|null $sender translate target sender. if null, translate by default language
     *
     * @return string
     */
    public function translateTo(string $str, array $params, ?CommandSender $sender = null) : string{
        $locale = null;
        if($sender !== null && method_exists($sender, 'getLocale') && !Server::getInstance()->isLanguageForced()){
            $locale = LocaleConverter::convertIEFT($sender->getLocale());
        }
        return $this->translate($str, $params, $locale);
    }

    /** @return Language|null if $locale is null, return default language */
    public function getLanguage(?string $locale = null) : ?Language{
        $locale = $locale === null ? $this->getDefaultLocale() : strtolower($locale);
        return $this->languages[$locale] ?? $this->languages[Server::getInstance()->getLanguage()->getLang()] ?? $this->languages["eng"] ?? null;
    }

    /** @return Language[] */
    public function getLangList() : array{
        return $this->languages;
    }

    public function getDefaultLocale() : string{
        return $this->defaultLocale;
    }

    /** @return string[] */
    public function getLocaleList() : array{
        return array_keys($this->getLangList());
    }

    public function setDefaultLocale(string $locale) : bool{
        $locale = strtolower($locale);
        if(!isset($this->languages[$locale]))
            return false;

        $this->defaultLocale = strtolower($locale);
        return true;
    }

    /** Load all locale file from plugin data folder */
    public function loadAllLocale() : void{
        $path = $this->plugin->getDataFolder() . "locale/";
        if(!is_dir($path))
            throw new RuntimeException("Language directory $path does not exist or is not a directory");

        foreach(scandir($path, SCANDIR_SORT_NONE) as $_ => $filename){
            if(!preg_match('/^([a-zA-Z]{3})\.ini$/', $filename, $matches) || !isset($matches[1]))
                continue;

            $this->languages[$matches[1]] = Language::loadFrom($path . $filename, $matches[1]);
        }
    }

    public function getPlugin() : PluginBase{
        return $this->plugin;
    }
}