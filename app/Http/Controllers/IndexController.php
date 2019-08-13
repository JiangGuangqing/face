<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use AipFace;
use App\Models\Face;
use App\Models\User;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    const APP_ID = '16980453';
    const API_KEY = 'bpxgFGqq210OFPpRD08mvkZz';
    const SECRET_KEY = 'APpoxD0QTF5Wm4VoPAFN6hBeHg30Gu4F';

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
    public function detect(Request $request)
    {
        $data = $request->all();
        Log::info(json_encode($data));
        $image = $data['faces'][0]['face_img'];

        $imageType = "BASE64";

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
            if ($score < 60) {
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
        $options["face_type"] = "CERT";
        $options["liveness_control"] = "NONE";

        // 带参数调用人脸检测
        $res = $client->detect($image, $imageType, $options);
        Log::info($searchResult);
        Log::info($res);

        //存储人脸照片

        $fileName = time() . rand(100000, 999999) . '.jpg';
        Log::info($fileName);

        // 设置图片本地保存路劲
        $path = "../resources/upload";

        $imageSrc = $path . "/" . $fileName; //图片名字

        file_put_contents($imageSrc, base64_decode($image));//返回的是字节数


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
                'img' => $fileName,
                'glasses' => $glasses,
                'emotion' => $emotion,
                'expression' => $expression,
                'beauty' => $res['result']['face_list'][0]['beauty']
            ]);
        }
        return [];

    }

    /*
     * 心跳数据
     * */
    public function heart(Request $request)
    {
        return [];
    }


    /*
     * 数据展示
     * */
    public function show()
    {
        $faceNum = Face::count();
        $userNum = User::count();
        $today = date('Y-m-d');
        $data['visitTimesTotal'] = $faceNum;
        $data['visitTimesToday'] = Face::where('created_at','like',"$today%")->count();
        $data['revisitRate'] = round($userNum/$faceNum*100);
        $data['currTime'] = date('Y年m月d日 H:i');

        $maleNum = User::where('gender',1)->count();
        $femaleNum = User::where('gender',2)->count();
        $data['sex'][0]['field'] = 'male';
        $data['sex'][0]['value'] = round($maleNum/$userNum*100);
        $data['sex'][1]['field'] = 'female';
        $data['sex'][1]['value'] = 100-round($maleNum/$userNum*100);

        $childNum = User::where('age','<','18')->count();
        $oldNum = User::where('age','>=','55')->count();
        $data['age'][0]['field'] = 'child';
        $data['age'][1]['field'] = 'young';
        $data['age'][2]['field'] = 'old';
        $data['age'][0]['value'] = round($childNum/$userNum*100);
        $data['age'][2]['value'] = round($oldNum/$userNum*100);
        $data['age'][1]['value'] = 100-round($childNum/$userNum*100)-round($oldNum/$userNum*100);
        return $data;

    }
}
