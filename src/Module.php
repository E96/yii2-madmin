<?php

namespace e96\madmin;


use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\twig\ViewRenderer;

class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        Yii::setAlias('madmin', __DIR__);

        if (is_null(Yii::$app->view->renderers) || !array_key_exists('twig', Yii::$app->view->renderers)) {
            Yii::$app->view->renderers['twig'] = [
                'class' => ViewRenderer::className(),
                'cachePath' => '@runtime/Twig/cache',
                'globals' => [
                    'html' => '\yii\helpers\Html'
                ],
                'options' => [
                    'auto_reload' => true,
                ],
                'uses' => [
                    'yii\bootstrap',
                    'yii\grid'
                ],
            ];
        } else {
            Yii::$app->view->renderers['twig']['globals']['html'] = '\yii\helpers\Html';
            if (!array_search('yii\bootstrap', Yii::$app->view->renderers['twig']['uses'])) {
                Yii::$app->view->renderers['twig']['uses'][] = 'yii\bootstrap';
            }
            if (!array_search('yii\grid', Yii::$app->view->renderers['twig']['uses'])) {
                Yii::$app->view->renderers['twig']['uses'][] = 'yii\grid';
            }
        }
        if (!Yii::$app->hasModule('gridview')) {
            Yii::$app->setModule('gridview', [
                'class' => \kartik\grid\Module::class,
            ]);
        }
    }
}