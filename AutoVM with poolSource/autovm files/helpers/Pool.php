<?php

namespace app\helpers;

use app\models\ServerPool;
use app\models\Server;
use app\models\Datastore;
use app\models\Ip;


class Pool{
	
	public $pool_id;
		
	public function findBestServer($diskSize = 1, $ipCount = 1){
		$pool = ServerPool::findOne($this->pool_id); // I find the pool from the database with the entered pool_id value
		if($pool){ // If there is a pool, I do the following operations.
			$serverAr = []; // I will register the server's data to this array. The variable is defined below, data will be entered into this array.
			$servers = json_decode($pool->servers); // The data in the servers column is transferred to array because it is json.
			foreach($servers as $serverId){ // The server ids transferred to the array in the servers column are listed
				$server = Server::findOne($serverId); // Transferred to server $server variable in database with server id
				if($server){ // if the server exists in the database, continue with the following operations
					$datastores = Datastore::find()->where(['server_id' => $server->id, 'is_public' => 1])->all(); // I transferred the datastores in the database of the server to the $datastores variable as an object. is_public = Is it public?
					foreach($datastores as $datastore){ // I listed the disks I transferred to $datastores
						$serverAr[$server->id]['datastore'][$datastore->id] =  $datastore->free; // I transferred the free space of the disk to the $serverAr array.
					}
					$serverAr[$server->id]['emptyip'] = 
					Ip::find()->leftJoin('vps_ip', 'vps_ip.ip_id = ip.id')
					->andWhere('vps_ip.id IS NULL')
					->andWhere(['ip.server_id' => [$server->id, $server->parent_id]])
					->isPublic()					
					->count(); // How many available threads are there for this server, here I get its count.
				}
			}
			return $this->findBestPool($serverAr, $diskSize, $ipCount, $pool); // With the data we receive and transfer, we now direct it to this function to find the appropriate server and disk.
		}
		return false; // if it returns false here, it means the repository was not found in the database.
	}
	
	public function findBestPool($datas, $diskSize, $ipCount, $pool){
			if(count($datas) > 0){ // The $datas variable contains the server and the number of disk IP addresses of this server. If there is data in it, the following lines work.

				$cpuFree = []; // I created an empty array to get the CPU values of the servers
				$ramFree = []; // I created an empty array to get the ram values of the servers
				$freeDisks = []; // same^^ for disks 
				foreach($datas as $serverId => $data){ // I looped $datas.
					$server = Server::findOne($serverId); // In the $datas loop, the key value represents the server Id. I get the values of the server from the database with the server Id
					if($server){ // If the server is found in the database, I transfer the ram and cpu values of that server to the $cpuFree and $ramFree I created above.
						$cpuFree[$server->id] = $server->cpu_free; // I passed the cpu value to the $cpuFree array.
						$ramFree[$server->id] = $server->ram_free; // I transferred ram value to $cpuFree array.
					}
				}
				
				$serverIdCpu = array_keys($cpuFree, max($cpuFree))[0]; // Above, we transferred the CPU values of the servers to the $cpuFree variable. Here I find the server with the highest CPU value and get its id.. 
				
				foreach($datas[$serverIdCpu]['datastore'] as $datastoreId => $free){ // fter I find the physical server with the most suitable cpu mhz, I list the disks of that physical server and transfer how much free space it has to the $freeDisks variable below
					$freeDisks[$datastoreId] = $free; // The disk's free space has been transferred to the $freeDisks variable.
				}
				
				$diskId = array_keys($freeDisks, max($freeDisks))[0]; // I pass the datastore id number of the disk with the highest capacity to the $diskId variable..
				if(max($freeDisks) > $diskSize && max($freeDisks) > $pool->free_disk && $ramFree[$serverIdCpu] >= $pool->free_ram && $cpuFree[$serverIdCpu] >= $pool->free_cpu){ 
					// While creating a server pool, we entered values so that how many mhz cpu, how many gb ram and how many gb disk is left to eliminate the server. 
					// This is where we check the values we entered. If there is a server with a higher value than the values we entered, the following codes will work.
					$datastore = Datastore::findOne($diskId); // We got the id of the most suitable disk. We get the values of the disk from the database with this disk Id.
					if($datastore){ // If there is a disk in the database, the following process continues.
						if($datas[$datastore->server_id]['emptyip'] >= $ipCount){ // If there is 1 or more than 1 free ip address on the most suitable physical server, we say continue with the following process..
							return ['serverId' => $serverIdCpu, 'datastoreId' => $datastore->id]; // we return these values as we found the most suitable server and disk here.
						}
						else{ //Since there is no idle ip address, we do the following operations.
							unset($datas[$datastore->server_id]); //T his physical server has been eliminated because there is no idle ip address. Therefore, we remove this physical 
							// server from the $datas variable so that the function does not go into an infinite loop.
							return $this->findBestPool($datas, $diskSize, $ipCount, $pool); // we run this function again. The purpose here is to run the function continuously until we find the most suitable physical server.
						}
					}
					return false; // it will return false because the datastore is not found.
				}
				else{
					// Since the values of the physical server are low according to the control values we entered into the server pool, we will eliminate that physical server here..
					unset($datas[$serverIdCpu]); // I'm removing the physical server from the $datas variable so it doesn't loop endlessly..
					return $this->findBestPool($datas, $diskSize, $ipCount, $pool); // we run this function again. The purpose here is to run the function continuously until we find the most suitable physical server..
				}
				
			}
			return false; // We return false because there is no server in $datas variable.
	}
	
	
}