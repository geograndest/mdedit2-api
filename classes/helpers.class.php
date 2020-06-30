<?php

namespace MdEditApi;

class Helpers
{
    public static function get_file_content($file = false)
    {
        if (\file_exists($file)) {
            $filecontent = \file_get_contents($file);
            return \json_decode($filecontent, true);
        }
        return [];
    }

    public static function get($param, $default = false)
    {
        if (isset($_GET[$param])) {
            return $_GET[$param];
        }
        return $default;
    }

    public static function post($param, $default = false)
    {
        if (isset($_POST[$param])) {
            return $_POST[$param];
        }
        return $default;
    }

    public static function postdata($default = false)
    {
        $postdata = file_get_contents("php://input");
        $result = json_decode($postdata);
        if ($result) {
            return $result;
        }
        return $default;
    }

    public static function getHeader($param, $default = false)
    {
        if (isset($_SERVER[$param])) {
            return $_SERVER[$param];
        }
        return $default;
    }

    public static function startWith($string, $word)
    {
        return (substr_compare($string, $word, 0, strlen($word)) === 0);
    }

    public static function endWith($string, $word)
    {
        return (substr_compare($string, $word, 0, -strlen($word)) === 0);
    }

    public static function getXmlFileInfo($filename = false, $info = false, $root_path = false)
    {
        $xpaths = [
            'fileIdentifier' => 'gmd:fileIdentifier/gco:CharacterString/text()',
            'dataTitle' => 'gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString/text()'
        ];
        $response = [
            'filename' => $filename,
            $info => false,
            'success' => false,
            'message' => 'Erreur lors de la lecture du fichier XML.'
        ];
        if (is_file($root_path . $filename) and array_key_exists($info, $xpaths)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($root_path . $filename);
            if ($xml !== false) {
                $namespaces = $xml->getDocNamespaces();
                foreach ($namespaces as $key => $value) {
                    $xml->registerXPathNamespace($key, $value);
                }
                if (is_array($xml->xpath($xpaths[$info]))) {
                    $response[$info] = implode('', $xml->xpath($xpaths[$info]));
                } else {
                    $response[$info] = $xml->xpath($xpaths[$info]);
                }
                $response['success'] = true;
                $response['message'] = 'Lecture du fichier XML et récupétation du titre';
            }
        }
        return $response;
    }

    // Fonction permettant de décoder les données reçues en POST et encodées en Base 64
    public static function decodeDataBase64($data)
    {
        $data = explode(';base64,', $data);
        if (!is_array($data) || !isset($data[1])) {
            return false;
        }
        $data = base64_decode($data[1]);
        if (!$data) {
            return false;
        }
        return $data;
    }
}
