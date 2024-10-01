#include "esp_camera.h"
#include <WiFi.h>
#include "DHT.h"
#include "ESP32_OV5640_AF.h"
#include "Timer.h"  //引用Timer程式庫

#define CAMERA_MODEL_AI_THINKER // Has PSRAM
#define DHT_PIN 2

#include "camera_pins.h"
Timer tcb; //建立計時器物件(T可以自訂名稱)
OV5640 ov5640 = OV5640();
WiFiClient client;

const char* ssid = "eddie";
const char* password = "12345678";
const char* ntpServer = "pool.ntp.org";
const int GMT_OFFSET_SEC = 8 * 3600; // GMT+8時區
const char* SERVER = "34.83.20.35";
const char* num = "00";
const int PORT = 80;  // Or the port you're using for the server
const char* UPLOAD_URL = "/esp32cam";  // The URL endpoint for uploading images
float hmem = 0;
float tmem = 0;
/*設定led腳位，初始亮度為0*/
int LED_BUILTIN = 4;
int brightness = 100; //range 0-255
/*設定led腳位，初始亮度為0*/
DHT dht(DHT_PIN , DHT11);

const unsigned long interval = 10 * 1000; // 30秒的毫秒數

void startCameraServer();
void setupLedFlash(int pin);

void setup() {
  Serial.begin(115200);
  Serial.setDebugOutput(true);
  Serial.println();
pinMode(LED_BUILTIN, OUTPUT);
pinMode(12,OUTPUT);
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.frame_size = FRAMESIZE_UXGA;
  config.pixel_format = PIXFORMAT_JPEG; // for streaming
  //config.pixel_format = PIXFORMAT_RGB565; // for face detection/recognition
  config.grab_mode = CAMERA_GRAB_WHEN_EMPTY;
  config.fb_location = CAMERA_FB_IN_PSRAM;
  config.jpeg_quality = 10;
  config.fb_count = 2;
  
  // if PSRAM IC present, init with UXGA resolution and higher JPEG quality
  //                      for larger pre-allocated frame buffer.
  if(config.pixel_format == PIXFORMAT_JPEG){
    if(psramFound()){
      config.jpeg_quality = 10;
      config.fb_count = 2;
      config.grab_mode = CAMERA_GRAB_LATEST;
    } else {
      // Limit the frame size when PSRAM is not available
      config.frame_size = FRAMESIZE_SVGA;
      config.fb_location = CAMERA_FB_IN_DRAM;
    }
  } else {
    // Best option for face detection/recognition
    config.frame_size = FRAMESIZE_240X240;
#if CONFIG_IDF_TARGET_ESP32S3
    config.fb_count = 2;
#endif
  }

#if defined(CAMERA_MODEL_ESP_EYE)
  pinMode(13, INPUT_PULLUP);
  pinMode(14, INPUT_PULLUP);
#endif

  // camera init
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x", err);
    return;
  }

  sensor_t * s = esp_camera_sensor_get();
  // initial sensors are flipped vertically and colors are a bit saturated
  if (s->id.PID == OV3660_PID) {
    s->set_vflip(s, 1); // flip it back
    s->set_brightness(s, 1); // up the brightness just a bit
    s->set_saturation(s, -2); // lower the saturation
  }
  // drop down frame size for higher initial frame rate
  if(config.pixel_format == PIXFORMAT_JPEG){
    s->set_framesize(s, FRAMESIZE_UXGA); //設定拍照解析度
  }

#if defined(CAMERA_MODEL_M5STACK_WIDE) || defined(CAMERA_MODEL_M5STACK_ESP32CAM)
  s->set_vflip(s, 1);
  s->set_hmirror(s, 1);
#endif

#if defined(CAMERA_MODEL_ESP32S3_EYE)
  s->set_vflip(s, 1);
#endif

// Setup LED FLash if LED pin is defined in camera_pins.h
#if defined(LED_GPIO_NUM)
  setupLedFlash(LED_GPIO_NUM);
#endif

  WiFi.begin(ssid, password);
  WiFi.setSleep(false);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.println("WiFi connected");

  dht.begin();

  startCameraServer();

  Serial.print("Camera Ready! Use 'http://");
  Serial.print(WiFi.localIP());
  Serial.println("' to connect");

  sensor_t* sensor = esp_camera_sensor_get();
  ov5640.start(sensor);

  if (ov5640.focusInit() == 0) {
    Serial.println("OV5640_Focus_Init Successful!");
  }

  if (ov5640.autoFocusMode() == 0) {
    Serial.println("OV5640_Auto_Focus Successful!");
  }
configTime(8 * 3600, 0, "pool.ntp.org");
tcb.every(interval, measureTemperatureAndHumidity);
}

