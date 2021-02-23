<?php

namespace app\controllers;

use Yii;

class AddrController extends ApiController
{
    /**
     * 获取省信息
     */
    public function actionGetpro()
    {
        //$data = $this->selectData('province',array() );
        $data = array(
            'pro_id' => '440000',
            'province_name' => '广东省',
            'mini_name' => '粤'
        );
        return array('data'=>$data);

    }

    /**
     * 由省获取市信息
     */
    public function actionGetcitybypro()
    {
        $pro_id = Yii::$app->request->post('pro_id');
        if (empty($pro_id)) $pro_id = 440000;
        $where = array(
            'pro_id' => $pro_id
        );
        $cond = array(
            'where' => $where
        );
        $data = $this->selectData('city',$cond );
        return array('data'=>$data);
    }

    public function actionGetaddr()
    {
        $uid = Yii::$app->request->post('uid');
        $where = array(
            'uid' => $uid
        );
        $cond = array(
            'field' => 'uid,lng,lat,pro_id,city_id,full_addr',
            'where' => $where,
            'limit' => '1'
        );
        $uaddrs = $this->selectData('user_addr',$cond );
        if (empty($uaddrs)){
            $default = array(
                'pro_id' => '440000'
                //'province_name' => '广东省',
                //'mini_name' => '粤'
            );
            return $default;
        } else {
            return $uaddrs[0];
        }
    }
}
