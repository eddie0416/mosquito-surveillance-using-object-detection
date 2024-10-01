<?php
include "connMySQL.php"; // 連接資料庫
date_default_timezone_set('Asia/Taipei'); // 設置時區為 GMT+8
header('Content-Type: application/json'); // 設置回應的內容類型為 JSON
$response = array('message' => '');

if (isset($_POST['serial']) && isset($_POST['town']) && isset($_POST['village'])) {
    // 取得圖片路徑並轉義HTML特殊字符以防止XSS攻擊
    $serial = htmlspecialchars($_POST['serial'], ENT_QUOTES, 'UTF-8');
    $town = htmlspecialchars($_POST['town']);
    $village = htmlspecialchars($_POST['village']);
    $start_date = '2024-05-01';
    $end_date = date('Y-m-d');
    
    // 準備 SQL 查詢
    $sql = "SELECT shot_time, humidity, temperature, vector_num, nonvector_num
            FROM upload_log
            WHERE serial = '$serial'
            ORDER BY shot_time DESC
            LIMIT 1";

    // 執行查詢
    $result = mysqli_query($conn, $sql);

    if ($result) {
        // 檢查是否有結果
        if (mysqli_num_rows($result) > 0) {
            // 獲取結果行
            $row = mysqli_fetch_assoc($result);
            // 顯示結果
            /*echo "shot_time: " . $row["shot_time"]. "<br>";
            echo "humidity: " . $row["humidity"]. "<br>";
            echo "temperature: " . $row["temperature"]. "<br>";
            echo "vector_num: " . $row["vector_num"]. "<br>";
            echo "nonvector_num: " . $row["nonvector_num"]. "<br>";*/
            $response['message'] = 'sql success';
            // 爬蟲
            $ch = curl_init(); // 初始化 cURL
            $url = "http://34.83.20.35/dengueQuery";
            $data = array(
                'town' => $town,
                'village' => $village,
                'start_date' => $start_date,
                'end_date' => $end_date
            );
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ));

            $responseData = curl_exec($ch);
            if($responseData == false){
                $response['message'] = 'cURL error: ' . curl_error($ch);
            }else{
                $response['message'] = 'crawler success';
                $responseData = json_decode($responseData, true);
                $return_dict = array_merge($row, $responseData, $response); // 將 SQL 查詢結果和病例數爬蟲結果合併
                echo json_encode($return_dict);
            }
        } else {
            $response['message'] = 'no record find';
        }
        // 釋放查詢結果
        mysqli_free_result($result);
    } else {
        $response['message'] = "sql fail".mysqli_error($conn);
        //echo "查詢失敗: " . mysqli_error($conn);
    }
} else {
    //echo 'post fail !';
    $response['message'] = "post fail";
}

// 關閉資料庫連接
mysqli_close($conn);
?>