<?php


namespace NorthStack\NorthStackClient\JSON;


use ColinODell\Json5\Json5Decoder;

class Merger
{
    public static function merge(string $base, string ...$other)
    {
        if (empty($base)) {
            throw new \RuntimeException('Empty config file');
        }

        /** @var \stdClass|array $new */
        $new = Json5Decoder::decode($base);

        foreach ($other as $mergeJson) {
            $merge = Json5Decoder::decode($mergeJson);
            if (is_object($merge)) {
                foreach (get_object_vars($merge) as $key => $value) {
                    if (isset($new->{$key}) && is_array($new->{$key}) && !empty($value)) {
                        array_push($new->{$key}, ...$value);
                    } else {
                        $new->{$key} = $value;
                    }
                }
            } elseif (is_array($merge)) {
                array_push($new, ...$merge);
                $new  = array_unique($new);
            }
        }

        return json_encode($new);
    }
}
