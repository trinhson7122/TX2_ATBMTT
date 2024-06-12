<?php

class Affine
{
    private array $z = [];
    private int $mod = 0;
    private array $key = [];
    const CHAR = ',';

    public function __construct(array $key = [])
    {
        $this->z = range('a', 'z');
        $this->mod = count($this->z);
        $this->key = array_map(function ($value) {
            return self::mod($value, $this->mod);
        }, $key);
    }
    public function encrypt(string $text): string
    {
        // chuyen ve chu thuong
        $text = strtolower($text);
        $encrypted = '';

        for ($i = 0; $i < strlen($text); $i++) {
            // tim kiem vi tri cua tung chu trong mang
            $index = array_search($text[$i], $this->z);

            // neu khong ton tai thi khong ma hoa
            if ($index === false) {
                $encrypted .= $text[$i];
                continue;
            }

            $encrypted .= $this->z[self::mod($index * $this->key[0] + $this->key[1], $this->mod)];
        }

        return $encrypted;
    }

    public function decrypt(string $text): string
    {
        // chuyen ve chu thuong
        $text = strtolower($text);
        $decrypted = '';

        for ($i = 0; $i < strlen($text); $i++) {
            // tim kiem vi tri cua tung chu trong mang
            $index = array_search($text[$i], $this->z);

            // neu khong ton tai thi khong giai ma
            if ($index === false) {
                $decrypted .= $text[$i];
                continue;
            }

            $decrypted .= $this->z[self::mod(self::modInverse($this->key[0], $this->mod) * ($index - $this->key[1]), $this->mod)];
        }

        return $decrypted;
    }

    /**
     * Hàm tính modulo
     *
     * @return int The modulo of $a and $b.
     */
    public static function mod(int $a, int $b): int
    {
        return (($a % $b) + $b) % $b;
    }

    public function randomKey(): array
    {
        $this->key = [];

        $b = rand(1, $this->mod - 1);
        $a = rand(1, $this->mod - 1);

        while (self::GCD($a, $this->mod) != 1) {
            $a = rand(1, $this->mod - 1);
        }

        $this->key = [$a, $b];

        return $this->key;
    }

    public static function printKey(array $key)
    {
        return implode(self::CHAR, $key);
    }

    public static function loadKey(string $key): array
    {
        return explode(self::CHAR, $key);
    }

    public function getKey(): array
    {
        return $this->key;
    }

    /**
     * Tinh phan tu nghich dao
     */
    public static function modInverse(int $a, int $m): int
    {
        $a = $a % $m;
        for ($x = 1; $x < $m; $x++) {
            if (($a * $x) % $m == 1) {
                return $x;
            }
        }
        return 1;
    }

    /**
     * Tinh GCD
     */
    public static function GCD($a, $b)
    {
        return $b == 0 ? $a : self::GCD($b, self::mod($a, $b));
    }
}
