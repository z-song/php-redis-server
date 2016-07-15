<?php

namespace Encore\RedisServer\DataStructure;

class String
{
    protected $value;

    public function __construct($value = '')
    {
        if (!empty($value)) {
            $this->value = $value;
        }
    }

    public function length()
    {
        return strlen($this->value);
    }

    public function append($append = '')
    {
        $this->value .= $append;
    }

    public function set($value)
    {
        $this->value = $value;
    }

    public function setRange($offset, $value)
    {
        
    }

    public function bitCount($start = null, $end = null)
    {
        if ($start < 0 && $end < 0 && $start > $end) {
            return 0;
        }

        if ($start < 0) $start = $this->length()+$start;
        if ($end < 0) $end = $this->length()+$end;


        if ($start < 0) $start = 0;
        if ($end < 0) $end = 0;
        if ($end >= $this->length()) $end = $this->length()-1;


        $value = unpack('H*', $this->value);

        $bin = base_convert($value[1], 16, 10);

        $count = 0;
        while($bin) {
            $count += ($bin & 1);
            $bin = $bin >> 1;
        }

        return $count;
    }

    public function setBit($offset, $value)
    {

    }

    public function getBit()
    {

    }

    public function incrementBy($increment = 1)
    {
        if (is_int($this->value)) {
            $this->value += $increment;
        }

        throw new Exception('ERR value is not an integer or out of range');
    }

    public function decrementBy($decrement = 1)
    {
        if (! is_int($this->value)) {
            throw new Exception('ERR value is not an integer or out of range');
        }

        $this->value -= $decrement;
    }
}
