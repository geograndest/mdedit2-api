<?php
namespace MdEditApi;

class Json
{
    public static function get($file = false) {
        if (\file_exists($file)) {
            $filecontent = \file_get_contents($file);
            return \json_decode($filecontent, true);
        }
        return [];
    }

}