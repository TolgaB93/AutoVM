<?php 
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\models\Server;
use app\helpers\Pool;
?>
<!-- content -->
<div class="content user">     
    <div class="col-md-12">
<div class="title_up"><h3>View Pool</h3></div>
	   
				<div class="row">
				
				<?php 
				
				$bestPoolClass = new Pool();
				$bestPoolClass->pool_id = $pool->id;
				
				$bestPool = $bestPoolClass->findBestServer();
				
								
				?>
				
				<?php foreach($servers as $server){ ?>
				
				<div class="col-md-4">
                      <div class="ribbon-wrapper ribbon-lg">
                        <div class="ribbon <?php if($bestPool != false){ if($bestPool['serverId'] == $server['detail']->id){ echo 'bg-success'; }else{ echo 'bg-primary'; } }else{ echo 'bg-primary';} ?>">
                          <?php echo $server['detail']->name;?>
                        </div>
                      </div>
					  
					  <p><i class="fa fa-server"></i> Dedicated Server Name : <b> <?php echo $server['detail']->name;?></b></p>
					  <p><i class="fa fa-location-arrow"></i> Number of Available IPs : <b> <?php echo $server['emptyip'];?></b></p>
					  <p><i class="fa fa-microchip"></i> Available CPU Speed : <b> <?php echo $server['detail']->cpu_free;?></b> MHZ</p>
					  <p><i class="fa fa-memory"></i> Available RAM Amount : <b> <?php echo $server['detail']->ram_free;?></b> MB</p>
					  <?php foreach($server['datastores'] as $datastore){ ?>
						<div><p <?php if($bestPool != false){ if($bestPool['datastoreId'] == $datastore->id){ echo 'class="label label-success"'; } }  ?>>
						<i class="fa fa-database"></i> <?php echo $datastore->value;?> - Free Space : <b><?php echo $datastore->free;?> GB</b></p></div>
					  
					  <?php } ?>
					  
                  </div>
				  
				<?php } ?>
				  
				
				</div>
           
           
       
    </div>
</div>
<!-- END content -->
