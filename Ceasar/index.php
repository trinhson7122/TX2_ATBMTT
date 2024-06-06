<?php
require "./Ceasar.php";

if (!empty($_POST)) {
    $key = @$_POST['key'];
    $text = @$_POST['text'];
    $text2 = @$_POST['text2'];
    $key = empty($key) ? 3 : $key;

    $ceasar = new Ceasar($key);

    // random key
    if (isset($_POST['randomKey'])) {
        $key = $ceasar->randomKey();
    }
    // ma hoa
    if (isset($_POST['encrypt'])) {
        $encrypted = $ceasar->encrypt($text);
        $text2 = $encrypted;
    }
    // giai ma
    if (isset($_POST['decrypt'])) {
        $decrypted = $ceasar->decrypt($text2);
    }
}
?>

<form method="post">
    <table>
        <tr>
            <td colspan="2"><button name="randomKey">Tạo ngẫu nhiên khóa</button></td>
        </tr>
        <tr>
            <td>Nhập khóa K:</td>
            <td><input type="number" name="key" value="<?= @$key ?>"></td>
        </tr>
        <tr>
            <td>Nhập chuỗi cần mã hóa:</td>
            <td><input type="text" name="text" value="<?= @$text ?>"></td>
        </tr>
        <tr>
            <td>Nhập chuỗi cần giải mã:</td>
            <td><input type="text" name="text2" value="<?= @$text2 ?>"></td>
        </tr>
        <tr>
            <td><button name="encrypt">Mã hóa</button></td>
            <td><button name="decrypt">Giải mã</button></td>
        </tr>
        <tr>
            <td>Nội dung sau khi mã hóa:</td>
            <td><?= @$encrypted ?></td>
        </tr>
        <tr>
            <td>Nội dung sau khi giải mã:</td>
            <td><?= @$decrypted ?></td>
        </tr>
    </table>
</form>