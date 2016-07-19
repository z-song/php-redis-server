<?php

namespace Encore\RedisServer\DataStructure;

class Set
{
    protected $value = [];

    public function __construct($value = [])
    {
        if (!empty($value)) {
            $keys = array_unique($value);

            $this->value = array_combine($keys, array_pad([], count($keys), 0));
        }
    }

    public function add($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $count = 0;
        foreach (array_unique($members) as $member) {
            if (! $this->isMember($member)) {
                $this->value[$member] = 0;
                $count++;
            }
        }

        return $count;
    }

    public function card()
    {
        return count($this->value);
    }

    public function difference(array $arr)
    {
        if (empty($arr)) {
            return [];
        }

        if (! is_array($arr[0])) {
            $arr = [$arr];
        }

        array_unshift($arr, $this->members());

        return call_user_func_array('array_diff', $arr);
    }

    public function intersect(array $arr)
    {
        if (empty($arr)) {
            return [];
        }

        if (! is_array($arr[0])) {
            $arr = [$arr];
        }

        array_unshift($arr, $this->members());

        return call_user_func_array('array_intersect', $arr);
    }

    public function isMember($member)
    {
        return isset($this->value[$member]);
    }

    public function members()
    {
        return array_keys($this->value);
    }

    public function pop()
    {
        $pop = array_rand($this->members());

        $this->remove($pop);

        return $pop;
    }

    public function remove($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $count = 0;
        foreach ($members as $member) {
            if ($this->isMember($member)) {
                unset($this->value[$member]);
                $count++;
            }
        }

        return $count;
    }

    public function randomMember($count = 1)
    {
        if ($count == 0) {
            $count = 1;
        }

        if ($count > 0) {
            return array_rand($this->members(), $count);
        }

        $arr = [];

        for ($i = 0; $i < abs($count); $i++) {
            $arr[] =  array_rand($this->members());
        }

        return $arr;
    }

    public function union(array $arr)
    {
        if (empty($arr)) {
            return [];
        }

        if (! is_array($arr[0])) {
            $arr = [$arr];
        }

        array_unshift($arr, $this->members());

        return call_user_func_array('array_merge', $arr);
    }

    // TODO
    public function scan()
    {
        
    }
}
