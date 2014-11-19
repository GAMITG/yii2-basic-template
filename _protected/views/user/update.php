<?php
use nenad\passwordStrength\PasswordInput;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \common\models\User */

$this->title = 'Update account';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-update-account">
    <h1><?= Html::encode($this->title) ?></h1>
<hr>

    <div class="row">
        <div class="col-lg-6">
            <?php $form = ActiveForm::begin(['id' => 'update-account']); ?>
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'email') ?>
                <?= $form->field($model, 'newPassword')
                         ->widget(PasswordInput::classname(), [])
                         ->passwordInput(['placeholder' => 
                            'Type new password ( if you want to change it )']) ?>
                
                <div class="form-group">
                    <?= Html::submitButton('Update', ['class' => 'btn btn-primary', 
                                                      'name' => 'update-button']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
    <div style="color:#999;margin:1em 0">
        *If you are not changing password, leave that field empty.
    </div>
</div>