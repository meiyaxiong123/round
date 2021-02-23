<?php

namespace app\controllers;

use Yii;

class ShopController extends ApiController
{
    /**
     * 商家基本信息(单个)
     */
    public function actionGetshopbase()
    {
        $this->_init();
        $shop_id = Yii::$app->request->get('sid');
        $where = array(
            'shop_id' => $shop_id
        );
        $cond = array(
            'field' => 'shop_id,name,logo',
            'where' => $where
        );

        $shop = $this->selectData('shop', $cond );
        //可提供的服务（桌椅、上门、搭棚、单厨）
        $cond = array(
            'where' => $where
        );
        $services = $this->selectData('service', $cond );
        $data = $shop[0];
        if (!empty($data))
        $data['service'] = $services;
        return array('data'=>$data);

    }

    /**
     * 商家列表
     */
    public function actionGetshoplist()
    {
        $lng = Yii::$app->request->post('lng');
        $lat = Yii::$app->request->post('lat');
        $cond = array(
            'field' => 'shop_id,name,logo,FLOOR(st_distance(point('.$lng.', '.$lat.'),point(lng, lat))/0.0111) as distance',
            'order' => 'distance asc'
        );
        $data = $this->selectData('shop', $cond );

        return array('data'=>$data);

    }

    /**
     * 客户下单页面详细信息
     */
    public function actionGetshopdetail()
    {
        $shop_id = Yii::$app->request->post('shop_id');
        $where = array(
            'shop_id' => $shop_id
        );
        $cond = array(
            'where' => $where
        );
        //商家信息
        $shop = $this->selectData('shop', $cond );
        //问答
        $qa = $this->selectData('qa', $cond );
        //评价
        $cond['limit'] = '10';
        $appraise = $this->selectData('appraise', $cond );

        $data = $shop[0];
        if (!empty($data)){
            $data['qa'] = $qa;
            $data['appraise'] = $appraise;
        }

        return array('data'=>$data);
    }

    /**
     * 添加商家问答
     */
    public function actionAddshopqa()
    {
        $shop_id = Yii::$app->request->post('shop_id');
        $qa_json = Yii::$app->request->post('qa');
        $qas = json_decode($qa_json,true);
        if(Yii::$app->request->isPost) {
            if (empty($qas) || empty($shop_id)) {
                return array('data'=>'缺少参数');
            }
            $data = array();
            foreach ($qas as $qa) {
                $data[] = [$shop_id, $qa['que'], $qa['ans']];
            }
            $res = Yii::$app->db->createCommand()->batchInsert(
                'qa',
                [ 'shop_id', 'question', 'answer'],
                $data
            )->execute();
            //商家信息
        }
        $data = $res?'添加成功':'添加失败';
        return array('data'=>$data);
    }

    /**
     * 获取商家问答
     */
    public function actionGetshopqa()
    {
        $shop_id = Yii::$app->request->post('shop_id');
        $where = array(
            'shop_id' => $shop_id
        );
        $cond = array(
            'where' => $where
        );
        //商家信息
        $data = $this->selectData('qa', $cond );

        return array('data'=>$data);
    }

    /**
     * 添加商家问答
     */
    public function actionDelshopqa()
    {
        $qa_id = Yii::$app->request->post('qa_id');
        $res = Yii::$app->db->createCommand()->delete('qa', ['id' => $qa_id])->execute();
        $data = $res?'删除成功':'删除失败';
        return array('data'=>$data);
    }

    /**
     * 获取商家商品信息
     */
    public function actionGetshopgoods()
    {
        $shop_id = Yii::$app->request->post('shop_id');
        $where = array(
            'shop_id' => $shop_id
        );
        $cond = array(
            'where' => $where
        );
        //信息
        $dishes = $this->selectData('dish', $cond );
        $food = array();//菜品
        $dr = array();//酒水
        $tc = array();//桌椅
        $service = array();//服务
        $grouds = array();//场地
        $fixup = array();//布置
        $trans = array();//运输
        foreach ($dishes as $ds) {
            if ($ds['type'] == 1) $food[] = $ds;
            if ($ds['type'] == 2) $dr[] = $ds;
            if ($ds['type'] == 3) $tc[] = $ds;
            if ($ds['type'] == 4) $service[] = $ds;
            if ($ds['type'] == 5) $grouds[] = $ds;
            if ($ds['type'] == 6) $fixup[] = $ds;
            if ($ds['type'] == 7) $trans[] = $ds;
        }

        $data = array(
            'dishes'   => $food,
            'drink'    => $dr,
            'table'    => $tc,
            'service'  => $service,
            'groud'  => $grouds,
            'fixup'  => $fixup,
            'trans'  => $trans,
        );
        return array('data'=>$data);
    }
}
