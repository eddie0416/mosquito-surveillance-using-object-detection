<?php
// 初始化 $searchSerial 為空字串
$searchSerial = '';
$startTime = '';
$endTime = '';

// 檢查是否有 POST 請求提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取 POST 表單提交的捕蚊燈編號
    $searchSerial = $_POST['searchSerial'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    
    // 清理和轉義使用者輸入，以避免 SQL 注入攻擊
    $searchSerial = htmlspecialchars($searchSerial);
    $startTime = htmlspecialchars($startTime);
    $endTime = htmlspecialchars($endTime);
}
?>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>查詢捕蚊燈資訊</title>
        <link rel="stylesheet" href="css/form.css">
    </head>

    <?php include("nav.php");?>

    <div class=form_body>
        
        <h2>查詢捕蚊燈資訊</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"  onsubmit="return validateTime()">
        <!--<form id="searchForm" action="search_lamp.php" method="POST">-->
            <label for="searchSerial">　捕蚊燈編號：</label>
            <input type="text" id="searchSerial" name="searchSerial" value="<?php echo $searchSerial; ?>" required>
            <br>
            <label for="timeRange">　時間區段：</label>
            <input type="datetime-local" id="startTime" name="startTime" value="<?php echo $startTime; ?>">
            <label for="timeRange"> ～ </label>
            <input type="datetime-local" id="endTime" name="endTime" value="<?php echo $endTime; ?>">
            
            <input type="submit" value="開始查詢">
        </form>

        <script>
        function validateTime() {
            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;

            // 檢查起始時間和結束時間是否都為空或都有值
            if ((startTime === '' && endTime === '') || (startTime !== '' && endTime !== '')) {
                // 檢查結束時間是否晚於起始時間
                if (startTime !== '' && endTime !== '' && startTime >= endTime) {
                    alert('查詢之結束時間必須晚於起始時間。');
                    return false; 
                } else {
                    return true; // 符合條件，允許表單提交
                }
            } else {
                alert('請填寫完整的時間區段，或者清除時間區段、僅以編號進行查詢。');
                return false; // 不符合條件，阻止表單提交
            }
        }
    </script>

    </div>

    <?php
    error_reporting(E_ALL); // 啟用所有錯誤報告
    ini_set('display_errors', 1); // 在網頁上顯示錯誤信息

    // 檢查是否有 POST 請求提交
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // 連接 MySQL 資料庫
        $servername = "localhost";
        $username = "root";
        $password = "u110029024";
        $dbname = "mosquitoDB";

        $conn = new mysqli($servername, $username, $password, $dbname);

        // 檢查連接是否成功
        if ($conn->connect_error) {
            die("連接失敗: " . $conn->connect_error);
        }

        // 獲取 POST 表單提交的捕蚊燈編號
        $searchSerial = $_POST['searchSerial'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];

        // 清理和轉義使用者輸入，以避免 SQL 注入攻擊
        $searchSerial = $conn->real_escape_string($searchSerial);

        // 準備 SQL 查詢語句
        $sql = "SELECT lamp.serial, lamp.locate, upload_log.vector_num, upload_log.nonvector_num, upload_log.humidity, upload_log.temperature, upload_log.shot_time
                FROM lamp
                INNER JOIN upload_log ON lamp.serial = upload_log.serial
                WHERE lamp.serial = '$searchSerial'";           

        // 如果有指定起始時間和結束時間，則加入時間範圍條件
        if (!empty($startTime) && !empty($endTime)) {
            // 將用戶輸入的時間從 12 小時制轉換為 24 小時制，並格式化為 SQL 中的 DATETIME 字串
            $formattedStartTime = date('Y-m-d H:i:s', strtotime($startTime));
            $formattedEndTime = date('Y-m-d H:i:s', strtotime($endTime));

            // 添加時間範圍條件到 SQL 查詢
            $sql .= " AND upload_log.shot_time BETWEEN '$formattedStartTime' AND '$formattedEndTime'";
        }

        // 執行 SQL 查詢
        $result = $conn->query($sql);

        // 檢查是否有查詢結果
        if ($result->num_rows > 0) {
            echo '<div id="searchResult">';
            echo '<h2>查詢結果：<br></h2>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>筆數</th>';
            echo '<th>捕蚊燈編號</th>';
            echo '<th>放置地點</th>';
            echo '<th>病媒蚊數量</th>';
            echo '<th>非病媒蚊數量</th>';
            echo '<th>濕度</th>';
            echo '<th>溫度</th>';
            echo '<th>拍攝時間</th>';
            echo '</tr>';
            echo '</thead>';

            echo '<tbody>';
            $counter = 1; // 初始化筆數計數器

            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter++ . '</td>'; // 自動編號筆數
                echo '<td>' . $row["serial"] . '</td>';
                echo '<td>' . $row["locate"] . '</td>';
                echo '<td>' . $row["vector_num"] . '</td>';
                echo '<td>' . $row["nonvector_num"] . '</td>';
                echo '<td>' . $row["humidity"] . '%</td>';
                echo '<td>' . round($row["temperature"], 1) . '°C</td>';
                echo '<td>' . $row["shot_time"] . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';

            echo '</table>';
            echo '<br><br>';
            echo '</div>';
        
        } else {
            echo '<div id="searchResult"><p>未找到符合的捕蚊燈紀錄。</p></div>';
        }

        // 關閉資料庫連接
        $conn->close();
    }
    ?>
</html>

