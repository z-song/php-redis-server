<?php

namespace Encore\RedisServer\DataStructure;

use Exception;

class String
{
    protected $value;

    public function __construct($value = '')
    {
        if (!empty($value)) {
            $this->value = $value;
        }
    }

    public function set($value)
    {
        $this->value = $value;
    }

    public function get()
    {
        return $this->value;
    }

    public function length()
    {
        return strlen($this->value);
    }

    public function append($append = '')
    {
        $this->value .= $append;
    }

    public function setRange($offset, $value)
    {
        if ($offset > $this->length()) {
            $value = str_pad('', $offset - $this->length(), chr(0)) . $value;
        }

        $this->value = substr_replace($this->value, $value, $offset);
    }

    public function getRange($start, $end)
    {
        list($start, $length) = $this->foo($this->length(), $start, $end);

        return substr($this->value, $start, $length);
    }

    protected function foo($length, $start, $end)
    {
        if ($start < 0 && $end < 0 && $start > $end) {;
            return '';
        }
        if ($start < 0) $start = $length+$start;
        if ($end < 0) $end = $length+$end;
        if ($start < 0) $start = 0;
        if ($end < 0) $end = 0;
        if ($end >= $length) $end = $length-1;

        if ($start > $end || $length == 0) {
            return [0, 0];
        }

        return [$start, $end-$start+1];
    }

    public function bitOperate($op)
    {
        if (! in_array($op, ['AND', 'OR', 'XOR', 'NOT'])) {
            throw new Exception('syntax error');
        }

        $result = $this->value;

        if ($op == 'NOT') {
            $result = ~func_get_args()[1];
        } else {
            foreach (array_slice(func_get_args(), 1) as $value) {
                switch ($op) {
                    case 'AND':
                        $result &= $value;
                        break;
                    case 'OR':
                        $result |= $value;
                        break;
                    case 'XOR':
                        $result ^= $value;
                        break;
                }
            }
        }

        return $result;
    }

    public function bitCount($start = null, $end = null)
    {
        list($start, $length) = $this->foo($this->length(), $start, $end);

        $tmp = substr($this->value, $start, $length);

        $bin = $this->str2bin($tmp);

        return substr_count($bin, '1');
    }

    public function setBit($offset, $value)
    {
        if (! in_array($value, [0, 1])) {
            throw new \Exception('ERR bit is not an integer or out of range');
        }

        $bin = $this->str2bin();

        $bin{$offset - 1} = $value;

        $this->value = $this->bin2str($bin);
    }

    public function getBit($offset)
    {
        $bin = $this->str2bin();

        if ($offset > strlen($bin)) {
            return 0;
        }

        return (int) $bin{$offset -1};
    }

    protected function str2bin($str = '')
    {
        list(,$hex) = unpack('H*', $str ?: $this->value);

        var_dump(base_convert($hex, 16, 2));

        return base_convert($hex, 16, 2);
    }

    protected function bin2str($bin)
    {
        $hex = base_convert($bin, 2, 16);

        return pack('H*', $hex);
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
