<!--
  橫幅 nav.php 
  首頁 home.php

  查詢地圖 search_map.php
  查詢個別捕蚊燈資訊 search_lamp_form.php
  病媒蚊統計資訊 #
  

  組員與分工 mat_ref.php （組員、分工、素材來源）
-->

<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <title>病媒蚊管理系統</title> <!--網站名稱-->
  <link rel="stylesheet" href="css/nav.css">
</head>

<!-- LOGO 標題 -->
<div class="header">
  <img src="./pic/logo.png" alt="Logo" width="12%" height="150%"> <!-- width="150" height="100"-->
  <h1>　病媒蚊管理系統　</h1>

  <!-- 導覽列(固定橫福) -->
  <nav class="navbar">
    <ul>

      <li><a href="upload_pic.php">上傳圖片</a></li> 

      <li class="submenu">
        <a href="#">開始查詢</a>
        <ul class="submenu-content">
          <li><a href="search_map.php">地圖查詢</a></li>
          <li><a href="search_lamp_form.php">個別捕蚊燈查詢</a></li>
          <li><a href="#">病媒蚊統計資訊</a></li>
        </ul>
      </li>

      <li><a href="mat_ref.php">組員與分工</a></li>
      <li><a href="home.php">回首頁</a></li>
      
    </ul>
  </nav>
</div>


</html>


