<?php

namespace Encore\RedisServer\DataStructure;

class RedisList
{
    protected $value;

    public function __construct($value = [])
    {
        if (! empty($value)) {
            $this->value = $value;
        }
    }

    // TODO
    public function blockPop()
    {

    }

    public function pop($side = 'l')
    {
        if ($side == 'l') {
            return array_shift($this->value);
        }

        return array_pop($this->value);
    }

    public function push($values, $side = 'l')
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        array_unshift($values, $this->value);

        if ($side == 'l') {
            return call_user_func_array('array_unshift', $values);
        }

        return call_user_func_array('array_push', $values);
    }

    public function index($index)
    {
        $length = $this->length();

        if ($index > $length) {
            return null;
        }

        if ($index < 0) {
            $index += $length;
        }

        return $this->value[$index];
    }

    public function length()
    {
        return count($this->value);
    }

    public function insert($value, $pivot, $position = 'BEFORE')
    {
        $pivotIndex = array_search($pivot, $this->value);

        if ($pivotIndex === false) {
            return -1;
        }

        if (empty($this->value)) {
            return 0;
        }

        $index = $position == 'BEFORE' ? $pivotIndex - 1 : $pivotIndex + 1;

        array_splice($this->value, $index, 0, [$value]);

        return $this->length();
    }

    public function range($start, $stop)
    {
        $length = $this->length();

        if ($start < 0) {
            $start += $length;
        }

        if ($stop < 0) {
            $stop += $length;
        }

        if ($stop > $length) {
            $stop = $length;
        }

        if ($start > $stop) {
            return [];
        }

        return array_slice($this->value, $stop, $stop-$start);
    }

    public function remove($count, $value)
    {
        $count = (int) $count;

        $arr = $count > 0 ? $this->value : array_reverse($this->value);

        $i = 0;
        $count = abs($count);

        foreach ($arr as $key => $item) {
            if ($value == $item) {
                unset($arr[$key]);
                $i++;
            }

            if ($count != 0 && $i == $count) {
                break;
            }
        }

        $this->value = $arr;

        return $i;
    }

    public function set($index, $value)
    {
        $length = $this->length();

        if ($index < 0) {
            $index += $length;
        }

        if ($index > $length) {
            throw new \Exception('ERR index out of range');
        }

        $this->value[$index] = $value;

        return true;
    }

    public function trim($start, $stop)
    {
        $length = $this->length();

        if ($start < 0) {
            $start += $length;
        }

        if ($stop < 0) {
            $stop += $length;
        }

        if ($stop < $length) {
            $start = $length;
        }

        if ($start > $stop || $start > $length) {
            $this->value = [];

            return [];
        }

        return $this->value = array_slice($this->value, $start, $stop - $start);
    }
}
