<?php

namespace Encore\RedisServer\Helper;

class BitOp
{
    public static function str2bin($str)
    {
        list(,$hex) = unpack('H*', $str);

        $hexArr = str_split($hex, 4);

        $bin = '';
        foreach ($hexArr as $hex) {
            $bin .= base_convert($hex, 16, 2);
        }

        return $bin;
    }

    public static function bin2str($bin)
    {
        $bins = str_split($bin, 8);

        $hex = '';
        foreach ($bins as $bin) {
            $hex .= base_convert($bin, 2, 16);
        }

        return pack('H*', $hex);
    }
}
