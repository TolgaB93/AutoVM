<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use app\models\Vps;
use app\models\VpsAction;
use app\models\Server;
use app\models\Bandwidth;
use app\extensions\Api;
use app\models\ServerPool;
use app\models\Datastore;


class CronController extends Controller
{
    public function actionIndex()
    {
        $this->actionStatus();
        $this->actionBandwidth();
    }
	
	public function actionServerPool(){
		$pool_list = ServerPool::find()->all(); // Transfer pool as array.
		foreach($pool_list as $pool){ // List server pools
			$servers = json_decode($pool->servers); // server_pool json to array.
			if(is_array($servers)){ // is it includes array? control
				if(count($servers) > 0){ // if it has more than one server control it
					foreach($servers as $serverId){ // There are server id values in the servers column, I loop them with foreach and get the server ids
						$server = Server::findOne($serverId); // I get the details of the server with the server id
							
						$data = ['server' => $server->getAttributes()]; // Since the captured server details come with an object, I get it as an array with getAttributes()

						$api = new Api($data); // coonnect Api Library

						$result = $api->request(Api::ACTION_SERVER_POOL); //Python file is executed and returned values are passed to result variable
							
						if($result != false){
							$availableCpu = ($result->cpu->size * $result->cpu->core) - ($result->cpu->usage); // available cpu
								
							$availableRam = intval($result->memory->size / 1048576) - $result->memory->usage; // available ram
								
							$server->cpu_free = $availableCpu;
							$server->ram_free = $availableRam;
							$server->save(false); // I print the values returned from python to whatever id is in the server database.
								
							foreach($result->storages as $storage){ // I'm listing disks returning from python.
								$storageCapacity = $storage->capacity; // server disk capacity
								$freeStorage = $storage->free; // server disk free space
									
								$storage = Datastore::find()->where(['server_id' => $serverId, 'uuid' => $storage->hash])->one(); // I'm checking if the values returned from python exist in the database
								if($storage){ // If the disk is found, I do the following operations.
									$storage->space = $storageCapacity / 1073741824; // 1073741824 partition operation to save the value of the disk in GB
									$storage->free = $freeStorage / 1073741824; // 1073741824 partition operation to save the value of the disk in GB
									$storage->save(false); // print returned data datastore db.
								}
									
							}
						}

					}
				}
			}
		}
	}

    public function actionStatus()
    {
        set_time_limit(3600);

        $servers = Server::find()->all();

        foreach ($servers as $server) {

            $data = ['server' => $server->attributes];

            $api = new Api;
            $api->setUrl(Yii::$app->setting->api_url);
            $api->setData($data);

            $result = $api->request(Api::ACTION_ALL);

            if (!$result) {
                continue; // There is nothing
            }
            
            $process = Vps::find()->owner($server->id);

            foreach ($process->batch(10) as $machines) {

                foreach ($machines as $machine) {

                    $address = $machine->ip->ip;

                    $available = stripos($result, $address);

                    if ($available) {
                        $machine->power = Vps::ONLINE;
                    }

                    if (!$available) {
                        $machine->power = Vps::OFFLINE;
                    }

                    $machine->save(false);
                }
            }
        }
    }

    public function actionReset()
    {
        set_time_limit(3600);

        require Yii::getAlias('@app/extensions/jdf.php');

        $now = [ date('j') ];

        $n = date('n');

        if ($n == 2 && date('j') == 28) {
            $now = [28, 29, 30, 31];
        }

        $times = implode(',', $now);

        $virtualServers = Vps::find()->where("reset_at IN ($times)")->all();

        foreach ($virtualServers as $vps) {

            $vps->notify_at = null;
            $vps->save(false);
            
            $a = Bandwidth::find()->where(['vps_id' => $vps->id])->orderBy('id DESC')->limit(1)->one();
            $b = Bandwidth::find()->where(['vps_id' => $vps->id])->orderby('id DESC')->limit(1)->offset(1)->one();

            if ($a && $b) {
                $a->used = $a->pure_used = 0;
                $a->save(false);

                $b->used = $b->pure_used = 0;
                $b->save(false);
            }
        }
    }
    
    protected function notify($machine)
    {
        list($ip, $user, $email) = [$machine->ip, $machine->user, $machine->user->email];
        
        $params = ['ip' => $ip->ip];
        
        $subject = Yii::t('app', 'Bandwidth');
        $message = Yii::t('app', 'Dear customer. Your virtual server {ip} bandwidth is too low and it can get suspended as soon as the bandwidth usage reached.', $params);
        
        Yii::$app->mailer->compose()
            ->setSubject($subject)
            ->setTextBody($message)
            ->setTo($email->email)
            ->send();
        
        return true;
    }

