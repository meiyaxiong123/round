<?php

namespace app\controllers;

use Yii;
use app\models\UploadForm;
use yii\web\UploadedFile;

class DishController extends ApiController
{
    /**
     * 菜品基本信息
     */
    public function actionAdddish()
    {
        $this->_init();
        $shop_id = Yii::$app->request->get('shop_id');
        $name = Yii::$app->request->get('name');
        $price = Yii::$app->request->get('price');
        $size = Yii::$app->request->get('size');
        $type = Yii::$app->request->get('type');
        $profile = Yii::$app->request->get('profile');

        $shop_id = '22';
        $name = '菜品1';
        $profile = '菜品1简介';
        $price = '99.99';
        $size = '1斤';
        $type = '1';//菜品
        if (empty($shop_id) || empty($name) || empty($price) || empty($type)) {
            return array('msg' => '缺少必要参数');
        }
        $res = Yii::$app->db->createCommand()->insert('dish', [
            'shop_id' => $shop_id,
            'name' => $name,
            'price' => $price,
            'size' => $size,
            'profile' => $profile,
            'type' => $type
        ])->execute();
        if ($res) {
            $dish_id = Yii::$app->db->getLastInsertID();
        }

        return array('data'=>'插入成功');

    }

    /**
     * 菜品原材料
     */
    public function actionEditmaterial()
    {
        $this->_init();
        $dish_id = Yii::$app->request->get('dish_id');
        $name = Yii::$app->request->get('name');
        $unit = Yii::$app->request->get('unit');
        $qty = Yii::$app->request->get('qty');
        $type = Yii::$app->request->get('type');
        $res = Yii::$app->db->createCommand()->insert('material', [
            'dish_id' => $dish_id,
            'mat_name' => $name,
            'unit' => $unit,
            'qty' => $qty,
            'type' => $type
        ])->execute();
        if ($res) {
            $mid = Yii::$app->db->getLastInsertID();
        }
        return array('data'=>'插入成功');
    }

    /**
     * 菜品图片
     */
    public function actionEditimg()
    {
        $dish_id = Yii::$app->request->post('dish_id');
        if(Yii::$app->request->isPost) {
            $model = new UploadForm();
            $model->file = UploadedFile::getInstances($model, 'file');
            $rootPath = 'assets/images/';
            $ds_img = array();
            if ($model->file && $model->validate()) {
                foreach ($model->file as $file) {
                    $base_url = $rootPath . $file->baseName . '.' . $file->extension;
                    $ds_img[] = [$dish_id, $base_url, 1];

                    $file->saveAs($rootPath . $file->baseName . '.' . $file->extension);
                }
            }
        } else {
            return ['code'=>0, 'message'=>'不是POST'];
        }
        $res = Yii::$app->db->createCommand()->batchInsert(
            'dish_img',
            [ 'dish_id','src','type'],
            $ds_img
        )->execute();

        if ($res) {
            $mid = Yii::$app->db->getLastInsertID();
        }
        return array('data'=>'插入成功');
    }
}
