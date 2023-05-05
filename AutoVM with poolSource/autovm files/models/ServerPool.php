<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;


class ServerPool extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
	 
    public function attributeLabels()
    {
        return [ // defining label column names
            'description' => Yii::t('app', 'Description Pool'),
            'servers' => Yii::t('app', 'Dedicated Servers'),
            'free_disk' => Yii::t('app', 'When the GB disk is low, automatically disable the physical server that owns the disk.'),
            'free_ram' => Yii::t('app', 'Automatically decommission the physical server that has only MB RAM remaining.'),
            'free_cpu' => Yii::t('app', 'Automatically decommission the physical server that has only MHZ CPU remaining.'),
        ];
    }
	 
	 
    public function rules()
    {
        return [
            [['description', 'servers'], 'required'], // desc and servers must not be empty (crashing the system:( ) 
            [['free_disk', 'free_ram', 'free_cpu'], 'integer'], // free_disk, free_ram, free_cpu values must be integer.
        ];
    }
	 
    public static function tableName()
    {
        return 'server_pool'; // defining table name of model.
    }
	
	public static function getServers($poolId = false){ // transfering server names from db. 
	// If we added servers before, it shouldn't show up in the lsit. 

		if($poolId == false){ // $poolId if it's false we are adding.
			$pools = ServerPool::find()->all(); // listing pools. 
		}
		else{
			$pools = ServerPool::find()->where(['!=', 'id', $poolId])->all(); // except $poolId we list all servers.
		}
		$poolArray = []; // server id transfering to blank array
		
		if($pools){ // if $pools var including data, we continue.
		
			foreach($pools as $pool){ // listing pools.
				$server_decode = json_decode($pool->servers); // servers col json so have to turn array.
				foreach($server_decode as $server_p){ // and taking array to loop.
					$poolArray[] = $server_p; // transfering ids to $poolArray.
				}
			}
		
		}
		
		$servers = Server::find()->all(); // listing all servers from db
		$returnArray = []; // it will return array
		foreach($servers as $server){ // adding all servers to loop
			if(!in_array($server->id, $poolArray)){ // if the id is not exist in $poolArray array, returning server.
				$returnArray[$server->id] = $server->name; // server id keyname written in the array.
			}
		}
		return $returnArray; // returning all available servers.
		
	}
		
    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['description', 'servers', 'free_ram', 'free_disk', 'free_cpu'], // defining table by scenarios() function.
        ];
    }
	

}
