<?php

namespace app\controllers;

use Yii;
use yii\web\Response;

class UserController extends ApiController
{
    /**
     * 获取已填写的第一个地址
     */
    public function actionGetfaddr()
    {
        $uid = Yii::$app->request->post('uid');
        $where = array(
            'uid' => $uid
        );
        $cond['where'] = $where;
        $cond['limit'] = '1';
        $data = $this->selectData('user_addr', $cond);
        return array('data'=>$data);
    }

    /**
     * 获取用户所有地址信息
     */
    public function actionGetaaddr()
    {
        $uid = Yii::$app->request->post('uid');
        $where = array(
            'uid' => $uid
        );
        $cond['where'] = $where;
        $data = $this->selectData('user_addr', $cond);
        return array('data'=>$data);
    }

    /**
     * 添加用户地址信息
     */
    public function actionSetaddr()
    {
        $uid = Yii::$app->request->post('uid');
        $lng = Yii::$app->request->post('lng');
        $lat = Yii::$app->request->post('lat');
        $pro_id = Yii::$app->request->post('pid');
        $city_id = Yii::$app->request->post('cid');
        $full_addr = Yii::$app->request->post('full_addr');
        if (empty($uid) || empty($pro_id) || empty($city_id) || empty($full_addr) ) {
            return array('data'=>'缺少必要参数');
        }
        $res = Yii::$app->db->createCommand()->insert('user_addr', [
            'uid' => $uid,
            'lng' => $lng,
            'lat' => $lat,
            'pro_id' => $pro_id,
            'city_id' => $city_id,
            'full_addr' => $full_addr
        ])->execute();

        $data = $res?'添加成功':'添加失败';
        return array('data'=>$data);
    }

    /**
     * 删除用户地址信息
     */
    public function actionDeladdr()
    {
        $adr_id = Yii::$app->request->post('adr_id');

        $res = Yii::$app->db->createCommand()->delete('user_addr', ['id' => $adr_id])->execute();

        $data = $res?'删除成功':'删除失败';
        return array('data'=>$data);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
