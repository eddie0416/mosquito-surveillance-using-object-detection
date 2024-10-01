<?php
// 設定資料庫連線參數
$servername = "localhost";
$username = "root";
$password = "u110029024";
$dbname = "mosquitoDB";

// 建立與資料庫的連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線是否成功
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
?>