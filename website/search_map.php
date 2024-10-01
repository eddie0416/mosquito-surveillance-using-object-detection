<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>地圖查詢</title>
    <link rel="stylesheet" href="css/map.css">

    <?php include("nav.php");?>

    <!-- 引入 Google 地圖 JavaScript API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=??"></script>    
</head>
<div class= map_body>
    <h2>點選地圖上捕蚊燈以查看更多資訊：</h2>
    
    <div id="responseContainer" > </div>
    <!-- 在這裡建立地圖容器 -->
    <div id="map"></div>

    <script>
        // 全域變數，用於存儲 postData 函數接收到的字典資料
        let responseData = null;

        // 函數，用於發送 POST 請求
        async function postData(town, village, serial) {
            // 構建要發送的資料
            const data = new URLSearchParams();
            data.append('town', town);
            data.append('village', village);
            data.append('serial', serial);

            try {
                // 使用 fetch API 發送 POST 請求
                const response = await fetch('map_query.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: data.toString()
                });

                // 確認回應狀態
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }

                // 將回應的 JSON 資料存到 responseData 中
                responseData = await response.json();
                console.log(responseData); // 輸出 responseData 確認

                // 將 responseData 的內容顯示在網頁上
                //document.getElementById('responseContainer').innerText = JSON.stringify(responseData, null, 2);
                /*{
                "shot_time": "2024-05-22 16:54:22",
                "humidity": "56.00",
                "temperature": "28.00",
                "vector_num": "3",
                "nonvector_num": "2",
                "foreign": 0,
                "indigenous": 0,
                "town": "三民區",
                "village": "安生里",
                "message": "crawler success"
                }*/
                // 初始化地圖並添加標記
                initMap();

            } catch (error) {
                console.error('There was a problem with the fetch operation:', error);
                //document.getElementById('responseContainer').innerText = 'Error: ' + error.message;
            }
        }

        // 初始化地圖並添加標記
        function initMap() {
            // 初始化地圖的中心位置（這裡設為三民區的中心經緯度）
            var center = { lat: 22.6495, lng: 120.3069 };

            // 建立新的 Google 地圖
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 14, // 初始縮放級別
                center: center // 設定地圖中心位置
            });

            // 創建標記
            var markers = [
                {
                    position: { lat: 22.64798185532667, lng: 120.31066046895356 },
                    title: '捕蚊燈00',
                    description: '位置：' + responseData['town'] +' '+ responseData['village'] +'<br>'+
                        '更新時間：' + responseData['shot_time'] +'<br>'+
                        '病媒蚊數量：' + responseData['vector_num'] +'<br>'+
                        '非病媒蚊數量：' + responseData['nonvector_num'] +'<br>'+
                        '本土病例：' + responseData['indigenous'] +'<br>'+
                        '境外病例：' + responseData['foreign'] +'<br>'+
                        '溫度：' + responseData['temperature'] +'˚C'+'<br>'+
                        '濕度：' + responseData['humidity']+'%'
                }
                // 可再增加其他標記
            ];

            // 將標記添加到地圖上
            markers.forEach(function(markerInfo) {
                var marker = new google.maps.Marker({
                    position: markerInfo.position,
                    map: map,
                    title: markerInfo.title
                });

                var contentString = '<div id="content">'
                    + '<h3>' + markerInfo.title + '</h3>'
                    + '<p>' + markerInfo.description + '</p>'
                    // +'<img src="' + markerInfo.image + '" alt="Marker Image">'
                    + '</div>';

                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });

                // 在點擊標記時顯示訊息窗口
                marker.addListener('click', function() {
                    infowindow.open(map, marker);
                });
            });
        }

        // 發送 POST 請求並初始化地圖
        postData('三民區', '安生里', '00');
    </script>
    </div>
</body>
</html>