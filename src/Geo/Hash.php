<?php

namespace Encore\RedisServer\Geo;

class Hash
{
    private static $bits = [16, 8, 4, 2, 1];

    private static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    private static $neighbors = [
        'top'    => ['even' => 'bc01fg45238967deuvhjyznpkmstqrwx'],
        'bottom' => ['even' => '238967debc01fg45kmstqrwxuvhjyznp'],
        'right'  => ['even' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy'],
        'left'   => ['even' => '14365h7k9dcfesgujnmqp0r2twvyx8zb'],
    ];

    private static $borders = [
        'top'    => ['even' => 'bcfguvyz'],
        'bottom' => ['even' => '0145hjnp'],
        'right'  => ['even' => 'prxz'],
        'left'   => ['even' => '028b'],
    ];

    /**
     * Geohash encode
     *
     * @param   float $latitude
     * @param   float $longitude
     * @return  string
     */
    public static function encode($latitude, $longitude)
    {
        /***
        eq('xpssc0', Geohash::encode(43.025, 141.377));
        eq('xn76urx4dzxy', Geohash::encode(35.6813177190391, 139.7668218612671));
         */
        $isEven = true;
        $bit = 0;
        $ch = 0;
        $precision = min((max(strlen(strstr($latitude, '.')), strlen(strstr($longitude, '.'))) - 1) * 2, 12);
        $geohash = '';

        $lat = [-90.0, 90.0];
        $lon = [-180.0, 180.0];

        while(strlen($geohash) < $precision){
            if($isEven){
                $mid = array_sum($lon) / 2;
                if($longitude > $mid){
                    $ch |= self::$bits[$bit];
                    $lon[0] = $mid;
                } else {
                    $lon[1] = $mid;
                }
            } else {
                $mid = array_sum($lat) / 2;
                if($latitude > $mid){
                    $ch |= self::$bits[$bit];
                    $lat[0] = $mid;
                } else {
                    $lat[1] = $mid;
                }
            }
            $isEven = !$isEven;
            if($bit < 4){
                $bit++;
            } else {
                $geohash .= self::$base32{$ch};
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }

    /**
     * Geohash decode
     * @param   string $geohash
     * @return  array
     */
    public static function decode($geohash)
    {
        /***
        list($latitude, $longitude) = Geohash::decode('xpssc0');
        eq([43.0224609375, 43.027954101562, 43.025207519531), $latitude);
        eq([141.3720703125, 141.38305664062, 141.37756347656), $longitude);
         */
        
        $isEven = true;
        $lat = [-90.0, 90.0];
        $lon = [-180.0, 180.0];
        $lat_err = 90.0;
        $lon_err = 180.0;
        for($i = 0; $i < strlen($geohash); $i++){
            $c = $geohash{$i};
            $cd = stripos(self::$base32, $c);
            for($j = 0; $j < 5; $j++){
                $mask = self::$bits[$j];
                if($isEven){
                    $lon_err /= 2;
                    self::refineInterval($lon, $cd, $mask);
                } else {
                    $lat_err /= 2;
                    self::refineInterval($lat, $cd, $mask);
                }
                $isEven = !$isEven;
            }
        }
        $lat[2] = ($lat[0] + $lat[1]) / 2;
        $lon[2] = ($lon[0] + $lon[1]) / 2;

        return [$lat, $lon];
    }

    /**
     * adjacent
     *
     * @param $hash
     * @param $dir
     * @return string
     */
    public static function adjacent($hash, $dir)
    {
        /***
        eq('xne', Geohash::adjacent('xn7', 'top'));
        eq('xnk', Geohash::adjacent('xn7', 'right'));
        eq('xn5', Geohash::adjacent('xn7', 'bottom'));
        eq('xn6', Geohash::adjacent('xn7', 'left'));
         */
        $hash = strtolower($hash);
        $last = substr($hash, -1);
        // $type = (strlen($hash) % 2)? 'odd': 'even';
        $type = 'even'; //FIXME
        $base = substr($hash, 0, strlen($hash) - 1);
        if(strpos(self::$borders[$dir][$type], $last) !== false){
            $base = self::adjacent($base, $dir);
        }
        return $base. self::$base32[strpos(self::$neighbors[$dir][$type], $last)];
    }

    private static function refineInterval(&$interval, $cd, $mask)
    {
        $interval[($cd & $mask)? 0: 1] = ($interval[0] + $interval[1]) / 2;
    }
}
