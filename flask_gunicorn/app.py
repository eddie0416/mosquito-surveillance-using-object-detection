#若想在putty看到執行結果 可以直接在app.py(本文件)中使用print
from datetime import datetime
import pytz
import mysql.connector
import logging
import os
import subprocess
import filetype
from flask import Flask, Response, flash, request, redirect, url_for, render_template, jsonify, send_file
from flask_cors import CORS
from werkzeug.utils import secure_filename
from base64 import b64encode
from io import BytesIO
from PIL import Image
import requests
import re

UPLOAD_FOLDER = '/var/www/html/112-2_topic/mosquito_pic/'
APP_PATH = '/var/www/html/112-2_topic/'
DETECTED_PATH = '/var/www/html/112-2_topic/detected_photo/'


app = Flask(__name__)
CORS(app, resources={r"/esp32cam": {"origins": "http://34.83.20.35:8000"}})
logging.basicConfig(filename='app.log', level=logging.INFO)
app.secret_key = b'_5#y2L"F4Q8z\n\xec]/'     # 請自行修改密鑰
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER 
app.config['MAX_CONTENT_LENGTH'] = 3 * 1024 * 1024

ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}

def sql_query(query_type, data):
# 建立MySQL連線
    conn = mysql.connector.connect(
        host='localhost',       # 連線主機名稱
        user='root',            # 登入帳號
        password='',  # 登入密碼
        database='mosquitoDB'
    )

    cursor = conn.cursor()

    if query_type == 'insert':
        sql = "INSERT INTO upload_log (serial, shot_time, humidity, temperature, filename) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(sql, (data['serial'], data['date']+' '+data['time'], data['humidity'], data['temperature'], data['filename']))

    else:
        sql = "UPDATE upload_log SET vector_num = %s, nonvector_num = %s, proccess_time = %s WHERE serial = %s AND shot_time = %s"
        cursor.execute(sql, (data['vector'], data['nonvector'], data['detect_time'], data['serial'], data['datetime']))

    conn.commit()
    cursor.close()
    conn.close()


@app.route('/esp32cam', methods=['POST']) #當路由為esp32cam時(34.83.20.35/esp32cam)，會呼叫esp32cam()
def esp32cam():
    
    res = handle_file(request)  # 呼叫 handle_file()      

    if res['msg'] == 'ok':  
        filename = res['filename']
        #print(res) 有開啟putty時才能寫這段(因為如果沒開不知道要print到哪)
        sql_query('insert',res)

        result = execute_detect(os.path.join(app.config['UPLOAD_FOLDER'], filename))
        result_file_path = os.path.join(APP_PATH, 'mosquito_detection_result.txt')
        with open(result_file_path, 'w') as result_file:
            result_file.write(result.stdout)

        return res, result.stdout  # 立即返回成功訊息
    elif res['msg'] == 'experience':
        filename = res['filename']
        result = exp_detect(os.path.join(app.config['UPLOAD_FOLDER'], filename))
        pattern = r'\*{3}(.*?)\*{3}'
        matches = re.findall(pattern, result.stdout)
        #print(matches[0]) #matches[0] = experience_2024-05-13_23:55:24.jpg
        #return send_file(matches[0], mimetype='image/png')
        filename = matches[0]
        redirect_url = f'http://112mosquito.ddns.net:8000/112-2_topic/website/show_pic.php?filename={filename}'
        return redirect(redirect_url)
        #return redirect('http://112mosquito.ddns.net:8000/112-2_topic/website/show_pic.php?filename=')
        #return matches[0]
    else:
        return res['msg']  # 返回其他訊息


def handle_file(request):
    #這邊要去接編號、溫溼度，並將上數資料和系統時間變成檔名，因此這邊return的res應該為字典，內含上述資料
    #print(request.files)
    if 'filename' not in request.files:
        return {"msg": 'no_file'}  # 傳回代表「沒有檔案」的訊息
    
    file = request.files['filename']  # 取得上傳檔

    if file.filename == '':
        return {"msg": 'empty'}       # 傳回代表「空白」的訊息

    if file:
        file_type = filetype.guess_extension(file)  # 判斷上傳檔的類型
        if file_type in ALLOWED_EXTENSIONS:
            serial = request.form.get('number')
            gmt_8_time = datetime.now().astimezone(pytz.timezone('Asia/Taipei')) #設定GMT+8時區
            file.stream.seek(0)
            date = gmt_8_time.strftime('%Y-%m-%d')
            time = gmt_8_time.strftime('%H:%M:%S')
            filename = serial + '_' + date + '_' + time + '.' + file_type
            file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))

            if serial == 'experience':
                return {"msg": 'experience', "filename": filename}  # 傳回代表「單純回傳辨識照片給website(不存進DB)」的訊息
            else:
                humidity = request.form.get('humidity')
                temperature = request.form.get('temperature')
                # 傳回代表上傳成功的訊息以及檔名。
                return {"msg": 'ok', "filename": filename, "serial": serial, 
                        "humidity": humidity, "temperature": temperature, "date": date, "time":time}
        else:
            return {"msg": 'type_error'}  # 傳回代表「檔案類型錯誤」的訊息
