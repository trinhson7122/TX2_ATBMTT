<?php

class Ceasar
{
    private array $z = [];
    private int $mod = 0;
    private int $key = 0;

    public function __construct(int $key = 3)
    {
        $this->z = range('a', 'z');
        $this->mod = count($this->z);
        $this->key = $key % $this->mod;
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

            $encrypted .= $this->z[self::mod($index + $this->key, $this->mod)];
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

            $decrypted .= $this->z[self::mod($index - $this->key, $this->mod)];
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

    public function randomKey(): int
    {
        $this->key = rand(0, $this->mod - 1);

        return $this->key;
    }
}