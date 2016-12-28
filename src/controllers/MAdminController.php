<?php

namespace e96\madmin\controllers;


use e96\madmin\helpers\PhpMorphy;
use kartik\builder\Form;
use kartik\form\ActiveForm;
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
     * @var string[] Disable some action like 'create', 'view', 'update', 'delete' with hiding corresponding buttons
     */
    public $disabledActions = [];

    /**
     * @var array model human title in needed forms
     */
    protected $modelTitleForms = [];

    /**
     * @var string actionIndex view file
     */
    public $listView = '@madmin/views/list.twig';

    /**
     * @var string actionCreate/actionUpdate view file
     */
    public $formView = '@madmin/views/edit.twig';
    
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

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->modelTitleForms)) {
            $this->modelTitleForms = PhpMorphy::getNeededForms($this->modelTitle);
        }
    }

    /**
     * @return array
     * @see \yii\filters\VerbFilter::$actions
     */
    public function verbs()
    {
        return [
            'delete' => ['post'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $accessRules = [
            [
                'allow' => true,
                'roles' => ['@'],
            ]
        ];
        if (!empty($this->disabledActions)) {
            array_unshift($accessRules, [
                'allow' => false,
                'actions' => $this->disabledActions,
            ]);
        }
        return [
            'verbFilter' => [
                'class' => VerbFilter::className(),
                'actions' => $this->verbs(),
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => $accessRules,
            ],
        ];
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        /** @var ActiveRecord $searchModel */
        $searchModel = Yii::createObject($this->getSearchModelClass());
        /** @noinspection PhpUndefinedMethodInspection */
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render($this->listView, [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'modelTitleForms' => $this->modelTitleForms,
            'disabledActions' => $this->disabledActions,
            'tableColumns' => $this->getTableColumns($searchModel),
        ]);
    }

    /**
     * @return string|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCreate()
    {
        /** @var ActiveRecord $model */
        $model = Yii::createObject($this->getManagedModelClass());

        return $this->editModel($model);
    }

    /**
     * @param string|int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
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
            return $this->render($this->formView, [
                'model' => $model,
                'returnUrl' => $this->getReturnUrl(),
                'modelTitleForms' => $this->modelTitleForms,
                'formElements' => $this->getFormElements($model),
                'formProperties' => $this->getFormProperties(),
            ]);
        }
    }

    /**
     * WIP
     * @param string|int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('@madmin/views/view.twig', [
            'model' => $this->findModel($id),
            'modelTitleForms' => $this->modelTitleForms,
//            'returnUrl' => $this->getReturnUrl(),
        ]);
    }

    /**
     * @param string|int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionDelete($id)
    {
        if (!$this->findModel($id)->delete()) {
            Yii::$app->session->setFlash('danger', $this->getFailDeleteExplanation());
        }

        return $this->redirect($this->getReturnUrl());
    }

    /**
     * @return string
     */
    public function getFailDeleteExplanation()
    {
        $chooseWord = PhpMorphy::castChosenWordBy($this->modelTitle);
        if ($chooseWord) {
            $str = 'Не удалось удалить ' . $chooseWord . ' ' . $this->modelTitleForms[0];
        } else {
            $str = 'Не удалось удалить выбранную модель';
        }

        return "<strong>$str.</strong>";
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
        if (!array_intersect(['update', 'delete'], $this->disabledActions)) {
            $columns[] = $this->getActionColumn();
        }
        array_unshift($columns, [
            'class' => SerialColumn::className(),
            'contentOptions' => [
                'class' => 'action-column',
            ],
        ]);

        return $columns;
    }

    /**
     * @return array
     */
    public function getActionColumn()
    {
        $template = '';
        if (!in_array('update', $this->disabledActions)) {
            $template .= '{update}';
        }
        if (!in_array('delete', $this->disabledActions)) {
            $template .= '{delete}';
        }
        return [
            'class' => ActionColumn::className(),
            'template' => $template,
            'contentOptions' => [
                'class' => 'action-column',
            ]
        ];
    }

    /**
     * Format same as \kartik\builder\Form::$attributes
     * @param ActiveRecord $model
     * @return array
     *
     * @see \kartik\builder\Form::$attributes
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
     * @return array
     */
    public function getFormProperties()
    {
        return ['type' => ActiveForm::TYPE_HORIZONTAL];
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
            $returnUrl = Url::to([$this->uniqueId . '/' . $this->defaultAction]);
        }

        return $returnUrl;
    }
}