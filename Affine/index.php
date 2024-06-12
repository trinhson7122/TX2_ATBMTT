<?php
require "./Affine.php";

if (!empty($_POST)) {
    $key = @$_POST['key'];
    $text = @$_POST['text'];
    $text2 = @$_POST['text2'];

    $affine = new Affine(Affine::loadKey($key));
    $key = $affine->getKey();

    // random key
    if (isset($_POST['randomKey'])) {
        $key = $affine->randomKey();
    }

    // set key neu chua co
    if (empty($affine->getKey())) {
        $key = $affine->randomKey();
    }

    // ma hoa
    if (isset($_POST['encrypt'])) {
        $encrypted = $affine->encrypt($text);
        $text2 = $encrypted;
    }
    // giai ma
    if (isset($_POST['decrypt'])) {
        $decrypted = $affine->decrypt($text2);
    }

    // print_r($key);
}
?>

<form method="post">
    <table>
        <tr>
            <td colspan="2"><button name="randomKey">Tạo ngẫu nhiên khóa</button></td>
        </tr>
        <tr>
            <td>Nhập khóa K:</td>
            <td><input readonly type="text" name="key" value="<?= Affine::printKey($key ?? []) ?>"></td>
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