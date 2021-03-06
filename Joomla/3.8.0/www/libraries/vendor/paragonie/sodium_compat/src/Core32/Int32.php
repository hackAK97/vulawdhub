<?php

/**
 * Class ParagonIE_Sodium_Core32_Int32
 *
 * Encapsulates a 32-bit integer.
 *
 * These are immutable. It always returns a new instance.
 */
class ParagonIE_Sodium_Core32_Int32
{
    /**
     * @var array<int, int> - two 16-bit integers
     *
     * 0 is the higher 16 bits
     * 1 is the lower 16 bits
     */
    public $limbs;

    /**
     * @var int
     */
    public $overflow = 0;

    public function __construct($array = array(0, 0))
    {
        $this->limbs = $array;
        $this->overflow = 0;
    }

    /**
     * Adds two int32 objects
     *
     * @param ParagonIE_Sodium_Core32_Int32 $addend
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function addInt32(ParagonIE_Sodium_Core32_Int32 $addend)
    {
        $return = new ParagonIE_Sodium_Core32_Int32();

        $tmp = $this->limbs[1] + $addend->limbs[1];
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xffff);

        $tmp = $this->limbs[0] + $addend->limbs[0] + $carry;
        $return->limbs[0] = (int) ($tmp & 0xffff);
        $return->overflow = $this->overflow + $addend->overflow + ($tmp >> 16);

        return $return;
    }

    /**
     * Adds a normal integer to an int32 object
     *
     * @param int $int
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function addInt($int)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);

        $return = new ParagonIE_Sodium_Core32_Int32();

        $tmp = $this->limbs[1] + ($int & 0xffff);
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xffff);

        $tmp = $this->limbs[0] + (($int >> 16) & 0xffff) + $carry;
        $return->limbs[0] = (int) ($tmp & 0xffff);
        $return->overflow = $this->overflow + $tmp >> 16;
        return $return;
    }

    /**
     * @param int $b
     * @return int
     */
    public function compareInt($b = 0)
    {
        $gt = 0;
        $eq = 1;

        $i = 2;
        $j = 0;
        while ($i > 0) {
            --$i;
            $x1 = $this->limbs[$i];
            $x2 = ($b >> ($j << 4)) & 0xffff;
            $gt |= (($x2 - $x1) >> 8) & $eq;
            $eq &= (($x2 ^ $x1) - 1) >> 8;
        }
        return ($gt + $gt - $eq) + 1;
    }

    /**
     * @param int $m
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mask($m = 0)
    {
        $hi = ($m >> 16) & 0xffff;
        $lo = ($m & 0xffff);
        return new ParagonIE_Sodium_Core32_Int32(
            array(
                $this->limbs[0] & $hi,
                $this->limbs[1] & $lo
            )
        );
    }

    /**
     * @param int $int
     * @param int $size
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mulInt($int = 0, $size = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
        ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);
        if (!$size) {
            $size = 31;
        }

        $a = clone $this;
        $return = new ParagonIE_Sodium_Core32_Int32();

        for ($i = $size; $i >= 0; --$i) {
            $m = (int) (-($int & 1));
            $return = $return->addInt32($a->mask($m));
            $a = $a->shiftLeft(1);
            $int >>= 1;
        }
        return $return;
    }

    /**
     * @param ParagonIE_Sodium_Core32_Int32 $int
     * @param int $size
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mulInt32(ParagonIE_Sodium_Core32_Int32 $int, $size = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);
        if (!$size) {
            $size = 31;
        }

        $a = clone $this;
        $b = clone $int;
        $return = new ParagonIE_Sodium_Core32_Int32();

        for ($i = $size; $i >= 0; --$i) {
            $m = (int) (-($b->limbs[1] & 1));
            $return = $return->addInt32($a->mask($m));
            $a = $a->shiftLeft(1);
            $b = $b->shiftRight(1);
        }
        return $return;
    }

    /**
     * OR this 32-bit integer with another.
     *
     * @param ParagonIE_Sodium_Core32_Int32 $b
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function orInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32();
        $return->limbs = array(
            (int) ($this->limbs[0] | $b->limbs[0]),
            (int) ($this->limbs[1] | $b->limbs[1])
        );
        $return->overflow = $this->overflow | $b->overflow;
        return $return;
    }

    /**
     * @param int $b
     * @return bool
     */
    public function isGreaterThan($b = 0)
    {
        return $this->compareInt($b) > 0;
    }

