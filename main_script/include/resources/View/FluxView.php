<?php

namespace resources\View;

use InvalidArgumentException;

class FluxView
{
    public static function render(string $view, array $data = []): string
    {
        $path = self::resolvePath($view);
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Flux view "%s" not found.', $view));
        }

        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }

        ob_start();
        include $path;

        return ob_get_clean();
    }

    private static function resolvePath(string $view): string
    {
        $view = str_replace(['::', '.'], DIRECTORY_SEPARATOR, $view);

        return RESOURCES_PATH . 'Flux' . DIRECTORY_SEPARATOR . $view . '.php';
    }
}
