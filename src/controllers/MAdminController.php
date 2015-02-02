<?php

namespace e96\madmin\controllers;


use e96\madmin\helpers\PhpMorphy;
use kartik\builder\Form;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\grid\ActionColumn;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

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
    public function getManagedModelClass()
    {
        return ActiveRecord::className();
    }

    /**
     * @return string
     */
    public function getSearchModelClass()
    {
        return $this->getManagedModelClass() . 'Search';
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
        $searchModel = Yii::createObject($this->getSearchModelClass());
        /** @noinspection PhpUndefinedMethodInspection */
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('@madmin/views/list.twig', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'modelTitleForms' => $this->modelTitleForms,
            'tableColumns' => $this->getTableColumns($searchModel),
        ]);
    }

    public function actionCreate()
    {
        /** @var ActiveRecord $model */
        $model = Yii::createObject($this->getManagedModelClass());

        return $this->editModel($model);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        return $this->editModel($model);
    }

    /**
     * @param ActiveRecord $model
     * @return string|\yii\web\Response
     */
    protected function editModel($model)
    {
        // validate() && save(false) because of CantSave exception
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->save(false)) {
            return $this->redirect($this->getReturnUrl());
        } else {
            return $this->render('@madmin/views/edit.twig', [
                'model' => $model,
                'returnUrl' => $this->getReturnUrl(),
                'modelTitleForms' => $this->modelTitleForms,
                'formElements' => $this->getFormElements($model),
            ]);
        }
    }

    public function actionView($id)
    {

    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect($this->getReturnUrl());
    }

    /**
     * Format same as GridView::$columns
     * @param ActiveRecord $model
     * @return array
     *
     * @see GridView::$columns
     */
    public function getTableColumns($model)
    {
        $attributes = $model->getAttributes();
        foreach ($model->getTableSchema()->primaryKey as $pk) {
            unset($attributes[$pk]);
        }
        $columns = array_keys($attributes);
        $columns[] = $this->getActionColumn();
        array_unshift($columns, ['class' => SerialColumn::className()]);

        return $columns;
    }

    /**
     * @return array
     */
    public function getActionColumn()
    {
        return [
            'class' => ActionColumn::className(),
            'template' => '{update}{delete}',
            'contentOptions' => [
                'class' => 'action-column',
            ]
        ];
    }

    /**
     * Format same as kartik\builder\Form::$attributes
     * @param ActiveRecord $model
     * @return array
     *
     * @see kartik\builder\Form::$attributes
     */
    public function getFormElements($model)
    {
        $res = [];
        $attributes = $model->getAttributes();
        foreach ($model->getTableSchema()->primaryKey as $pk) {
            unset($attributes[$pk]);
        }
        $attributes = array_keys($attributes);
        foreach ($attributes as $attribute) {
            $res[$attribute]['type'] = Form::INPUT_TEXT;
        }
        $res[] = $this->getActionRow($model);

        return $res;
    }

    /**
     * @param ActiveRecord $model
     * @return array
     */
    public function getActionRow($model)
    {
        $html = '';
        $icon = '<span class="glyphicon glyphicon-ok"></span>';
        $html.= Html::submitButton(
            $icon . ($model->isNewRecord ? 'Создать' : 'Сохранить'),
            [
                'class' => 'btn btn-primary',
            ]
        );
        $icon = '<span class="glyphicon glyphicon-remove"></span>';
        $html.= ' ' . Html::a(
                $icon . ($model->isNewRecord ? 'Не создавать' : 'Отменить изменения'),
                $this->getReturnUrl(),
                [
                    'class' => 'btn btn-default',
                ]
            );
        $html = "<div class=\"form-group\"><div class=\"col-md-offset-2 col-md-10\">$html</div></div>";
        return [
            'type' => Form::INPUT_RAW,
            'value' => $html,
        ];
    }

    /**
     * @param mixed $id
     * @return ActiveRecord
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = call_user_func($this->getManagedModelClass() . '::findOne', $id);
        if ($model === null) {
            throw new NotFoundHttpException('Модель не найдена');
        } else {
            return $model;
        }
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        $returnUrl = Yii::$app->request->post('_returnUrl');
        if (empty($returnUrl)) {
            $returnUrl = Yii::$app->request->referrer;
        }
        if (empty($returnUrl)) {
            $returnUrl = Url::to([$this->uniqueId . '/index']);
        }

        return $returnUrl;
    }
}