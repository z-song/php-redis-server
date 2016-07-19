<?php
/**
 * Created by PhpStorm.
 * User: song
 * Email: zousong@yiban.cn
 * Date: 16/7/19
 * Time: 下午4:55
 */

namespace Encore\RedisServer\Geo;

class Helper
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
}
