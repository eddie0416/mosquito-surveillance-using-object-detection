<?php
if (isset($_GET['filename'])) {
    // 取得圖片路徑並轉義HTML特殊字符以防止XSS攻擊
    $filename = htmlspecialchars($_GET['filename']);
    $detected_dir = '/var/www/html/112-2_topic/detected_photo/';
    $imagePath = $detected_dir . $filename;
    $imageURL = '/112-2_topic/detected_photo/' . $filename;
    // 檢查文件是否存在
    if (file_exists($imagePath)) {
        
        echo '<script>';
        echo 'var imageURL = "' . $imageURL . '";';
        echo 'var imagePath = "' . $imagePath . '";';
        echo '</script>';
    } else {
        echo '圖片文件不存在。';
    }
} else {
    echo '未提供圖片路徑。';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳圖片辨識</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <?php include("nav.php"); ?>
    <div class="form_body">
        <h2>辨識結果：</h2> 
        <div class="searchResult">
            
            <img id="myImage" src="" alt="result_pic" class="responsive-image">
        </div>
        <button id="startButton" onclick="window.location.href='index.php'">返回重新查詢</button>
    </div>

    <script>
        document.getElementById('myImage').src = imageURL;

        var img = document.getElementById('myImage');
        img.onload = function() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'delete_image.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText); 
                }
            };
            xhr.send('imagePath=' + encodeURIComponent(imagePath));
        };

        document.getElementById('startButton').addEventListener('click', function() {
            window.location = "upload_pic.php";
        });
    </script>
</body>
</html>