    public function actionBandwidth()
    {
        @ob_start();
        
        set_time_limit(3600);

        $servers = Server::find()->all();

        foreach ($servers as $server) {

            $data = ['server' => $server->attributes];

            $api = new Api;
            $api->setUrl(Yii::$app->setting->api_url);
            $api->setData($data);

            $result = $api->request(Api::ACTION_BANDWIDTH);

            if (empty($result)) {
                continue; // There is nothing
            }
            
            $result = (array) $result;

            $process = Vps::find()->owner($server->id);

            foreach ($process->batch(10) as $machines) {

                foreach ($machines as $machine) {

                    $address = $machine->ip->ip;

                    /*if ($address <> '51.68.177.135') {
                        continue;
                    }*/
                    
                    $available = array_key_exists($address, $result);

                    if (!$available) {
                        continue; // There is nothing
                    }

                    $usedBandwidth = $result[$address];

                    $plan = $machine->plan;

                    if ($plan) {
                        $bandwidth = $plan->band_width;
                    } else {
                        $bandwidth = $machine->vps_band_width;
                    }

                    if ($machine->vps_band_width) {
                        $bandwidth = $machine->vps_band_width;
                    }

                    // Extra bandwidth
                    $bandwidth += $machine->extra_bw;

                    $baseQuery = Bandwidth::find()->owner($machine->id)->descending();

                    $query = clone $baseQuery;

                    $old = $query->one();

                    if ($old) {
                        $old = $old->pure_used;
                    }

                    if (!$old) {
                        $old = 0;
                    }

                    $query = clone $baseQuery;

                    $older = $query->offset(1)->one();

                    if ($older) {
                        $older = $older->pure_used;
                    }

                    if (!$older) {
                        $older = 0;
                    }

                    $query = clone $baseQuery;

                    $total = $query->one();

                    if ($total) {
                        $total = $total->used;
                    }

                    if (!$total) {
                        $total = 0;
                    }

                    $query = clone $baseQuery;

                    $active = $query->active()->one();

                    if ($active) {
                        $active = $active->used;
                    }

                    if (!$active) {
                        $active = 0;
                    }

                    $new = ceil($usedBandwidth / 1024 / 1024);

                    $newTotal = 0;
                    
                    if (!$old) {
                        $newTotal = 0;
                    }

                    if (!$older) {
                        $newTotal = 0;
                    }

                    if ($new == $old) {
                        continue;
                    }

                    if ($older > $old && $old > $new) {
                        $newTotal = ($total - ($old + $older)) + $new;
                    }

                    if ($older < $old && $old < $new) {
                        $newTotal = ($total - $old) + $new;
                    }

                    if ($older > $old && $old < $new) {
                        $newTotal = ($new - $old) + $total;
                    }

                    if ($older < $old && $old > $new) {
                        $newTotal = $total + $new;
                    }

                    $record = new Bandwidth;

                    $record->vps_id = $machine->id;
                    $record->used = $newTotal;
                    $record->pure_used = $new;

                    $record->save(false);

                    if ($bandwidth <= 0) {
                        continue; // There is nothing
                    }

                    $allowed = ($bandwidth * 1024);
                    
                    // Notify
                    $diff = ($allowed - $active);
                    
                    if ($diff <= 1024) {
                     
                        if (!$machine->notify_at) {
                            
                            $this->notify($machine);
                            
                            $machine->notify_at = date('U');
                            $machine->save(false);
                        }
                    }

                    if ($allowed > $active) {
                        continue; // There is nothing
                    }


                    $data = ['server' => $machine->server->attributes, 'vps' => $machine->attributes, 'ip' => $machine->ip->attributes];

                    $api->setData($data);

                    $result = $api->request(Api::ACTION_SUSPEND);

                    if (!$result) {
                        continue; // There is nothing
                    }

                    $machine->status = Vps::STATUS_INACTIVE;
                    $machine->save(false);

                    $action = new VpsAction;

                    $action->vps_id = $machine->id;
                    $action->action = VpsAction::ACTION_SUSPEND;
                    $action->description = 'bandwidth';

                    $action->save(false);
                }
            }
        }
        
        @flush();
        @ob_flush();
    }
}