void measureTemperatureAndHumidity() {
  // 測量溫濕度
  float h = dht.readHumidity();
  float t = dht.readTemperature();
  postImage(h, t, num);
 

  // 如果溫濕度有效，則顯示
  if (!isnan(h) && !isnan(t)) {
    Serial.print("Temperature: ");
    Serial.print(t);
    Serial.print(" °C\t");
    Serial.print("Humidity: ");
    Serial.print(h);
    Serial.println(" %");
  } else {
    Serial.println("Failed to read from DHT sensor!");
  }
}

void postImage(float humi, float temp, const char* num) {
  String humidity = String(humi, 1);
  String temperature = String(temp, 1);
  String number = String(num);

  camera_fb_t *fb = NULL;    // 宣告儲存影像結構資料的變數
   // 控制繼電器開關
  digitalWrite(12, HIGH);
  //delay(10000);
  analogWrite(LED_BUILTIN, brightness);
  delay(500);
  fb = esp_camera_fb_get();  // 拍照
  delay(100);
  analogWrite(LED_BUILTIN, 0);
  digitalWrite(12, LOW);
  //delay(10000);

  if(!fb) {
    Serial.println("無法取得影像資料…");
    delay(1000);
    ESP.restart();  // 重新啟動
  }

  Serial.printf("連接伺服器：%s\n", SERVER);

  if (client.connect(SERVER, PORT)) {
    Serial.println("開始上傳影像…");     

    String photoData = "--ESP32CAM\r\n";
    photoData += "Content-Disposition: form-data; name=\"filename\"; filename=\"pict.jpg\"\r\n";
    photoData += "Content-Type: image/jpeg\r\n";
    photoData += "\r\n";
    /*字串boundary*/
    String strData = "\r\n--ESP32CAM\r\n";
    strData += "Content-Disposition: form-data; name=\"humidity\"\r\n";
    strData += "\r\n";
    strData += humidity;
    strData += "\r\n";
    strData += "--ESP32CAM\r\n";
    strData += "Content-Disposition: form-data; name=\"temperature\"\r\n";
    strData += "\r\n";
    strData += temperature;
    strData += "\r\n";
    strData += "--ESP32CAM\r\n";
    strData += "Content-Disposition: form-data; name=\"number\"\r\n";
    strData += "\r\n";
    strData += number;
    /*字串boundary*/

    String boundEnd = "\r\n--ESP32CAM--\r\n";

    uint32_t imgSize = fb->len;  // 取得影像檔的大小
    uint32_t payloadSize = photoData.length() + imgSize + strData.length() + boundEnd.length();
    Serial.print(String(payloadSize));
    String httpMsg = String("POST ") + UPLOAD_URL + " HTTP/1.1\r\n";
    Serial.print(httpMsg);
    httpMsg += String("Host: ") + SERVER + "\r\n";
    httpMsg += "User-Agent: Arduino/ESP32CAM\r\n";
    httpMsg += "Content-Length: " + String(payloadSize) + "\r\n";
    httpMsg += "Content-Type: multipart/form-data; boundary=ESP32CAM\r\n";
    httpMsg += "\r\n";

    Serial.print(httpMsg);
    // 送出HTTP標頭訊息
    client.print(httpMsg.c_str());
    // 送出photo boundary
    client.print(photoData.c_str());

    // 上傳圖片檔案
    uint8_t *buf = fb->buf;
    for (uint32_t i=0; i<imgSize; i+=1024) {
      if (i+1024 < imgSize) {
        client.write(buf, 1024);
        buf += 1024;
      } else if (imgSize%1024>0) {
        uint32_t remainder = imgSize%1024;
        client.write(buf, remainder);
      }
    }
    esp_camera_fb_return(fb); //釋放照片緩衝區

    client.print(strData.c_str());// 送出字串 boundary

    client.print(boundEnd.c_str());// 送出boundary結尾

    // 等待伺服器的回應（10秒）
    long timout = 30000L + millis();

    while (timout > millis()) {
      Serial.print(".");
      delay(100);

      if (client.available()){
        // 讀取伺服器的回應
        Serial.println("\n伺服器回應：");
        String line = client.readStringUntil('\r');
        Serial.println(line);
        break;
      }
    }
    Serial.println("關閉連線");
  } else {
    Serial.printf("無法連接伺服器：%s\n", SERVER);
  }
  client.stop();  // 關閉用戶端
}

void loop() {
  // Do nothing. Everything is done in another task by the web server
  //delay(10000);
  uint8_t rc = ov5640.getFWStatus();
  //Serial.printf("FW_STATUS = 0x%x\n", rc);
  tcb.update();
  measureTemperatureAndHumidity();
  if (rc == -1) {
    Serial.println("Check your OV5640");
  } else if (rc == FW_STATUS_S_FOCUSED) {
    Serial.println("Focused!");
  } else if (rc == FW_STATUS_S_FOCUSING) {
    Serial.println("Focusing!");
  } else {
  }
}