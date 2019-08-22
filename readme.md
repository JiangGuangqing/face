## 基于百度 API 及人脸抓拍相机 采集展会人流量

使用百度群相人脸抓拍机，集成百度 AI 的 SDK，拍摄到人脸后将人脸图片传递到 API 接口;

API 接口接收到 base64 格式的图片先存放到本地，然后调用百度人脸识别 API 获取到人脸的基本参数（性别，年龄，人种）等;

使用 MySQL 存放人脸数据并做统计。

###  API 的功能
1，采集人脸数据   
2，展示人脸数据，用于展会现场的数据实时展示


### 部署

1,下载项目
> git clone https://github.com/JiangGuangqing/face.git

2,安装扩展
> composer install

3,构建数据库
> php artisan migrate

