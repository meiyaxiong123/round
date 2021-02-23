<?php

namespace app\controllers;

use Yii;

class OrderController extends ApiController
{
    /**
     * 添加订单
     */
    public function actionPlaceorder()
    {
        $uid = Yii::$app->request->post('uid');
        $shop_id = Yii::$app->request->post('shop_id');
        $party_time = Yii::$app->request->post('party_time');//服务时间
        $cnote = Yii::$app->request->post('cnote');
        $mqty = Yii::$app->request->post('mqty');
        $aqty = Yii::$app->request->post('aqty');
        $dishes_json = Yii::$app->request->post('dishes');
        if (empty($uid) || empty($shop_id) || empty($party_time) || (empty($mqty) && empty($aqty) ) || empty($dishes_json)) {
            return array('msg' => '缺少必要参数');
        }
        $res = Yii::$app->db->createCommand()->insert('order_basic', [
            'status' => '0',//待接单
            'shop_id' => $shop_id,//商家id
            'uid' => $uid,
            'party_time' => $party_time,
            'cnote' => $cnote,
            'mqty' => $mqty,
            'aqty' => $aqty
        ])->execute();
        if ($res) {
            $order_id = Yii::$app->db->getLastInsertID();
        }

        $dishes = json_decode($dishes_json,true);
        foreach ($dishes as $ds) {
            $ds_data[] = array($order_id,$ds['dish_id'], $ds['qty']);
        }
        $transition = Yii::$app->db->beginTransaction();
        try {
            $res = Yii::$app->db->createCommand()->batchInsert(
                'order_dish',
                [ 'order_id','dish_id', 'qty'],
                $ds_data
            )->execute();
            if ($res) {
                $transition->commit();
            }else {
                $transition->rollBack();
            }

        }catch (\Exception $e) {
            $transition->rollBack();
            Yii::info($e->getMessage(), 'api');
        }

        $data = array(
            'url' => '',
            'data' => array(
                'first'    => ['value' => urlencode('黄旭辉'),'color' => "#743A3A"],
                'keyword1' => ['value' => urlencode('男'),'color'=>'blue'],
                'keyword2' => ['value' => urlencode('1993-10-23'),'color' => 'blue'],
                'remark'   => ['value' => urlencode('我的模板'),'color' => '#743A3A']
            )
        );
        //公众号推送信息
        $this->sendData($data);

        return array('data'=>'插入成功');

    }

    /**
     * 订单信息
     * status 1:待接单 2：沟通中 3：已完成 4：已取消
     */
    public function actionGetorder()
    {
        $order_id = Yii::$app->request->post('order_id');
        $status = Yii::$app->request->post('status');
        $sta_sql = '';
        switch ($status) {
            case 1:
                $sta_sql = 'a.status=0';
                break;
            case 2:
                $sta_sql = 'a.status in (1,2)';
                break;
            case 3:
                $sta_sql = 'a.status =3';
                break;
            case 4:
                $sta_sql = 'a.status =4';
                break;
        }
        $where = array(
            'a.order_id' => $order_id
        );

        $query = new \yii\db\Query();
        $data =  $query
            ->select('a.*,b.name')
            ->from('order_basic a')
            ->join('left join','shop b', 'a.shop_id=b.shop_id')
            ->where($where)
            ->andWhere($sta_sql)
            ->all();
        //菜单信息
        return array('data'=>$data);
    }

    /**
     * 菜单信息
     */
    public function actionGetcaidan()
    {
        $this->_init();
        $order_id = Yii::$app->request->post('order_id');

        $data =  ( new \yii\db\Query())
            ->select('a.*,c.name as dish_name,b.mqty,b.aqty')
            ->from('order_dish a')
            ->join('left join','order_basic b', 'a.order_id=b.order_id')
            ->join('left join','dish c', 'a.dish_id=c.dish_id')
//            ->where('a.order_id=:order_id',array(':order_id' => $order_id))
            ->where(array('a.order_id' => $order_id,'c.type' => '1'))
            ->all();
        return array('data'=>$data);
    }


    /**
     * 采购单信息
     *
     */
    public function actionGetpurchase()
    {
        $order_id = Yii::$app->request->post('order_id');

        $sql = 'SELECT mat_name,unit,sum(tqty) as qty from ( '.
            ' select c.mid,b.mqty,b.aqty,c.mat_name,c.unit, ( c.qty * a.qty ) AS tqty '.
            ' from order_dish a '.
            ' LEFT JOIN order_basic b on a.order_id=b.order_id '.
            ' LEFT JOIN material c ON a.dish_id = c.dish_id  '.
            ' LEFT JOIN dish d ON a.dish_id = d.dish_id  '.
            ' where a.order_id=:order_id and d.type=1'.
            ' ) aa group by mid ';

        $re = Yii::$app->db->createCommand($sql);
        $re ->bindParam(':order_id', $order_id);
        $data = $re->queryAll();

        return array('data'=>$data);
    }

    /**
     * 取消订单
     *
     */
    public function actionCancelorder()
    {
        $order_id = Yii::$app->request->post('order_id');

        $sql = 'update order_basic set status = 5 where order_id = :order_id ';
        $query = Yii::$app->db->createCommand($sql);
        $query ->bindParam(':order_id', $order_id);
        $re = $query->query();
        $data = $re?'取消成功':'取消失败';
        return array('data'=>$data);
    }
}
