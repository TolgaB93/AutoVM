<?php

namespace app\modules\api\controllers;
use app\models\Server;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use app\models\Ip;
use app\models\ServerPool;
use app\models\Plan;
use app\modules\api\filters\Auth;
use app\modules\api\components\Status;
use app\helpers\Pool;


class ServerController extends Controller
{
    public function behaviors()
    {
        return [
            Auth::className(),
        ];
    }
    
    public function actionIp()
    {
					
	if ($poolId = Yii::$app->request->post('poolId')){
		
		$diskSize = 50;
		
		if ($planId = Yii::$app->request->post('planId')){ // whms tarafından planId post edilmiş ise aşağıdaki kodları çalıştır.
			$plan = Plan::findOne($planId);
			if($plan){
				$planObj = $plan;
			}
		}
		
		if(isset($planObj)){
			$diskSize = $planObj->hard;
		}
		else{
			if (Yii::$app->request->post('vpsHard')) {
				$diskSize = Yii::$app->request->post('vpsHard');
			}
		}
		
		$bestPoolClass = new Pool();
		$bestPoolClass->pool_id = $poolId;
		
		$bestPool = $bestPoolClass->findBestServer($diskSize, 1);
		if($bestPool != false){
			$server = Server::findOne($bestPool['serverId']);
			$serverList = [];
			
			if($server){
				$serverList[] = $server->id;
			}
			
			if ($server && $server->parent_id) {
				$serverList[] = $server->parent_id;
			}
			
		}
		else{
			return ['ok' => false];
		}
	}
	else{
		$serverIds = Yii::$app->request->post('serverId');
		
		$serverIds = explode(',', $serverIds);
			
		$serverList = [];

		foreach ($serverIds as $id) {
			$server = Server::find()->where(['id' => $id])->one();

			if ($server) {
				$serverList[] = $server->id;
			}

			if ($server && $server->parent_id) {
				$serverList[] = $server->parent_id;
			}
		}
	}


        $ips = Ip::find()->leftJoin('vps_ip', 'vps_ip.ip_id = ip.id')
                ->andWhere('vps_ip.id IS NULL')
                ->andWhere(['ip.server_id' => $serverList])
                ->orderBy('ip.id ASC')
                ->isPublic()
                ->all();

        //$ips = Ip::find()->where(['server_id' => $serverId])->all();
        
        return [
            'ok' => true, 
            'ips' => ArrayHelper::map($ips, 'id', 'ip'),
        ];
    }
}
