<?php
namespace Core;
class Autoloder
{
    public static function register($prepend = FALSE)
    {
        if(version_compare(phpversion(), '5.3.0', '>=')) {
            spl_autoload_register([__CLASS__, 'autoload'], TRUE, $prepend);
        } else {
            spl_autoload_register([__CLASS__, 'autoload']);
        }
    }

    public static function autoload($className)
    {
        if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
            return TRUE;
        }

        if (strpos($className, 'App\\Http\\Controllers\\Admin\\') === 0) {
            $legacyClass = substr($className, strlen('App\\Http\\Controllers\\Admin\\'));
            $legacyPath = ROOT_PATH . 'include' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $legacyClass . '.php';
            if (is_file($legacyPath)) {
                require_once $legacyPath;
                if (!class_exists($className, false) && class_exists($legacyClass, false)) {
                    class_alias($legacyClass, $className);
                }
                if (class_exists($className, false)) {
                    return TRUE;
                }
            }
        }

        $fullpath = self::getFullPath($className);
        if(is_file($fullpath)) {
            require($fullpath);
        } else {
            return false;
        }
        return TRUE;
    }

    public static function getFullPath($className)
    {
        return ROOT_PATH."include".DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
    }
}
Autoloder::register();
ErrorHandler::getInstance();
