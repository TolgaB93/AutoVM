<?php 
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\models\Server;
?>
<!-- content -->
<div class="content user">     
    <div class="col-md-12">
<div class="title_up"><h3>Dedicated Pools</h3></div>
       <div class="table-responsive"> 
           
            <?php echo Html::beginForm(Yii::$app->urlManager->createUrl('/admin/server-pool/delete'));?>
           
        <?php 
        Pjax::begin(['id' => 'pjax', 'enablePushState' => false]);
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'label' => 'Select',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<label class="checkbox"><input type="checkbox" name="data[]" value="' . $model->id . '"><span></span></label>';
                        }
                    ],
                    'id',
                    [
                        'label' => 'Pool Description',
                        'value' => 'description'
                    ],
                    [
                        'label' => 'servers',
                        'format' => 'raw',
                        'value' => function($model){
							$servers = json_decode($model->servers);
							$returnContent = "";
							foreach($servers as $server){
								$serverM = Server::findOne($server);
								if($serverM){
									$returnContent .= '<div><a target="_blank" href="' . Yii::$app->urlManager->createUrl(['/admin/server/edit', 'id' => $serverM->id]) . '" class="btn btn-primary btn-block"><i class="fa fa-server"></i> '.$serverM->name.'</a> <br></div>';
								}
							}
							return $returnContent;
						}
                    ],
                    [
                        'label' => 'İşlemler',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<a href="' . Yii::$app->urlManager->createUrl(['/admin/server-pool/edit', 'id' => $model->id]) . '" class="btn btn-warning"><i class="fa fa-edit"></i> Edit</a>
							<a href="' . Yii::$app->urlManager->createUrl(['/admin/server-pool/view', 'id' => $model->id]) . '" class="btn btn-success"><i class="fa fa-eye"></i> View</a>
							';
                        }
                    ],
                ],
            ]);
        Pjax::end();
        ?>
</div>
       
        <a href="<?php echo Yii::$app->urlManager->createUrl('/admin/server-pool/create');?>" class="btn btn-primary waves-effect waves-light"><i class="fa fa-plus"></i>Create </a>
        <button type="submit" class="btn btn-danger"><i class="fa fa-remove"></i>Delete</button>
        <br><br><hr>
        <?php echo Html::endForm();?>
    </div>
</div>
<!-- END content -->
