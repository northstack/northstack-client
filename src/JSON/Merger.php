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

        /** @var \stdClass $new */
        $new = Json5Decoder::decode($base, true);

        foreach ($other as $mergeJson) {
            $merge = Json5Decoder::decode($mergeJson);
            foreach (get_object_vars($merge) as $key => $value) {
                if (isset($new->{$key}) && is_array($new->{$key})) {
                    array_push($new->{$key}, ...$value);
                } else {
                    $new->{$key} = $value;
                }
            }
        }

        return json_encode($new);
    }
}
