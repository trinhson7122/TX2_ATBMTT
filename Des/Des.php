<?php

class Des
{
    public string $key = "";
    private string $padChar = "0";
    private int $padLength = 8;
    private int $maxBit = 64;

    public function __construct(string $key = "123456")
    {
        $this->key = $this->convertTextToBin(base64_encode($key));
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    public function encrypt(string $text): string
    {
        $textBin = $this->convertTextToBin(base64_encode($text));
        $encryptPart = [];
        $result = '';

        // nếu tổng bit không chia hết cho 64 -> tính số bit tối thiểu cần để chia hết cho 64
        $bit = strlen($textBin) % $this->maxBit;
        if (strlen($textBin) % $this->maxBit != 0) {
            $bit = ((int)(strlen($textBin) / $this->maxBit) + 1) * $this->maxBit;
        } else {
            $bit = (strlen($textBin) / $this->maxBit) * $this->maxBit;
        }

        // đệm bit 0 vào bên trái nếu cần
        for ($i = 0; $i < $bit; $i = $i + $this->maxBit) {
            $part = substr($textBin, $i, $this->maxBit);
            $part = str_pad($part, $this->maxBit, $this->padChar, STR_PAD_LEFT);

            $encryptPart[] = $part;
        }

        // tạo khóa con
        $subkeys = $this->generateSubkeys($this->key);

        // mã hóa cho từng 64 bit
        foreach ($encryptPart as $encrypt) {
            // hoán vị IP
            $block = $this->initialPermutation($encrypt);

            // lấy L0 và R0
            $left = substr($block, 0, 32);
            $right = substr($block, 32, 32);

            // 16 vòng lặp
            for ($i = 0; $i < 16; $i++) {
                $newRight = $this->xorBinaryStrings($left, $this->feistelFunction($right, $subkeys[$i]));
                $left = $right;
                $right = $newRight;
            }

            // hoán vị IP^-1
            $encryptedBlock = $this->finalPermutation($right . $left);

            $result .= $this->convertBinToText($encryptedBlock);
        }

        return base64_encode($result);
    }

    public function decrypt(string $text): string
    {
        // tạo khóa con
        $subkeys = $this->generateSubkeys($this->key);

        $textBin = $this->convertTextToBin(base64_decode($text));
        $decryptPart = [];
        $result = '';

        // nếu tổng bit không chia hết cho 64 -> tính số bit tối thiểu cần để chia hết cho 64
        $bit = strlen($textBin) % $this->maxBit;
        if (strlen($textBin) % $this->maxBit != 0) {
            $bit = ((int)(strlen($textBin) / $this->maxBit) + 1) * $this->maxBit;
        } else {
            $bit = (strlen($textBin) / $this->maxBit) * $this->maxBit;
        }

        // thêm từng phần cần giải mã vào mảng
        for ($i = 0; $i < $bit; $i = $i + $this->maxBit) {
            $part = substr($textBin, $i, $this->maxBit);

            $decryptPart[] = $part;
        }

        // giải mã từng phần
        foreach ($decryptPart as $decrypted) {
            // hoan vi IP
            $block = $this->initialPermutation($decrypted);

            // lấy L0 và R0
            $left = substr($block, 0, 32);
            $right = substr($block, 32, 32);

            // 16 vong lap
            for ($i = 15; $i >= 0; $i--) {
                $newRight = $this->xorBinaryStrings($left, $this->feistelFunction($right, $subkeys[$i]));
                $left = $right;
                $right = $newRight;
            }

            // hoan vi IP^-1
            $decryptedBlock = $this->finalPermutation($right . $left);

            $result .= $this->convertBinToText($decryptedBlock);
        }

        return base64_decode($result);
    }

    private function convertTextToAsciiArray(string $text): array
    {
        $strlen = strlen($text);

        $asciiArray = [];

        for ($i = 0; $i < $strlen; $i++) {
            $asciiArray[] = ord($text[$i]);
        }

        return $asciiArray;
    }

    private function convertTextToBin(string $text)
    {
        $ascii = $this->convertTextToAsciiArray($text);

        array_walk($ascii, function (&$value) {
            $value = decbin($value);
            $value = str_pad($value, $this->padLength, '0', STR_PAD_LEFT);
        });

        return implode('', $ascii);
    }

    private function convertBinToText(string $bin): string
    {
        $strlen = strlen($bin);
        $text = "";

        for ($i = 0; $i < $strlen; $i = $i + $this->padLength) {
            $text .= chr(bindec(substr($bin, $i, $this->padLength)));
        }

        return $text;
    }

    public function randomKey(): string
    {
        $this->key = substr(md5(mt_rand()), 0, 10);

        $result = $this->key;

        $this->key = $this->convertTextToBin(base64_encode($this->key));

        return $result;
    }

    public function getKey(): string
    {
        return base64_decode($this->convertBinToText($this->key));
    }

    private function getIP(): array
    {
        return [
            58, 50, 42, 34, 26, 18, 10, 2,
            60, 52, 44, 36, 28, 20, 12, 4,
            62, 54, 46, 38, 30, 22, 14, 6,
            64, 56, 48, 40, 32, 24, 16, 8,
            57, 49, 41, 33, 25, 17, 9, 1,
            59, 51, 43, 35, 27, 19, 11, 3,
            61, 53, 45, 37, 29, 21, 13, 5,
            63, 55, 47, 39, 31, 23, 15, 7
        ];
    }

    private function getInvIP(): array
    {
        return [
            40, 8, 48, 16, 56, 24, 64, 32,
            39, 7, 47, 15, 55, 23, 63, 31,
            38, 6, 46, 14, 54, 22, 62, 30,
            37, 5, 45, 13, 53, 21, 61, 29,
            36, 4, 44, 12, 52, 20, 60, 28,
            35, 3, 43, 11, 51, 19, 59, 27,
            34, 2, 42, 10, 50, 18, 58, 26,
            33, 1, 41, 9, 49, 17, 57, 25
        ];
    }

    private function getSBox(int $key = 1): array
    {
        $sBox = [
            // S1
            [
                [14, 4, 13, 1, 2, 15, 11, 8, 3, 10, 6, 12, 5, 9, 0, 7],
                [0, 15, 7, 4, 14, 2, 13, 1, 10, 6, 12, 11, 9, 5, 3, 8],
                [4, 1, 14, 8, 13, 6, 2, 11, 15, 12, 9, 7, 3, 10, 5, 0],
                [15, 12, 8, 2, 4, 9, 1, 7, 5, 11, 3, 14, 10, 0, 6, 13]
            ],
            // S2
            [
                [15, 1, 8, 14, 6, 11, 3, 4, 9, 7, 2, 13, 12, 0, 5, 10],
                [3, 13, 4, 7, 15, 2, 8, 14, 12, 0, 1, 10, 6, 9, 11, 5],
                [0, 14, 7, 11, 10, 4, 13, 1, 5, 8, 12, 6, 9, 3, 2, 15],
                [13, 8, 10, 1, 3, 15, 4, 2, 11, 6, 7, 12, 0, 5, 14, 9]
            ],
            // S3
            [
                [10, 0, 9, 14, 6, 3, 15, 5, 1, 13, 12, 7, 11, 4, 2, 8],
                [13, 7, 0, 9, 3, 4, 6, 10, 2, 8, 5, 14, 12, 11, 15, 1],
                [13, 6, 4, 9, 8, 15, 3, 0, 11, 1, 2, 12, 5, 10, 14, 7],
                [1, 10, 13, 0, 6, 9, 8, 7, 4, 15, 14, 3, 11, 5, 2, 12]
            ],
            // S4
            [
                [7, 13, 14, 3, 0, 6, 9, 10, 1, 2, 8, 5, 11, 12, 4, 15],
                [13, 8, 11, 5, 6, 15, 0, 3, 4, 7, 2, 12, 1, 10, 14, 9],
                [10, 6, 9, 0, 12, 11, 7, 13, 15, 1, 3, 14, 5, 2, 8, 4],
                [3, 15, 0, 6, 10, 1, 13, 8, 9, 4, 5, 11, 12, 7, 2, 14]
            ],
            // S5
            [
                [2, 12, 4, 1, 7, 10, 11, 6, 8, 5, 3, 15, 13, 0, 14, 9],
                [14, 11, 2, 12, 4, 7, 13, 1, 5, 0, 15, 10, 3, 9, 8, 6],
                [4, 2, 1, 11, 10, 13, 7, 8, 15, 9, 12, 5, 6, 3, 0, 14],
                [11, 8, 12, 7, 1, 14, 2, 13, 6, 15, 0, 9, 10, 4, 5, 3]
            ],
            // S6
            [
                [12, 1, 10, 15, 9, 2, 6, 8, 0, 13, 3, 4, 14, 7, 5, 11],
                [10, 15, 4, 2, 7, 12, 9, 5, 6, 1, 13, 14, 0, 11, 3, 8],
                [9, 14, 15, 5, 2, 8, 12, 3, 7, 0, 4, 10, 1, 13, 11, 6],
                [4, 3, 2, 12, 9, 5, 15, 10, 11, 14, 1, 7, 6, 0, 8, 13]
            ],
            // S7
            [
                [4, 11, 2, 14, 15, 0, 8, 13, 3, 12, 9, 7, 5, 10, 6, 1],
                [13, 0, 11, 7, 4, 9, 1, 10, 14, 3, 5, 12, 2, 15, 8, 6],
                [1, 4, 11, 13, 12, 3, 7, 14, 10, 15, 6, 8, 0, 5, 9, 2],
                [6, 11, 13, 8, 1, 4, 10, 7, 9, 5, 0, 15, 14, 2, 3, 12]
            ],
            // S8
            [
                [13, 2, 8, 4, 6, 15, 11, 1, 10, 9, 3, 14, 5, 0, 12, 7],
                [1, 15, 13, 8, 10, 3, 7, 4, 12, 5, 6, 11, 0, 14, 9, 2],
                [7, 11, 4, 1, 9, 12, 14, 2, 0, 6, 10, 13, 15, 3, 5, 8],
                [2, 1, 14, 7, 4, 10, 8, 13, 15, 12, 9, 0, 3, 5, 6, 11]
            ]
        ];

        return $sBox[$key - 1];
    }

    private function getPBox(): array
    {
        return [
            16, 7, 20, 21,
            29, 12, 28, 17,
            1, 15, 23, 26,
            5, 18, 31, 10,
            2, 8, 24, 14,
            32, 27, 3, 9,
            19, 13, 30, 6,
            22, 11, 4, 25
        ];
    }

    private function getPC(int $key = 1): array
    {
        $pBox = [
            [
                57, 49, 41, 33, 25, 17, 9,
                1, 58, 50, 42, 34, 26, 18,
                10, 2, 59, 51, 43, 35, 27,
                19, 11, 3, 60, 52, 44, 36,
                63, 55, 47, 39, 31, 23, 15,
                7, 62, 54, 46, 38, 30, 22,
                14, 6, 61, 53, 45, 37, 29,
                21, 13, 5, 28, 20, 12, 4
            ],
            [
                14, 17, 11, 24, 1, 5,
                3, 28, 15, 6, 21, 10,
                23, 19, 12, 4, 26, 8,
                16, 7, 27, 20, 13, 2,
                41, 52, 31, 37, 47, 55,
                30, 40, 51, 45, 33, 48,
                44, 49, 39, 56, 34, 53,
                46, 42, 50, 36, 29, 32
            ],
        ];

        return $pBox[$key - 1];
    }

    private function generateSubkeys(string $key): array
    {
        $rotations = [1, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1];

        $key56 = '';
        foreach ($this->getPC(1) as $index) {
            $key56 .= $key[$index - 1];
        }

        $c = substr($key56, 0, 28);
        $d = substr($key56, 28, 28);

        $subkeys = [];
        foreach ($rotations as $rotation) {
            $c = substr($c, $rotation) . substr($c, 0, $rotation);
            $d = substr($d, $rotation) . substr($d, 0, $rotation);
            $cd = $c . $d;

            $subkey = '';
            foreach ($this->getPC(2) as $index) {
                $subkey .= $cd[$index - 1];
            }
            $subkeys[] = $subkey;
        }

        return $subkeys;
    }

    /*
    * Hoán vị IP
    */
    private function initialPermutation($block): string
    {
        $permutedBlock = '';
        foreach ($this->getIP() as $index) {
            $permutedBlock .= $block[$index - 1];
        }

        return $permutedBlock;
    }

    /*
    * Ham f
    */
    private function feistelFunction($r, $subkey)
    {
        // Expansion table
        $expansionTable = [
            32, 1,  2,  3,  4,  5,
            4,  5,  6,  7,  8,  9,
            8,  9,  10, 11, 12, 13,
            12, 13, 14, 15, 16, 17,
            16, 17, 18, 19, 20, 21,
            20, 21, 22, 23, 24, 25,
            24, 25, 26, 27, 28, 29,
            28, 29, 30, 31, 32, 1
        ];

        // Expansion
        $expandedR = '';
        foreach ($expansionTable as $index) {
            $expandedR .= $r[$index - 1];
        }

        // XOR with subkey
        $xorResult = $this->xorBinaryStrings($expandedR, $subkey);

        // S-box substitution
        $sBoxResult = '';
        for ($i = 0; $i < 8; $i++) {
            $sBoxInput = substr($xorResult, $i * 6, 6);
            $row = bindec($sBoxInput[0] . $sBoxInput[5]);
            $col = bindec(substr($sBoxInput, 1, 4));
            $sBoxResult .= str_pad(decbin($this->getSBox($i + 1)[$row][$col]), 4, '0', STR_PAD_LEFT);
        }

        // Permutation
        $permutedResult = '';
        foreach ($this->getPBox() as $index) {
            $permutedResult .= $sBoxResult[$index - 1];
        }

        return $permutedResult;
    }

    /*
    * Tính XOR
    */
    private function xorBinaryStrings(string $a, string $b): string
    {
        $result = '';
        $length = strlen($a);

        for ($i = 0; $i < $length; $i++) {
            $result .= $a[$i] ^ $b[$i];
        }

        return $result;
    }

    /*
    * Hoán vị IP^-1
    */
    private function finalPermutation(string $block): string
    {
        $permutedBlock = '';

        foreach ($this->getInvIP() as $index) {
            $permutedBlock .= $block[$index - 1];
        }

        return $permutedBlock;
    }
}
