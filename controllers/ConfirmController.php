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
use yii\web\HttpException;

class ConfirmController extends RestController
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
                        'actions' => ['index', 'hash'],
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
                    list($type, $hs) = explode(" ", Yii::$app->request->headers['authorization']);
                    if (base64_decode($hs) ==  $this->decode()) {
                        return new User();
                    } else {
                        return null;
                    }
                }
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['index', 'hash'],
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
        $headers = Yii::$app->request->headers;
        $params = Yii::$app->request->getBodyParams();

        Yii::$app->logger->log($params, 'confirm');

        // Do a nav payload
        $service = Yii::$app->params['ServiceName']['CoopB2B'];
        $payload = [
            'account' => $params['AcctNo'],
            'amount' => $params['Amount'],
            'bookBalance' => $params['BookedBalance'],
            'clearedBalance' => $params['ClearedBalance'],
            'currency' => $params['Currency'],
            'custMemoLine1' => $params['CustMemoLine1'],
            'eventType' => $params['EventType'],
            'exchangeRate' => $params['ExchangeRate'],
            'narration' => $params['Narration'],
            'paymentRef' => $params['PaymentRef'],
            'transactionDate' => $this->formatDate($params['TransactionDate']),
            'transactionID' => $params['TransactionId']
        ];


        $confirm = Yii::$app->navhelper->Codeunit($service, $payload, 'SendIPNNotification');
        Yii::$app->logger->log($confirm, 'confirm');

        return [
            'MessageCode' => 200,
            "Message" => "Successfully received data",
            'result' => $confirm
        ];
    }

    public function decode()
    {
        return Yii::$app->params['IntegrationUsername'] . ':' . Yii::$app->params['IntegrationPassword'];
    }

    public function formatDate($timestamp)
    {
        list($date, $time) = explode('T', $timestamp);
        return date('Y-m-d', strtotime($date));
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
