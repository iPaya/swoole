<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Helpers;


class OptionResolver
{
    /**
     * @param array $options
     * @param array $valueSet
     * @param array $map $options 与 $valueSet 对应关系
     */
    public static function resolve(array &$options, array $valueSet, array $map)
    {
        foreach ($map as $name => $value) {
            if (isset($valueSet[$value])) {
                $options[$name] = $valueSet[$value];
            }
        }
    }
}
