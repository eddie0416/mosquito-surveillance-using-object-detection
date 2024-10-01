<?php
if (isset($_POST['imagePath'])) {
    $imagePath = $_POST['imagePath'];
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            echo '文件已成功删除。';
        } else {
            echo '刪除文件時出錯。';
        }
    } else {
        echo '文件不存在。';
    }
} else {
    echo '未提供文件路径。';
}
?>