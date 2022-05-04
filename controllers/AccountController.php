<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\ContentNegotiator;
use yii\rest\Controller as RestController;

class AccountController extends RestController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'index'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],

            'basicAuth' => [
                'class' => HttpBasicAuth::class,
                'auth' => function ($username, $password) {
                    if (Yii::$app->request->getBodyParam('header')['connectionID'] === 'bandari' && Yii::$app->request->getBodyParam('header')['connectionPassword'] == 'bandari123') {
                        return new User();
                    } else {
                        return null;
                    }
                }
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['index'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'application/xml' => Response::FORMAT_XML,
                ]
            ]

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        //$ConnectionID = Yii::$app->request->getBodyParam('header')['connectionID'];
        //$ConnectionPassword = Yii::$app->request->getBodyParam('header')['connectionPassword'];
        //$ConnectionPassword = Yii::$app->request->getBodyParam('request');


        $headers = Yii::$app->request->headers;
        $params = Yii::$app->request->getBodyParam('TransactionReferenceCode');

        // Fetch the user from Nav
        $service = Yii::$app->params['ServiceName']['CoopB2B'];
        $NavPayload = [
            'transactionReferenceCode' => Yii::$app->request->getBodyParam('request')['TransactionReferenceCode'],
            'transactionDate' => Yii::$app->request->getBodyParam('request')['TransactionDate'],
            'accountNumber' => '',
            'accountName' => '',
            'institutionCode' => Yii::$app->request->getBodyParam('request')['InstitutionCode'],
            'institutionName' => ''
        ];
        $member = Yii::$app->navhelper->Codeunit($service, $NavPayload, 'GetAccountValidation');

        /* print_r('<pre>');
        print_r($member);
        exit;*/
        if (is_array($member) && $member['accountNumber']) {
            return [
                'TransactionReferenceCode' => Yii::$app->request->getBodyParam('TransactionReferenceCode'),
                'TransactionDate' => Yii::$app->request->getBodyParam('TransactionDate'),
                'TotalAmount' => '0.00',
                'Currency' => '',
                'AdditionalInfo' => '', //From Nav,
                'AccountNumber' => $member['accountNumber'], //FROM NAV
                'AccountName' => $member['accountName'], //FROM NAV
                'InstitutionCode' => $member['institutionCode'], //
                'InstitutionName' => $member['institutionName']
            ];
        } else {
            return [
                'Error' => true,
                'Message' => 'Cannot Validate Account Details'
            ];
        }
    }



    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
