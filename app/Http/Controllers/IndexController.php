<?php

namespace App\Http\Controllers;

use AipFace;
use App\Models\Face;
use App\Models\User;

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
        $image = "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1565062568959&di=3f5e1a4d2f987601375ae9ebdddff1eb&imgtype=0&src=http%3A%2F%2Fimages.china.cn%2Fattachement%2Fjpg%2Fsite1000%2F20101213%2F002564bb1f430e6faa7553.jpg";

        $imageType = "URL";

        $groupId = "8_21";

        $client = new AipFace(self::APP_ID, self::API_KEY, self::SECRET_KEY);

        //跟现有的人脸库进行比对
        $searchResult = $client->search($image, $imageType, $groupId);

        if ($searchResult['error_code'] == '222207') {
            //未检测到人脸注册人脸
            $userId = uniqid();
            $client->addUser($image, $imageType, $groupId, $userId);
        } else {
            //匹配度85% 一下的算新用户注册人脸
            $score = (int)$searchResult['result']['user_list'][0]['score'];
            if ($score < 85) {
                $userId = uniqid();
                $client->addUser($image, $imageType, $groupId, $userId);
            } else {
                //匹配用户获取 userId
                $userId = $searchResult['result']['user_list'][0]['user_id'];
            }
        };

        $options = array();
        $options["face_field"] = "age,beauty,expression,gender,glasses,race,emotion";
        $options["max_face_num"] = 1;
        $options["face_type"] = "LIVE";
        $options["liveness_control"] = "LOW";

        // 带参数调用人脸检测
        $res = $client->detect($image, $imageType, $options);

        if ($res['error_code'] == 0) {

            if ($res['result']['face_list'][0]['gender']['type'] == 'male') {
                $gender = 1;
            } else {
                $gender = 2;
            }

            $race = '';
            switch ($res['result']['face_list'][0]['race']['type']) {
                case 'yellow':
                    $race = 1;
                    break;
                case 'white':
                    $race = 2;
                    break;
                case 'black':
                    $race = 3;
                    break;
                case 'arabs':
                    $race = 4;
                    break;
            }
            //记录用户
            User::firstOrCreate(
                ['user_id' => $userId],
                [
                    'age' => $res['result']['face_list'][0]['age'],
                    'gender' => $gender,
                    'race' => $race
                ]
            );

            $glasses = '';
            switch ($res['result']['face_list'][0]['glasses']['type']) {
                case 'none':
                    $glasses = 1;
                    break;
                case 'common':
                    $glasses = 2;
                    break;
                case 'sun':
                    $glasses = 3;
                    break;
            }

            $emotion = '';
            switch ($res['result']['face_list'][0]['emotion']['type']) {
                case 'angry':
                    $emotion = 1;
                    break;
                case 'disgust':
                    $emotion = 2;
                    break;
                case 'fear':
                    $emotion = 3;
                    break;
                case 'happy':
                    $emotion = 4;
                    break;
                case 'sad':
                    $emotion = 5;
                    break;
                case 'surprise':
                    $emotion = 6;
                    break;
                case 'neutral':
                    $emotion = 7;
                    break;
            }

            $expression = '';
            switch ($res['result']['face_list'][0]['expression']['type']) {
                case 'none':
                    $expression = 1;
                    break;
                case 'smile':
                    $expression = 2;
                    break;
                case 'laugh':
                    $expression = 3;
                    break;
            }

            //记录用户脸部信息
            Face::create([
                'user_id' => $userId,
                'face_token' => $res['result']['face_list'][0]['face_token'],
                'img' => $image,
                'glasses' => $glasses,
                'emotion' => $emotion,
                'expression' => $expression,
                'beauty' => $res['result']['face_list'][0]['beauty']
            ]);
        }
        return 'SUCCESS';

    }

}
