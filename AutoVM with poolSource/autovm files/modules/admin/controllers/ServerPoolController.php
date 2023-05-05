<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

use stdClass;
use app\models\Log;
use app\models\Os;
use app\models\Ip;
use app\models\Server;
use app\models\ServerPool;
use app\models\Datastore;
use app\modules\admin\filters\OnlyAdminFilter;
use yii\data\ActiveDataProvider;


class ServerPoolController extends Controller
{
    public function behaviors()
    {
        return [
            OnlyAdminFilter::className(), // If there is admin enterence continue . If it's not, to login screen.
        ];
    }
	
    public function actionIndex()
    {
        $pools = ServerPool::find()->orderBy('id DESC'); // Listing data of serverpool. id DESC taking last data to top ordering.

        $dataProvider = new ActiveDataProvider([ // ActiveDataProvider class yii2 paging method. With this library $pool value server listing will be decided.
              'query' => $pools, // server pool list to this key
              'pagination' => [ // paging settings
                'pageSize' => 10, // how many data will be listed on a page.
              ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]); // The yii2 render function pulls the php file I entered from the views folder. The things I enter with an array after putting commas represent the variables that will be passed into the file I am calling..
    }
	
	public function actionCreate(){
		
        $model = new ServerPool; // I'm passing the ServerPool model to the $model variable. I call it with new because I will add a new record to the database.
		
        if ($model->load(Yii::$app->request->post()) && $model->validate()) { // I'm importing the values posted here into the model and I'm doing the controls in the rules function that I wrote in the model with validate().
			
			$datas = array(); // I am creating a new array to pass the values I will enter into the columns in the server_pool table..
			foreach($model->Attributes as $key => $value){ // I am listing the columns of the table with Attributes.
				if($key == "servers"){ // If the column name is servers, I am doing the json_encode operation. so I'm converting the array to json. I need to convert it to json because it cannot be recorded in the database as an array..
					$datas[$key] = json_encode($value);
				}
				else{
					$datas[$key] = $value; // If the column is not servers, I enter the column name as a key and transfer its value to the array..
				}
			}
			$model->Attributes = $datas; // then I transfer the values that I imported into $datas to the Attributes variable as new values.
			
            if ($model->save(false)) { // I print the values I have transferred to the database with the save() function.
                Yii::$app->session->addFlash('success', Yii::t('app', 'The new physical server pool has been successfully created.'));

                return $this->refresh(); // kayıt işlemi gerçekleştikten sonra sayfayı yeniletiyorum.
            }
        }

        return $this->render('create', compact('model'));
		
	}
	
	public function actionEdit($id){
		
        $model = ServerPool::findOne($id); // Düzenleme sayfasında server havuzunun id si ile veritabanında araştırma yapıyorum. Yeni bir değer olmadığı için new kullanmadan static olan findOne() fonksiyonu ile veritabanındaki değerleri çektiriyorum.
		
        if (!$model) { // veri tabanında böyle bir id yok ise  aşağıdaki hata sayfasını tarayıcıya yazdırıyorum.
            throw new NotFoundHttpException(Yii::t('app', 'Nothing Found'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) { // burada post edilen değerleri modelin içine aktarıyorum ve modelin içine yazmış olduğum rules fonksiyonu içindeki kontrolleri validate() ile yapıyorum.
			$servers = json_encode($model->servers); // seçtiğim serverlar array olarak geldiği için json'a çeviriyorum.
			$model->servers = $servers; // çevirdiğim jsonu servers sütununa aktarıyorum.
            if ($model->save(false)) { // save() fonksiyonu ile veriyi güncelliyorum.
                Yii::$app->session->addFlash('success', Yii::t('app', 'The physical server pool has been successfully edited.'));

                return $this->refresh(); // ref page after register.
            }
        }

        return $this->render('edit', compact('model'));
		
	}
	
	public function actionView($id){
		
        $pool = ServerPool::findOne($id); // On the display page, I search the database with the id of the server pool. Since there is no new value, I pull the values from the database with the findOne() function, which is static without using new..
		
        if (!$pool) { // If there is no such value in the database, I print the following error text to the browser.
            throw new NotFoundHttpException(Yii::t('app', 'Nothing Found'));
        }
		
		$servers = [];
		
		$poolServers = json_decode($pool->servers); // Since the data registered in the servers column is json, I am converting it to an array..
		
		foreach($poolServers as $poolServer){ // I transferred the server IDs in the servers column to the $poolServers variable, here I am listing the ids.
			$server = Server::findOne($poolServer); // bu server id numarasına ait veritabanında veriyi buluyorum.
			if($server){ // If there is data, it does the following operations.
				$servers[$server->id]['detail'] = $server; // sdetails transfer
				$servers[$server->id]['emptyip'] = 
					Ip::find()->leftJoin('vps_ip', 'vps_ip.ip_id = ip.id')
					->andWhere('vps_ip.id IS NULL')
					->andWhere(['ip.server_id' => [$server->id, $server->parent_id]])
					->isPublic()					
					->count(); // take ips count.
				$servers[$server->id]['datastores'] = Datastore::find()->where(['server_id' => $server->id])->all(); // add all datastore k to server.
			}
		}


        return $this->render('view', compact('pool', 'servers'));
		
	}
	
    public function actionDelete()
    {
        $data = Yii::$app->request->post('data'); // I get the value of the pools I have selected on the listing page.
        
        foreach ($data as $id) { // loop vlaues.
         
            $pool = ServerPool::find()->where(['id' => $id])->one(); // server havuzunun veritabanındaki değerlerini alıyorum
            
            if ($pool) { // I get the values of the server pool in the database.
                
                $deleted = $pool->delete(); // I'm deleting the server pool from the database.
                
                if ($deleted) {
                    Log::log(sprintf('%s pool has been deleted by %s!', $pool->description, Yii::$app->user->identity->fullName));   // Finally, I record the name of the deleted pool server and the user who did this operation in the log database..
                }
            }
        }

        return $this->redirect(Yii::$app->request->referrer); // I am being redirected back to the page from which I was redirected here.
    }

}
