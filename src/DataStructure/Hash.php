<?php

namespace Encore\RedisServer\DataStructure;

class Hash
{
    protected $value = [];

    public function __construct(array $value = [])
    {
        if (!empty($value)) {
            $this->value = $value;
        }
    }

    public function values()
    {
        return array_values($this->value);
    }

    public function keys()
    {
        return array_keys($this->value);
    }

    public function length()
    {
        return count($this->value);
    }

    public function setField($field, $value)
    {
        $this->value[$field] = $value;
    }

    public function getField($field)
    {
        return $this->fieldExists($field) ? $this->value[$field] : null;
    }

    public function getMultipleField($fields)
    {
        return array_map([$this, 'getField'], $fields);
    }

    public function setMultipleField($pairs)
    {
        foreach ($pairs as $field => $value) {
            $this->setField($field, $value);
        }
    }

    public function setFieldIfNotExists($field, $value)
    {
        if ($this->fieldExists($field)) {
            return 0;
        }

        $this->setField($field, $value);

        return 1;
    }

    public function removeField($fields)
    {
        if (! is_array($fields)) {
            $fields = [$fields];
        }

        $count = 0;
        foreach ($fields as $field) {
            if ($this->fieldExists($field)) {
                unset($this->value[$field]);
                $count++;
            }
        }

        return $count;
    }

    public function fieldExists($field)
    {
        return array_key_exists($field, $this->value);
    }

    public function getAll()
    {
        return $this->value;
    }

    public function incrementBy($field, $increment = 1)
    {
        if (! $this->fieldExists($field)) {
            $this->setField($field, 0);
        }

        if (! is_int($val = $this->getField($field))) {
            throw new Exception('ERR value is not an integer or out of range');
        }

        $val += $increment;

        $this->setField($field, $val);

        return $val;
    }

    public function incrementByFloat($field, $increment = 1)
    {
        if (! $this->fieldExists($field)) {
            $this->setField($field, 0);
        }

        if (! is_int($val = $this->getField($field))) {
            throw new Exception('ERR value is not an integer or out of range');
        }

        $val -= $increment;

        $this->setField($field, $val);

        return $val;
    }

    //TODO
    public function scan()
    {

    }
}
