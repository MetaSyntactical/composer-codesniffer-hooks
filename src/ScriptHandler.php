<?php

namespace MetaSyntactical\CodeSniffer\Composer;

use Composer\Config;
use Composer\Script\Event;

class ScriptHandler
{
    private static function arrayResolvePath($path, $array)
    {
        $paths = explode(':', $path);
        $currentElement = $array;
        foreach ($paths as $idx) {
            if (!is_array($currentElement)) {
                return null;
            }
            if (!isset($currentElement[$idx])) {
                return null;
            }
            $currentElement = $currentElement[$idx];
        }
        return $currentElement;
    }

    public static function addPhpCsToPreCommitHook(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        $dependencyToResolve = self::arrayResolvePath("codesniffer:standard:dependency", $extra);
        if (is_null($dependencyToResolve)) {
            $event->getIO()->writeError(
                "Cannot install pre-commit hooks. No CodeSniffer standard configured at extra->codesniffer->standard."
            );
            return;
        }
        if (is_array($dependencyToResolve)) {
            $event->getIO()->writeError(
                "Cannot install pre-commit hooks. Configuration of extra->codesniffer->standard->dependency is invalid."
            );
            return;
        }
        $originFile = getcwd().'/.git/hooks/pre-commit';
        if (!is_dir(dirname($originFile))) {
            mkdir(dirname($originFile), 0777, true);
        }
        $templateContent = file_get_contents(__DIR__.'/templates/git/hooks/pre-commit-phpcs');
        $originContent = '';
        if (file_exists($originFile)) {
            $originContent = file_get_contents($originFile);
        }
        if (strpos($originContent, '# BEGIN:metasyntactical/composer-codesniffer-hooks') !== false) {
            return;
        }
        $newContent = $originContent;
        if (mb_strlen($originContent)) {
            $newContent .= "\n";
        }
        $newContent .= str_replace(
            array(
                "{STANDARDPATH}"
            ),
            array(
                $event->getComposer()->getConfig()->get("vendor-dir", Config::RELATIVE_PATHS)."/${dependencyToResolve}/ruleset.xml"
            ),
            $templateContent
        );
        file_put_contents($originFile, $newContent);
        $perms = fileperms($originFile);
        chmod($originFile, $perms | 0x0040 | 0x008 | 0x0001);
        clearstatcache(null, $originFile);
    }
}
