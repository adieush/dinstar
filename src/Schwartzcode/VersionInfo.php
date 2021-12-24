<?php


namespace Schwartzcode;


class VersionInfo {
    const MAJOR = 0;
    const MINOR = 0;
    const PATCH = 1;

    public static function string() {
        return implode('.', array(self::MAJOR, self::MINOR, self::PATCH));
    }
}
