<?php

namespace Encore\RedisServer\Helper;

class Geo
{
    const EARTH_RADIUS_IN_METERS = 6372797.560856;

    private static $bits = [16, 8, 4, 2, 1];

    private static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    private static $neighbors = [
        'north' => [
            'even' => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
            'odd'  => 'bc01fg45238967deuvhjyznpkmstqrwx'
        ],
        'south' => [
            'even' => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
            'odd'  => '238967debc01fg45kmstqrwxuvhjyznp'
        ],
        'east'  => [
            'even' => 'bc01fg45238967deuvhjyznpkmstqrwx',
            'odd'  => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy'
        ],
        'west'  => [
            'even' => '238967debc01fg45kmstqrwxuvhjyznp',
            'odd'  => '14365h7k9dcfesgujnmqp0r2twvyx8zb'
        ],
    ];

    private static $borders = [
        'north' => [
            'even' => 'prxz',
            'odd'  => 'bcfguvyz'
        ],
        'south' => [
            'even' => '028b',
            'odd'  => '0145hjnp'
        ],
        'east'  => [
            'even' => 'bcfguvyz',
            'odd'  => 'prxz'
        ],
        'west'  => [
            'even' => '0145hjnp',
            'odd'  => '028b'
        ],
    ];

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

    /**
     * Geohash encode
     *
     * @param   float $latitude
     * @param   float $longitude
     * @return  string
     */
    public static function encode($latitude, $longitude, $getHash = true)
    {
        $even = true;
        $bit = $ch = 0;
        $precision = min((max(strlen(strstr($latitude, '.')), strlen(strstr($longitude, '.'))) - 1) * 2, 12);
        $geoHash = '';
        $bits = 0;

        $latRange = [-90.0, 90.0];
        $lonRange = [-180.0, 180.0];

        while(strlen($geoHash) < $precision){
            if($even){
                $mid = array_sum($lonRange) / 2;
                if($longitude > $mid){
                    $ch |= self::$bits[$bit];
                    $lonRange[0] = $mid;
                } else {
                    $lonRange[1] = $mid;
                }
            } else {
                $mid = array_sum($latRange) / 2;
                if($latitude > $mid){
                    $ch |= self::$bits[$bit];
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }
            $even = !$even;
            if($bit < 4){
                $bit++;
            } else {

                $geoHash .= self::$base32{$ch};

                $bits = ($bits == 0) ? $ch : ($bits << 5) + $ch;

                $bit = $ch = 0;
            }
        }

        return $getHash ? $geoHash : $bits;
    }

    /**
     * Geohash decode
     * @param   string $geoHash
     * @return  array
     */
    public static function decode($geoHash)
    {
        $even = true;
        $lat = [-90.0, 90.0];
        $lon = [-180.0, 180.0];
        $lat_err = 90.0;
        $lon_err = 180.0;
        for($i = 0; $i < strlen($geoHash); $i++){
            $c = $geoHash{$i};
            $cd = stripos(self::$base32, $c);
            for($j = 0; $j < 5; $j++){
                $mask = self::$bits[$j];
                if($even){
                    $lon_err /= 2;
                    self::refineInterval($lon, $cd, $mask);
                } else {
                    $lat_err /= 2;
                    self::refineInterval($lat, $cd, $mask);
                }
                $even = !$even;
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

    public static function hash2dec($hash)
    {
        $base32 = str_split(static::$base32);
        $base32 = array_flip($base32);

        $dec = 0;
        for ($i = 0; $i < strlen($hash); $i++) {
            $index = (int)$base32[$hash{$i}];
            $dec = ($dec == 0) ? $index : ($dec << 5) + $index;
        }

        return $dec;
    }

    public static function dec2hash($dec)
    {
        $buf = '';
        for ($i = 0; $i < 12; $i++) {
            $idx = ($dec >> (60-(($i+1)*5))) & 0x1f;
            $buf .= static::$base32[$idx];
        }

        return $buf;
    }

    public static function fixBits($dec, $count = 60)
    {
        for ($i = 0; $i < $count; $i++) {
            if ($dec >> ($count-($i+1)) & 1) {
                break;
            }
        }

        return $dec << $i;
    }

    public static function neighbors($hash)
    {
        $neighbors['north']      = static::adjacent($hash, 'north');
        $neighbors['south']      = static::adjacent($hash, 'south');
        $neighbors['east']       = static::adjacent($hash, 'east');
        $neighbors['west']       = static::adjacent($hash, 'west');
        $neighbors['northwest']  = static::adjacent($neighbors['west'], 'north');
        $neighbors['northeast']  = static::adjacent($neighbors['east'], 'north');
        $neighbors['southeast']  = static::adjacent($neighbors['east'], 'south');
        $neighbors['southwest']  = static::adjacent($neighbors['west'], 'south');

        return $neighbors;
    }

    private static function adjacent($hash, $dir)
    {
        $hash    = strtolower($hash);
        $lastChr = $hash[strlen($hash) - 1];
        $type    = (strlen($hash) % 2) ? 'odd' : 'even';
        $base    = substr($hash, 0, strlen($hash) - 1);

        if (strpos(static::$borders[$dir][$type], $lastChr) !== false) {
            $base = static::adjacent($base, $dir);
        }

        return $base . static::$base32[strpos(static::$neighbors[$dir][$type], $lastChr)];
    }
}
