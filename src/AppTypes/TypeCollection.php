<?php

namespace NorthStack\NorthStackClient\AppTypes;

class TypeCollection
{
    protected static $types = [
        'wordpress' => WordPressType::class,
        'jekyll'    => JekyllType::class,
        'gatsby'    => GatsbyType::class,
        'static'    => StaticType::class,
    ];

    public static function getTypes()
    {
        foreach (self::$types as $name => $type)
        {
            yield $type;
        }
    }

    public static function getTypeByName($name)
    {
        $key = strToLower($name);
        return self::$types[$key];
    }
}
