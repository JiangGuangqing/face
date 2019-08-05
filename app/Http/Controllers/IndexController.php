<?php

namespace App\Http\Controllers;

use AipFace;

class IndexController extends Controller
{

    const APP_ID = '16951142';
    const API_KEY = 'd45ebCQozLbh4l7XhDa5vl07';
    const SECRET_KEY = 'tOHKpERVcV6L7kosG8umDTvMYWzNQBGH';

    /*
     * 用户信息采集
     *  性别（男，女）
     *  年龄
     *  种族（白种人，黄种人，黑种人，阿拉伯人）
     *
     * 脸部信息采集
     *  眼镜（无眼镜，普通眼镜，太阳镜）
     *  情绪（愤怒，厌恶，恐惧，高兴，伤心，惊讶，无情绪）
     *  表情（不笑，微笑，大笑）
     *  美丑打分（数值越大越俊）
     * */
    public function detect()
    {
        $image = "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1564993899516&di=b6681c8cd3183311c8fe286e953d7aec&imgtype=0&src=http%3A%2F%2Fdingyue.ws.126.net%2F2019%2F04%2F03%2Fff20d9986bc6433d936107521d13add1.jpeg";

        $client = new AipFace(self::APP_ID, self::API_KEY, self::SECRET_KEY);

        $imageType = "URL";

        $options = array();
        $options["face_field"] = "age,beauty,expression,gender,glasses,race,emotion";
        $options["max_face_num"] = 1;
        $options["face_type"] = "LIVE";
        $options["liveness_control"] = "LOW";

        // 带参数调用人脸检测
        $res = $client->detect($image, $imageType, $options);

        return $res['result'];
    }

}
