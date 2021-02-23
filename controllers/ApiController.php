<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    //bind params
    protected $prepare = array();
    protected $openid  = '';//用户openid
    protected $template_id  = '';//用户openid
    protected $topcolor  = '';//模板内容字体颜色，不填默认为黑色
    private static $appid  = '';//开发者ID(AppID)
    private static $appsecret  = '';//开发者密码(AppSecret)

    /**
     * {@inheritdoc}
     */
    protected function _init()
    {
//        Yii::$app->response->format=Response::FORMAT_JSON;
    }

    public function selectData($table,$cond) {
        $this->prepare = array();
        $where    = empty($cond['where'])?array():$cond['where'];
        $fieldStr = empty($cond['field'])?'*':$cond['field'];
        $join     = empty($cond['join'])?'':$cond['join'];
        $limit    = empty($cond['limit'])?'':$cond['limit'];
        $order    = empty($cond['order'])?'':$cond['order'];
        $group    = empty($cond['group'])?'':$cond['group'];

        //where
        $whereStr = $this->parseWhere($where);

        //join
        $joinStr = '';
        if(!empty($join)) {
            $joinStr    =   ' '.implode(' ',$join).' ';
        }
        $groupStr = empty($group)?'':' GROUP BY '.$group.' ';
        $orderStr = empty($order)?'':' ORDER BY '.$order.' ';
        //limit
        $limitStr = empty($limit)?'':' LIMIT '.$limit.' ';
        $sql = 'SELECT '.$fieldStr.' FROM '.$table.' a '. $joinStr .$whereStr.$groupStr.$orderStr.$limitStr;

        $re = Yii::$app->db->createCommand($sql);
        foreach ($this->prepare as $k=> $v) {
            $re = $re->bindValue($k,$v);
        }
        return $re->queryAll();
    }

//    public function save($where, $data) {
//        if (empty($this->_table)) {
//            echo 'error:table can not find';
//            die;
//        }
//        $this->prepare = array();
//
//        $updateStr = $this->parseSet($data);
//        //where
//        $whereStr = $this->parseWhere($where);
//        $sql = 'UPDATE '.$this->_table.' '.$updateStr  .$whereStr;
//        return $this->db->update($sql,$this->prepare);
//    }

    public function parseWhere($where) {

        $whereStr = '';
        //$prepare = array();
        // 对数组查询条件进行字段类型检查
        if (!empty($where)) {
            if(is_string($where)) {
                // 直接使用字符串条件
                $whereStr = $where;
            }else{
                // 默认进行 AND 运算
                $operate    =   ' AND ';
                foreach ($where as $key=>$val){
                    // 多条件支持
                    $key    = trim($key);
                    //对字符串类型字段采用模糊匹配
                    if(is_array($val)) {
                        $exp	=	strtolower($val[0]);
                        if(preg_match('/^(eq|neq|gt|egt|lt|elt|<|>)$/',$exp)) { // 比较运算
                            $c = count($this->prepare);
                            $k = $c+1;
                            $whereStr .= $key.' '.$exp.' :k_'.$k;
                            $this->prepare[':k_'.$k] = $val[1];
                        }elseif(preg_match('/^(notlike|like)$/',$exp)){// 模糊查找
                            $c = count($this->prepare);
                            $k = $c+1;
                            $whereStr .= $key.' '.$exp.' :k_'.$k;
                            $this->prepare[':k_'.$k] = $val[1];
                        }elseif('exp' == $exp ){ // 使用表达式
                            $whereStr .= $key.' '.$val[1];
                        }elseif(preg_match('/^(notin|not in|in)$/',$exp)){ // IN 运算
                            if(is_string($val[1])) {
                                $val[1] =  explode(',',$val[1]);
                            }

                            foreach ($val[1] as &$s) {
                                $s = '\''.$s.'\'';
                            }
                            $zone      =   implode(',',$val[1]);
                            $whereStr .= $key.' '.$exp.' ('.$zone.')';

                        }elseif(preg_match('/^(notbetween|not between|between)$/',$exp)){ // BETWEEN运算
                            $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                            $c = count($this->prepare);
                            $k = $c+1;
                            $whereStr .=  $key.' '.$exp.' :k_'.$k.' AND :k_'.($k+1).' ';
                            $this->prepare[':k_'.$k] = $data[0];
                            $this->prepare[':k_'.($k+1)] = $data[1];
                        }else{
                            echo 'error ',$val[0];
                        }
                    } else {
                        $c = count($this->prepare);
                        $k = $c+1;
                        $whereStr .= $key.' =  :k_'.$k;
                        $this->prepare[':k_'.$k] = $val;
                    }
                    $whereStr .= $operate;

                }
                $whereStr = substr($whereStr,0,-strlen($operate));
            }
            $whereStr = ' WHERE '.$whereStr;
        }
        return $whereStr;
    }

    public function parseSet($data) {
        foreach ($data as $key=>$val){
            if(is_null($val)){
                $set[]  =  $key.'=NULL';
            }elseif(is_scalar($val)) {// 过滤非标量数据
                $set[]  =   $key.'=?';
                $this->prepare[] = $val;
            }
        }
        return ' SET '.implode(',',$set);
    }

    /**
      * pushMessage 发送自定义的模板消息
      * @param  array  $data          模板数据
         $data = [
             'openid' => '', 用户openid
            'url' => '', 跳转链接
            'template_id' => '', 模板id
            'data' => [ // 消息模板数据
                'first'    => ['value' => urlencode('黄旭辉'),'color' => "#743A3A"],
                'keyword1' => ['value' => urlencode('男'),'color'=>'blue'],
                'keyword2' => ['value' => urlencode('1993-10-23'),'color' => 'blue'],
                'remark'   => ['value' => urlencode('我的模板'),'color' => '#743A3A']
            ]
        ];
      * @param  string $topcolor 模板内容字体颜色，不填默认为黑色
      * @return array
      */
    public function sendData($data)
    {
        $template = [
            'touser'      => $this->openid,
            'template_id' => $this->template_id,
            'url'         => $data['url'],
            'topcolor'    => $this->topcolor,
            'data'        => $data['data']
        ];
        $json_template = json_encode($template);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . self::getToken();
        $data = urldecode($json_template);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!empty($data)){
             curl_setopt($curl, CURLOPT_POST, 1);
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $resultData = json_decode($output, true);
        return $resultData;
    }

    // 获取TOKEN
    public static function getToken(){
        $urla = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . self::$appid . "&secret=" . self::$appsecret;
        $outputa = self::curlGet($urla);
        $result = json_decode($outputa, true);
        return $result['access_token'];
   }

    private static function curlGet($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if(curl_errno($curl)){
            return 'ERROR ' . curl_error($curl);
        }
        curl_close($curl);
        return $output;
    }
}