    /**
     * @param int $b
     * @return bool
     */
    public function isLessThanInt($b = 0)
    {
        return $this->compareInt($b) < 0;
    }

    /**
     * @param int $c
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function rotateLeft($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);

        $return = new ParagonIE_Sodium_Core32_Int32();
        $c &= 31;
        if ($c === 0) {
            // NOP, but we want a copy.
            $return->limbs = $this->limbs;
        } else {
            $idx_shift = ($c >> 4) & 1;
            $sub_shift = $c & 15;

            for ($i = 1; $i >= 0; --$i) {
                $j = ($i + $idx_shift) & 1;
                $k = ($i + $idx_shift + 1) & 1;
                $return->limbs[$i] = (int) (
                    (
                        ($this->limbs[$j] << $sub_shift)
                            |
                        ($this->limbs[$k] >> (16 - $sub_shift))
                    ) & 0xffff
                );
            }
        }
        return $return;
    }

    /**
     * Rotate to the right
     *
     * @param int $c
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function rotateRight($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);

        $return = new ParagonIE_Sodium_Core32_Int32();
        $c &= 31;
        if ($c === 0) {
            // NOP, but we want a copy.
            $return->limbs = $this->limbs;
        } else {
            $idx_shift = ($c >> 4) & 1;
            $sub_shift = $c & 15;

            for ($i = 1; $i >= 0; --$i) {
                $j = ($i - $idx_shift) & 1;
                $k = ($i - $idx_shift - 1) & 1;
                $return->limbs[$i] = (int) (
                    (
                        ($this->limbs[$j] >> ($sub_shift))
                            |
                        ($this->limbs[$k] << (16 - $sub_shift))
                    ) & 0xffff
                );
            }
        }
        return $return;
    }

    /**
     * @param int $c
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function shiftLeft($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        $return = new ParagonIE_Sodium_Core32_Int32();
        $c &= 63;
        if ($c === 0) {
            $return->limbs = $this->limbs;
        } elseif ($c < 0) {
            return $this->shiftRight(-$c);
        } else {
            $tmp = $this->limbs[1] << $c;
            $return->limbs[1] = (int)($tmp & 0xffff);
            $carry = $tmp >> 16;

            $tmp = ($this->limbs[0] << $c) | ($carry & 0xffff);
            $return->limbs[0] = (int) ($tmp & 0xffff);
        }
        return $return;
    }

    /**
     * @param int $c
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function shiftRight($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        $return = new ParagonIE_Sodium_Core32_Int32();
        $c &= 63;
        if ($c >= 16) {
            $return->limbs = array(
                0,
                $this->limbs[0]
            );
            return $return->shiftRight($c & 15);
        }
        if ($c === 0) {
            $return->limbs = $this->limbs;
        } elseif ($c < 0) {
            return $this->shiftLeft(-$c);
        } else {
            $carryRight = (int) ($this->limbs[0] & ((1 << ($c + 1)) - 1));
            $return->limbs[0] = (int) (($this->limbs[0] >> $c) & 0xffff);
            $return->limbs[1] = (int) ((($this->limbs[1] >> $c) | ($carryRight << (16 - $c))) & 0xffff);
            $return->overflow >>= $c;
        }
        return $return;
    }

    /**
     * Subtract a normal integer from an int32 object.
     *
     * @param int $int
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function subInt($int)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);

        $return = new ParagonIE_Sodium_Core32_Int32();

        $tmp = $this->limbs[1] - ($int & 0xffff);
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xffff);

        $tmp = $this->limbs[0] - (($int >> 16) & 0xffff) + $carry;
        $return->limbs[0] = (int) ($tmp & 0xffff);
        return $return;
    }

    /**
     * Subtract two int32 objects from each other
     *
     * @param ParagonIE_Sodium_Core32_Int32 $b
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function subInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32();

        $tmp = $this->limbs[1] - ($b->limbs[1] & 0xffff);
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xffff);

        $tmp = $this->limbs[0] - ($b->limbs[0] & 0xffff) + $carry;
        $return->limbs[0] = (int) ($tmp & 0xffff);
        return $return;
    }

    /**
     * XOR this 32-bit integer with another.
     *
     * @param ParagonIE_Sodium_Core32_Int32 $b
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function xorInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32();
        $return->limbs = array(
            (int) ($this->limbs[0] ^ $b->limbs[0]),
            (int) ($this->limbs[1] ^ $b->limbs[1])
        );
        return $return;
    }

    /**
     * @param int $signed
     * @return self
     */
    public static function fromInt($signed)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($signed, 'int', 1);;

