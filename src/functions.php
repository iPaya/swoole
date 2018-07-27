<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

/**
 * @param string $extension
 * @return null|string
 */
function getMimeTypeByExtension(string $extension): ?string
{
    $map = require(__DIR__ . '/mimeTypes.php');
    return $map[$extension] ?? null;
}

/**
 * @param string $file
 * @return null|string
 */
function getMimeType(string $file)
{
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    return $extension ? getMimeTypeByExtension($extension) : null;
}
