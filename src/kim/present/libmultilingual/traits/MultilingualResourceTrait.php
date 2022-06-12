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
 * @noinspection PhpDocSignatureInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\libmultilingual\traits;

use pocketmine\lang\Language;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;

use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function mkdir;
use function sprintf;
use function stream_copy_to_stream;

/** This trait override most methods in the {@link PluginBase} abstract class. */
trait MultilingualResourceTrait{
    /**
     * It works like getResource(), but automatically convert resource path according to server language.
     *
     * @param string $resourcePattern The resource path string containing %s (it will replace to locale code)
     *
     * @return resource|null Resource data, or null
     *
     * @see PluginBase::getResource()
     */
    public function getResourceByLanguage(string $resourcePattern){
        /** @var PluginBase $this */
        return $this->getResource(sprintf($resourcePattern, $this->getServer()->getLanguage()->getLang()))
            ?? $this->getResource(sprintf($resourcePattern, Language::FALLBACK_LANGUAGE));
    }

    /**
     * It works like saveResource(), but automatically convert resource path according to server language.
     *
     * @param string $filename The name of the file to be saved
     * @param string $resourcePattern The resource path string containing %s (it will replace to locale code)
     * @param bool   $replace Whether to replace the file if it already exists
     *
     * @see PluginBase::saveResource()
     */
    public function saveResourceByLanguage(string $filename, string $resourcePattern, bool $replace = false) : bool{
        /** @var PluginBase $this */
        $out = $this->getDataFolder() . $filename;
        if(file_exists($out) && !$replace){
            return false;
        }

        $resource = $this->getResourceByLanguage($resourcePattern);
        if($resource === null){
            return false;
        }

        $dir = dirname($out);
        if(!file_exists($dir) && !mkdir($dir, 0777, true)){
            return false;
        }

        $fp = fopen($out, "wb");
        if($fp === false){
            throw new AssumptionFailedError("fopen() should not fail with wb flags");
        }

        $ret = stream_copy_to_stream($resource, $fp) > 0;
        fclose($fp);
        fclose($resource);
        return $ret;
    }
}