<script>
</script>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳圖片辨識</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <?php
    include("nav.php");
    ?>
    <div class="form_body">
        <h2>上傳圖片辨識病媒蚊：</h2>
        <form action="http://34.83.20.35/esp32cam" method="post" enctype="multipart/form-data">
            <label for="file">選取圖片：</label>
            <input type="file" id="file" name="filename">
            <input type="submit" value="上傳圖片">
            <!-- 使用隱藏的input欄位 -->
            <input type="hidden" id="number" name="number" value="experience">
            <br>
        </form>
        
    </div>
</body>


</html>