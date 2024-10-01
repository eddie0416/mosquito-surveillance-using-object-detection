<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>病媒蚊管理系統</title>
        <link rel="stylesheet" href="css/home.css">

    </head>

    <?php
    include("nav.php");
    ?>

    <div class = home_body>

        <!--放置視窗盒子元素（介紹BMI、BMR）-->
        <div class="container">
            <div class="box">
            <img src="./pic/box1.png" alt="viewport box">
            <div class="text-overlay">
                <h2>關於登革熱...<br></h2>
                <p>全世界約有一半人口，約近39億人生活在登革熱流行區，每年<br>
                    約有3.9億人感染登革熱，其中約9,600萬人出現不同嚴重程度<br>
                    之臨床症狀。<br></p>

                <h2>病媒蚊有哪些？<br></h2>
                <p>臺灣重要的病媒蚊為埃及斑蚊（Aedes aegypti）及白線斑蚊<br>
                （Aedes albopictus），登革熱主要傳播方式為受帶登革病毒的<br>
                病媒蚊叮咬，而其他病媒蚊叮咬處於可傳染期之登革熱患者後，<br>
                該病媒蚊亦會被病毒感染，再叮咬其他健康人即造成社區傳播。<br></p>
                
            </div>
            
            </div>
            <!--<p id="begin">關於登革熱，<br>你應該要知道的事...<br></p>-->
            
        </div>
        <p id="begin">關於登革熱，<br>你應該要知道的事...<br></p>
        <button id="startButton">上傳圖片</button>

        <script>
            window.onload = function() {
                detectOrientation(); // 檢測設備方向
                show_button(); // 按鈕上升進入
            }; 


            function show_button(){
                var home_body = document.querySelector('.home_body');
                home_body.classList.add('show-button');
            }

            document.getElementById('startButton').addEventListener('click', function() {
                window.location = "upload_pic.php";
            }); //按鈕點擊時跳轉到 查詢 頁面

            // 定義函式以獲取並顯示視口寬高
            function displayViewportWH() {
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                console.log("寬度：", viewportWidth, "px；","高度：", viewportHeight, "px");
            }

            // 載入時執行一次
            displayViewportWH();

            // 監聽視窗大小變化事件
            window.addEventListener('resize', displayViewportWH);

            
            // 檢查設備開啟時的方向
            function detectOrientation() {
                if (window.matchMedia("(orientation: portrait)").matches) {
                    // 當設備以直向開啟時跳提醒視窗，以橫向開啟
                    alert("建議以橫向瀏覽本網站以獲得最佳體驗。");
                }
            }

            window.addEventListener("resize", detectOrientation); //"orientationchange"無法查詢直或橫；"resize"可偵測當長大於寬時跳對話框
            
        </script>

    </div>

</html>