<?php

namespace Jhiino\ESNLeJeu\Helper;

class Filter
{
    /**
     * @param $string
     *
     * @return int
     */
    public static function getInt($string)
    {
        return intval(preg_replace('/\D/', '', $string));
    }

    /**
     * @param     $string
     * @param int $expected
     *
     * @return float|array
     */
    public static function getPercentage($string, $expected = 1)
    {
        preg_match_all('/\d+(,|.)?\d*%/', $string, $matches);

        $results = $matches[0];

        foreach ($results as $key => $result) {
            $result        = preg_replace(['/,/', '/%/'], ['.', ''], $result);
            $results[$key] = floatval($result)/100;
        }

        if (1 == $expected) {
            return $results[0];
        } else {
            return array_slice($results, 0, $expected);
        }
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function getString($string)
    {
        return filter_var(trim($string), FILTER_SANITIZE_STRING);
    }
}