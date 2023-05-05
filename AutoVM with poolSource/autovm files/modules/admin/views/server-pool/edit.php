<?php use yii\widgets\ActiveForm;use yii\helpers\Url;?>
<!-- content -->
<div class="content">
    <div class="col-md-6">
        <?php echo \app\widgets\Alert::widget();?>
        <?php $form = ActiveForm::begin(['enableClientValidation' => true]);?>
		
			<?php
				$servers = json_decode($model->servers);
				$model->servers = $servers;
			?>
		
            <?php echo $form->field($model, 'description');?>
            <?php echo $form->field($model, 'free_disk');?>
            <?php echo $form->field($model, 'free_ram');?>
            <?php echo $form->field($model, 'free_cpu');?>
            <?php echo $form->field($model, 'servers')->dropDownList(\app\models\ServerPool::getServers($model->id), array('multiple' => 'multiple'));?>
			
			
            <div class="margin-top-10"></div>
            <div class="margin-top-10"></div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary waves-effect waves-light">Submit</button>
                <button type="reset" class="btn btn-danger">Reset</button>
            </div>
        <?php ActiveForm::end();?>
    </div>
</div>
<!-- END content -->
