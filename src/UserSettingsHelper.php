<?php


namespace NorthStack\NorthStackClient;


class UserSettingsHelper
{
    static $settings = null;
    static $settingsFilepath = null;
    const KEY_LOCAL_APPS_DIR = 'local_apps_dir';
    const KEY_LOCAL_APPS_MAP = 'local_apps_map';

    static function getSettingsFilepath()
    {
        if (!static::$settingsFilepath) {
            static::$settingsFilepath = "{$_SERVER['HOME']}/.northstack-settings.json";
        }
        return static::$settingsFilepath;
    }

    static function get($key) {
        $settings = self::getSettings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }

    static function getSettings()
    {
        if (null !== static::$settings) {
            return static::$settings;
        }

        $filePath = self::getSettingsFilepath();
        $data = file_exists($filePath) ? file_get_contents($filePath) : false;

        // file_get_contents should return the boolean if there's a failure
        if (false === $data) {
            try {
                file_put_contents($filePath, '');
            } catch (\Throwable $e) {
                throw $e;
            }
        } elseif (!$data) {
            return [];
        }

        return json_decode($data, true);
    }

    static function updateSetting($key, $value)
    {
        $settings = self::getSettings();
        $settings[$key] = $value;

        // save to the settings file
        file_put_contents(self::getSettingsFilepath(), json_encode($settings));

        return $settings;
    }
}
