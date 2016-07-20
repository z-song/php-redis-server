<?php
/**
 * Created by PhpStorm.
 * User: song
 * Email: zousong@yiban.cn
 * Date: 16/7/20
 * Time: 下午4:36
 */

namespace Encore\RedisServer\Helper;

class Geo
{
    const EARTH_RADIUS_IN_METERS = 6372797.560856;

    /**
     * Get distance of two coordinates.
     *
     * @param double $lat1
     * @param double $long1
     * @param double $lat2
     * @param double $long2
     * @return int
     * @see http://stackoverflow.com/questions/10053358/measuring-the-distance-between-two-coordinates-in-php
     */
    public static function distance($lat1, $long1, $lat2, $long2)
    {
        $lat1  = deg2rad($lat1);
        $long1 = deg2rad($long1);
        $lat2  = deg2rad($lat2);
        $long2 = deg2rad($long2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $long2 - $long1;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));

        return $angle * static::EARTH_RADIUS_IN_METERS;
    }

    public static function boundingBox($latitude, $longitude, $distance)
    {
        $lonr = deg2rad($longitude);
        $latr = deg2rad($latitude);

        if ($distance > static::EARTH_RADIUS_IN_METERS) {
            $distance = static::EARTH_RADIUS_IN_METERS;
        }

        $angular = $distance / static::EARTH_RADIUS_IN_METERS;

        $minLat = $latr - $angular;
        $maxLat = $latr + $angular;

        $longDiff = asin(sin($angular) / cos($latr));

        $minLong = $lonr - $longDiff;
        $maxLong = $lonr + $longDiff;

        return [
            rad2deg($minLong),
            rad2deg($minLat),
            rad2deg($maxLong),
            rad2deg($maxLat),
        ];
    }

    private static $bits = [16, 8, 4, 2, 1];

    private static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    /**
     * Geohash encode
     *
     * @param   float $latitude
     * @param   float $longitude
     * @return  string
     */
    public static function encode($latitude, $longitude)
    {
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

    private static function refineInterval(&$interval, $cd, $mask)
    {
        $interval[($cd & $mask)? 0: 1] = ($interval[0] + $interval[1]) / 2;
    }

    public static function hash2bin($hash)
    {
        $base32 = str_split(static::$base32);
        $base32 = array_flip($base32);

        $bits = 0;
        for ($i = 0; $i < strlen($hash); $i++) {
            $index = (int)$base32[$hash{$i}];


            $bits = ($bits == 0) ? $index : ($bits << 5) + $index;
        }
var_dump(decbin($bits));
        return $bits;
    }

    private static $neighbors = [
        'north' => ['even' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy'],
        'south' => ['even' => '14365h7k9dcfesgujnmqp0r2twvyx8zb'],
        'east'  => ['even' => 'bc01fg45238967deuvhjyznpkmstqrwx'],
        'west'  => ['even' => '238967debc01fg45kmstqrwxuvhjyznp'],
    ];

    private static $borders = [
        'north' => ['even' => 'prxz'],
        'south' => ['even' => '028b'],
        'east'  => ['even' => 'bcfguvyz'],
        'west'  => ['even' => '0145hjnp'],
    ];

    public static function neighbors($hash)
    {
        static::$neighbors['south']['odd'] = static::$neighbors['west']['even'];
        static::$neighbors['north']['odd'] = static::$neighbors['east']['even'];
        static::$neighbors['west']['odd']  = static::$neighbors['south']['even'];
        static::$neighbors['east']['odd']  = static::$neighbors['north']['even'];

        static::$borders['south']['odd']   = static::$borders['west']['even'];
        static::$borders['north']['odd']   = static::$borders['east']['even'];
        static::$borders['west']['odd']    = static::$borders['south']['even'];
        static::$borders['east']['odd']    = static::$borders['north']['even'];

        $neighbors['north']      = static::calculateAdjacent($hash, 'north');
        $neighbors['south']      = static::calculateAdjacent($hash, 'south');
        $neighbors['east']       = static::calculateAdjacent($hash, 'east');
        $neighbors['west']       = static::calculateAdjacent($hash, 'west');

        $neighbors['northwest']  = static::calculateAdjacent($neighbors['west'], 'north');
        $neighbors['northeast']  = static::calculateAdjacent($neighbors['east'], 'north');
        $neighbors['southeast']  = static::calculateAdjacent($neighbors['east'], 'south');
        $neighbors['southwest']  = static::calculateAdjacent($neighbors['west'], 'south');

        return $neighbors;
    }

    private static function calculateAdjacent($hash, $dir)
    {
        $hash = strtolower($hash);
        $lastChr = $hash[strlen($hash) - 1];
        $type = (strlen($hash) % 2) ? 'odd' : 'even';
        $base = substr($hash, 0, strlen($hash) - 1);

        if (strpos(static::$borders[$dir][$type], $lastChr) !== false) {
            $base = static::calculateAdjacent($base, $dir);
        }

        return $base . static::$base32[strpos(static::$neighbors[$dir][$type], $lastChr)];
    }
}