def exp_detect(photo_path):
    absolute_path = '/var/www/html/112-2_topic/yolov7'
    os.chdir(absolute_path)  # 切換到 yolov7 資料夾
    weight_path = '/var/www/html/112-2_topic/best.pt'
    
    # 組合 command
    command = f'python exp_detect.py --source {photo_path} --weights {weight_path}'
    result = subprocess.run(command, shell=True, capture_output=True, text=True)
    # 記錄命令輸出
    logging.info(f'{photo_path} 的命令輸出：')
    logging.info(f'Stdout: {result.stdout}')
    logging.info(f'Stderr: {result.stderr}')
    return result

def execute_detect(photo_path):
    absolute_path = '/var/www/html/112-2_topic/yolov7'
    os.chdir(absolute_path)  # 切換到 yolov7 資料夾
    weight_path = '/var/www/html/112-2_topic/best.pt'
    
    # 組合 command
    command = f'python mosquito_detect.py --source {photo_path} --weights {weight_path}'

    # 使用 subprocess 執行 command，並捕獲輸出
    result = subprocess.run(command, shell=True, capture_output=True, text=True)
    
    # 記錄命令輸出
    logging.info(f'{photo_path} 的命令輸出：')
    logging.info(f'Stdout: {result.stdout}')
    logging.info(f'Stderr: {result.stderr}')
    
    # 將執行結果回傳到 esp32cam() 函式中
    return result

@app.route('/update_db', methods=['POST']) #將yolov7辨識結果儲存到phpmysql
def update_db():
    if request.method == 'POST':
        data = request.get_json()  # 獲取 POST 請求中的 JSON 數據 
        sql_query('update',data)
        return jsonify({'message': 'POST request received'}), 200

@app.route('/img/<filename>')
def display_image(filename):
    return redirect(url_for('static', filename='uploads/' + filename))

@app.route('/ws')
def index():
    return Response(get_image(), mimetype='multipart/x-mixed-replace; boundary=frame')


def get_image():
    while True:
        try:
            with open("image.jpg", "rb") as f:
                image_bytes = f.read()
            image = Image.open(BytesIO(image_bytes))
            img_io = BytesIO()
            image.save(img_io, 'JPEG')
            img_io.seek(0)
            img_bytes = img_io.read()
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + img_bytes + b'\r\n')

        except Exception as e:
            print("encountered an exception: ")
            print(e)

            with open("placeholder.jpg", "rb") as f:
                image_bytes = f.read()
            image = Image.open(BytesIO(image_bytes))
            img_io = BytesIO()
            image.save(img_io, 'JPEG')
            img_io.seek(0)
            img_bytes = img_io.read()
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + img_bytes + b'\r\n')
            continue


@app.route('/website')
def redirect_to_website():
    return redirect('http://112mosquito.ddns.net:8000/112-2_topic/website/home.php')

@app.route('/dengueQuery', methods=['POST'])
def dengueQuery():
    try:
        # 獲取 JSON 數據
        data = request.get_json()

        # 獲取變數
        town = ',' + data.get('town', '').strip() + ','
        village = data.get('village', '').strip()
        start_date = data.get('start_date', '').strip()
        end_date = data.get('end_date', '').strip()
        case_status = '發病日'

        # 構建參數字典
        params = {
            "casestatus": case_status,
            "datetimepicker1": start_date,
            "datetimepicker2": end_date,
            "town": town,
            "village": village
        }

        # 發送 GET 請求
        response = requests.get("https://khweb.geohealth.tw/php/getDetail.php", params=params)

        if response.status_code == 200:
            data_dict = response.json()  # 直接使用 response.json() 方法
            if "data" in data_dict and len(data_dict["data"]) > 0:
                data_list = data_dict["data"][0]

                result_dict = {
                    "town": data_list[0],
                    "village": data_list[1],
                    "indigenous": int(data_list[2]),
                    "foreign": int(data_list[3])
                }
                return jsonify(result_dict)
            else:
                return jsonify({"error": "No data available"}), 404
        else:
            return jsonify({"error": "Failed to fetch data", "status_code": response.status_code}), response.status_code
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(host='0.0.0.0', port=80)