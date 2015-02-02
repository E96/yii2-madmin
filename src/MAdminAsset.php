<?php

namespace e96\madmin;


use yii\web\AssetBundle;

class MAdminAsset extends AssetBundle
{
    public $sourcePath = '@e96/madmin/assets';
    public $css = [
        'main.css',
    ];
    public $depends = [
        'yii\bootstrap\BootstrapAsset',
    ];
}