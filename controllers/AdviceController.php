<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\User;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\ContentNegotiator;
use yii\rest\Controller as RestController;


class AdviceController extends RestController
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

            /* 'basicAuth' => [
                'class' => HttpBasicAuth::class,
                'auth' => function ($username, $password) {
                    if (Yii::$app->request->getBodyParam('header')['connectionID'] == 'bandari' && Yii::$app->request->getBodyParam('header')['connectionPassword'] == 'bandari123') {
                        return new User();
                    } else {
                        return null;
                    }
                }
            ],*/
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
        $headers = Yii::$app->request->headers;
        $params = Yii::$app->request->getBodyParams();

        if ($params['header']['connectionID'] == 'bandari' &&  $params['header']['connectionPassword'] == 'bandari123') {
            // Post Advice To Nav
            $service = Yii::$app->params['ServiceName']['CoopB2B'];
            $payload = [
                'transactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                'transactionDate' => $params['request']['TransactionDate'],
                'totalAmount' => $params['request']['TotalAmount'],
                'currency' => $params['request']['Currency'],
                'documentReferenceNumber' => $params['request']['DocumentReferenceNumber'],
                'bankCode' => $params['request']['BankCode'],
                'branchCode' => $params['request']['BranchCode'],
                'paymentDate' => $params['request']['PaymentDate'],
                'paymentReferenceCode' => $params['request']['PaymentReferenceCode'],
                'paymentCode' => $params['request']['PaymentCode'],
                'paymentMode' => $params['request']['PaymentMode'],
                'paymentAmount' => $params['request']['PaymentAmount'],
                'accountNumber' => $params['request']['AccountNumber'],
                'accountName' => $params['request']['AccountName'],
                'institutionCode' => $params['request']['InstitutionCode'],
                'institutionName' => $params['request']['InstitutionName']
            ];

            $advice = Yii::$app->navhelper->Codeunit($service, $payload, 'SendAccountPaymentAdvice');

            //return $payload;
            //$this->logger($advice, 'advice');
            Yii::$app->logger->log($advice, 'advice');
            //return $payload;
            $response =  [
                'header' => [
                    'messageID' =>  Yii::$app->security->generateRandomString(8),
                    'statusCode' => 200,
                    'statusDescription' => 'Payment advice received successfully.',
                ],
                'response' => $advice
            ];

            // $this->logger($advice, 'advice');
            Yii::$app->logger->log($advice, 'advice');
            return $response;
        } else {
            $response =  [
                'header' => [
                    'messageID' =>  Yii::$app->security->generateRandomString(8),
                    'statusCode' => 401,
                    'statusDescription' => 'Unauthorized.',
                ],
                'response' => [
                    'transactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                    'transactionDate' => $params['request']['TransactionDate'],
                    'totalAmount' => $params['request']['TotalAmount'],
                    'currency' => $params['request']['Currency'],
                    'documentReferenceNumber' => $params['request']['DocumentReferenceNumber'],
                    'bankCode' => $params['request']['BankCode'],
                    'branchCode' => $params['request']['BranchCode'],
                    'paymentDate' => $params['request']['PaymentDate'],
                    'paymentReferenceCode' => $params['request']['PaymentReferenceCode'],
                    'paymentCode' => $params['request']['PaymentCode'],
                    'paymentMode' => $params['request']['PaymentMode'],
                    'paymentAmount' => $params['request']['PaymentAmount'],
                    'accountNumber' => $params['request']['AccountNumber'],
                    'accountName' => $params['request']['AccountName'],
                    'institutionCode' => $params['request']['InstitutionCode'],
                    'institutionName' => $params['request']['InstitutionName']
                ]
            ];

            //$this->logger($response, 'advice');
            Yii::$app->logger->log($response, 'advice');
            return $response;
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