        return new ParagonIE_Sodium_Core32_Int32(
            array(
                (int) (($signed >> 16) & 0xffff),
                (int) ($signed & 0xffff)
            )
        );
    }

    /**
     * @param string $string
     * @return self
     */
    public static function fromString($string)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
        $string = (string) $string;
        if (ParagonIE_Sodium_Core32_Util::strlen($string) !== 4) {
            throw new RangeException(
                'String must be 4 bytes; ' . ParagonIE_Sodium_Core32_Util::strlen($string) . ' given.'
            );
        }
        $return = new ParagonIE_Sodium_Core32_Int32();

        $return->limbs[0]  = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xff) << 8);
        $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xff);
        $return->limbs[1]  = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xff) << 8);
        $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xff);
        return $return;
    }

    /**
     * @param string $string
     * @return self
     */
    public static function fromReverseString($string)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
        $string = (string) $string;
        if (ParagonIE_Sodium_Core32_Util::strlen($string) !== 4) {
            throw new RangeException(
                'String must be 4 bytes; ' . ParagonIE_Sodium_Core32_Util::strlen($string) . ' given.'
            );
        }
        $return = new ParagonIE_Sodium_Core32_Int32();

        $return->limbs[0]  = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xff) << 8);
        $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xff);
        $return->limbs[1]  = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xff) << 8);
        $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xff);
        return $return;
    }

    /**
     * @return array<int, int>
     */
    public function toArray()
    {
        return array((int) ($this->limbs[0] << 16 | $this->limbs[1]));
    }

    /**
     * @return string
     */
    public function toString()
    {
        return
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xff);
    }

    /**
     * @return int
     */
    public function toInt()
    {
        return (int) (
            (($this->limbs[0] & 0xffff) << 16)
                |
            ($this->limbs[1] & 0xffff)
        );
    }

    /**
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function toInt32()
    {
        $return = new ParagonIE_Sodium_Core32_Int32();
        $return->limbs[0] = (int) ($this->limbs[0] & 0xffff);
        $return->limbs[1] = (int) ($this->limbs[1] & 0xffff);
        return $return;
    }

    /**
     * @return ParagonIE_Sodium_Core32_Int64
     */
    public function toInt64()
    {
        $return = new ParagonIE_Sodium_Core32_Int64();
        $neg = -(($this->limbs[0] >> 15) & 1);
        $return->limbs[0] = (int) ($neg & 0xffff);
        $return->limbs[1] = (int) ($neg & 0xffff);
        $return->limbs[2] = (int) ($this->limbs[0] & 0xffff);
        $return->limbs[3] = (int) ($this->limbs[1] & 0xffff);
        return $return;
    }

    /**
     * @return string
     */
    public function toReverseString()
    {
        return ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xff) .
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xff);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
