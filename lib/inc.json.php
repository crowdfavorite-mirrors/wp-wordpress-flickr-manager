<?php
if (!function_exists('json_decode')) {
    function json_decode($content, $assoc=false) {
        require_once(dirname(__FILE__) . '/lib.json.php');
        if ($assoc) {
            $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }
        else {
            $json = new Services_JSON;
        }
        return $json->decode($content);
    }
}

if (!function_exists('json_encode')) {
    function json_encode($content) {
        require_once(dirname(__FILE__) . '/lib.json.php');
        $json = new Services_JSON;
        return $json->encode($content);
    }
}
?>