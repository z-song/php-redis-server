<?php
/**
 * Created by PhpStorm.
 * User: song
 * Email: zousong@yiban.cn
 * Date: 16/7/19
 * Time: 下午5:06
 */

namespace Encore\RedisServer\DataStructure;

use Encore\RedisServer\Geo\Hash;
use Encore\RedisServer\Geo\Helper;

class Geo extends Zset
{
    public function add(array $members)
    {
        if (! is_array($members[0])) {
            $members = [$members];
        }

        $count = 0;
        foreach ($members as $member) {
            list($long, $lat, $member) = $member;

            if (! isset($this->value[$member])) {
                $count++;
            }

            parent::add(Hash::encode($lat, $long),$member);
        }

        return $count;
    }

    public function position($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $result = [];
        foreach ($members as $member) {
            if (isset($this->value[$member])) {
                $result[] = Hash::decode($this->value[$member]);
            } else {
                $result[] = null;
            }
        }

        return $result;
    }

    public function distance($member1, $member2, $unit = 'm')
    {
        if (! isset($this->value[$member1]) || ! isset($this->value[$member2])) {
            return null;
        }

        list($lat1, $long1) = Hash::decode($this->value[$member1]);
        list($lat2, $long2) = Hash::decode($this->value[$member2]);

        $distance = Helper::distance($lat1, $long1, $lat2, $long2);

        return $distance / $this->extractUnit($unit);
    }

    protected function extractUnit($unit = 'm')
    {
        $unitRate = [
            'm'  => 1,
            'km' => 1000,
            'mi' => 1609.34,
            'ft' => 0.3048
        ];

        if (isset($unitRate[$unit])) {
            return $unitRate[$unit];
        }

        throw new \Exception("unsupported unit provided. please use m, km, ft, mi");
    }

    public function hash($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $result = [];
        foreach ($members as $member) {
            if (isset($this->value[$member])) {
                $result[] = $this->value[$member];
            } else {
                $result[] = null;
            }
        }

        return $result;
    }

    /**
     * @param $long
     * @param $lat
     * @param $radius
     * @param string $unit
     * @param array $options [
     *  'withCoordinate' => false,
     *  'withDistance'   => false,
     *  'withHash'       => false,
     *  'order'          => 'ASC',
     *  'count'          => null
     * ]
     */
    public function radius($long, $lat, $radius, $unit = 'm', $options = [])
    {
        
    }

    public function membersInDistance()
    {
        
    }
}
