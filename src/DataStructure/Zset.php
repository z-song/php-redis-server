<?php

namespace Encore\RedisServer\DataStructure;

use Exception;

class Zset
{
    protected $value;

    /**
     * @param array $value [member => score, ...]
     */
    public function __construct($value = [])
    {
        if (! empty($value)) {
            $this->value = $value;
        }
    }

    public function add($value)
    {
        $add = [];
        if (func_num_args() == 2) {
            $add = [func_get_arg(0), func_get_arg(1)];
        } elseif (is_array($value) && ! empty($value)) {
            if (! is_array($value[0])) {
                $add[] = $value;
            } else {
                $add = $value;
            }
        }

        $count = 0;
        foreach ($add as $item) {
            list($score, $member) = $item;

            if (! isset($this->value[$member])) {
                $count++;
            }

            $this->value[$member] = $score;
        }

        return $count;
    }

    public function count($min = -INF, $max = INF)
    {
        $minOp = '>=';
        $maxOp = '<=';
        $count = 0;
        $maxOpRes = $minOpRes = false;

        if (strpos($min, '(') === 0) {
            $minOp = '>';
            $min = ltrim($min, '(');
        }

        if (strpos($min, '(') === 0) {
            $maxOp = '<';
            $min = ltrim($max, ')');
        }

        foreach ($this->value as $member => $score) {
            switch ($minOp) {
                case '>=':
                    $minOpRes = $score >= $min;
                    break;
                case '>':
                    $minOpRes = $score > $min;
                    break;
            }

            switch ($maxOp) {
                case '<=':
                    $maxOpRes = $score >= $max;
                    break;
                case '<':
                    $maxOpRes = $score > $max;
                    break;
            }

            if ($maxOpRes && $minOpRes) {
                $count++;
            }
        }

        return $count;
    }

    public function lexCount($min, $max)
    {
        $members = $this->parseLexMinAndMax($min, $max);

        return count($members);
    }

    public function rangeByLex($min, $max, $options)
    {
        $members = $this->parseLexMinAndMax($min, $max);

        sort($members);

        if (empty($options)) {
            return $members;
        }

        return array_slice($members, $options['offset'], $options['count']);
    }

    public function removeRangeByLex($min, $max)
    {
        $members = $this->parseLexMinAndMax($min, $max);

        return $this->remove($members);
    }

    protected function parseLexMinAndMax($min, $max)
    {
        $minOp = $maxOp = '';

        if ($min == '-') {
            $min = -INF;
            $minOp = '>';
        }

        if ($max == '+') {
            $max = INF;
            $maxOp = '<';
        }

        if ($min{0} == '[') {
            $min = substr($min, 1);
            $minOp = '>=';
        }

        if ($min{0} == '(') {
            $min = substr($min, 1);
            $minOp = '>';
        }

        if ($max{0} == '[') {
            $max = substr($max, 1);
            $maxOp = '<=';
        }

        if ($max{0} == '(') {
            $max = substr($max, 1);
            $maxOp = '<';
        }

        $members = 0;
        $maxOpRes = $minOpRes = false;

        foreach (array_keys($this->value) as $member) {
            switch ($minOp) {
                case '>=':
                    $minOpRes = $member >= $min;
                    break;
                case '>':
                    $minOpRes = $member > $min;
                    break;
            }

            switch ($maxOp) {
                case '<=':
                    $maxOpRes = $member <= $max;
                    break;
                case '<':
                    $maxOpRes = $member < $max;
                    break;
            }

            if ($minOpRes && $maxOpRes) {
                $members[] = $member;
            }
        }

        return $members;
    }

    public function incrementBy($increment, $member)
    {
        if (! isset($this->value[$member])) {
            $this->add($member, $increment);

            return $increment;
        }

        return $this->value[$member] += $increment;
    }

    public function range($start, $stop, $order = 'ASC', $withScore = false)
    {
        $length = count($this->value);

        if ($start < 0) {
            $start += $length;
        }

        if ($stop < 0) {
            $stop += $length;
        }

        if ($start > $length || $start > $stop) {
            return [];
        }

        $slice = array_slice($this->value, $start, $stop-$start);

        $order == 'ASC' ? sort($slice) : rsort($slice);

        if (! $withScore) {
            return array_keys($slice);
        }

        return $slice;
    }

    public function rangeByScore($min = -INF, $max = INF, $order = 'ASC', $withScore = false, $options = [])
    {
        $minOp = '>=';
        $maxOp = '<=';
        $maxOpRes = $minOpRes = false;
        $arr = [];

        if (strpos($min, '(') === 0) {
            $minOp = '>';
            $min = ltrim($min, '(');
        }

        if (strpos($min, '(') === 0) {
            $maxOp = '<';
            $min = ltrim($max, ')');
        }

        foreach ($this->value as $member => $score) {
            switch ($minOp) {
                case '>=':
                    $minOpRes = $score >= $min;
                    break;
                case '>':
                    $minOpRes = $score > $min;
                    break;
            }

            switch ($maxOp) {
                case '<=':
                    $maxOpRes = $score >= $max;
                    break;
                case '<':
                    $maxOpRes = $score > $max;
                    break;
            }

            if ($maxOpRes && $minOpRes) {
                $arr[$member] = $score;
            }
        }

        $order == 'ASC' ? sort($arr) : rsort($arr);

        $arr = $withScore ? $arr : array_keys($arr);

        if (empty($options)) {
            return $arr;
        }

        return array_slice($arr, $options['offset'], $options['count']);
    }

    public function rank($member, $order = 'ASC')
    {
        $value = $this->value;

        $order == 'ASC' ? sort($value) : rsort($value);

        return array_search($member, array_keys($value));
    }

    public function remove($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $count = 0;
        foreach ($members as $member) {
            if (isset($this->value[$member])) {
                unset($this->value[$member]);
                $count++;
            }
        }

        return $count;
    }

    public function score($member)
    {
        if (! isset($this->value[$member])) {
            return null;
        }

        return $this->value[$member];
    }

    // TODO
    public static function union($keys = [], $weights = [], $aggregate = 'SUM')
    {
        if (count($keys) != count($weights)) {
            throw new Exception('');
        }


    }

    // TODO
    public static function intersect($keys = [], $weights = [], $aggregate = 'SUM')
    {
        if (count($keys) != count($weights)) {
            throw new Exception('');
        }
    }

    // TODO
    public function scan()
    {

    }
}
