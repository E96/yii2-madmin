<?php

namespace e96\madmin\controllers;


use e96\madmin\helpers\PhpMorphy;
use yii\db\ActiveRecord;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\grid\ActionColumn;
use yii\grid\SerialColumn;
use yii\web\Controller;

class MAdminController extends Controller
{
    /**
     * @var string Model human title
     */
    public $modelTitle = 'Модель';

    /**
     * @var array model human title in needed forms
     */
    protected $modelTitleForms = [];

    /**
     * @return string Name of managed model
     */
    public function getModelClassName()
    {
        return ActiveRecord::className();
    }

    public function init()
    {
        parent::init();

        $this->modelTitleForms = PhpMorphy::getNeededForms($this->modelTitle);
    }

    public function verbs()
    {
        return [
            'delete' => ['post'],
        ];
    }

    public function behaviors()
    {
        return [
            'verbFilter' => [
                'class' => VerbFilter::className(),
                'actions' => $this->verbs(),
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ]
            ],
        ];
    }

    public function actionIndex()
    {
        /** @var ActiveRecord $searchModel */
        $searchModel = \Yii::createObject($this->getModelClassName().'Search');
        /** @noinspection PhpUndefinedMethodInspection */
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('@madmin/views/list.twig', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'modelTitleForms' => $this->modelTitleForms,
            'tableColumns' => $this->getTableColumns($searchModel),
        ]);
    }

    public function actionCreate()
    {

    }

    public function actionView()
    {

    }

    public function actionUpdate()
    {

    }

    public function actionDelete()
    {

    }

    /**
     * Format same as GridView::$columns
     * @param ActiveRecord $model
     * @return array
     */
    public function getTableColumns($model)
    {
        $attributes = $model->getAttributes();
        foreach ($model->getTableSchema()->primaryKey as $pk) {
            unset($attributes[$pk]);
        }
        $columns = array_keys($attributes);
        $columns[] = ['class' => ActionColumn::className()];
        array_unshift($columns, ['class' => SerialColumn::className()]);

        return $columns;
    }
}