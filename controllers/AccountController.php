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
use yii\helpers\Html;
use yii\rest\Controller as RestController;
use yii\web\HttpException;


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

            /* 'basicAuth' => [
                'class' => HttpBasicAuth::class,
                'auth' => function ($username, $password) {
                    if (Yii::$app->request->getBodyParam('header')['connectionID'] === 'bandari' && Yii::$app->request->getBodyParam('header')['connectionPassword'] == 'bandari123') {
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

        /* print_r('<pre>');
        print_r($params);
        exit('End');*/

        if ($params['header']['connectionID'] == 'mhasibu' &&  $params['header']['connectionPassword'] == 'mhasibu123') {
            // Fetch the user from Nav
            $service = Yii::$app->params['ServiceName']['CreditPortalManagement'];
            $NavPayload = [
                'transactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                'transactionDate' => Html::encode($params['request']['TransactionDate']),
                'accountNumber' => '',
                'accountName' => '',
                'institutionCode' => $params['request']['InstitutionCode'],
                'institutionName' => ''
            ];
            $member = Yii::$app->navhelper->Codeunit($service, $NavPayload, 'GetAccountValidation');

            //print_r($member);exit;
            if (is_array($member) && $member['accountNumber']) {
                $response =  [
                    'header' => [
                        'messageID' =>  Yii::$app->security->generateRandomString(8),
                        'statusCode' => 200,
                        'statusDescription' => 'Successfully validated Member',
                    ],
                    'response' => [
                        'TransactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                        'TransactionDate' => $params['request']['TransactionDate'],
                        'TotalAmount' => '0.00',
                        'Currency' => '',
                        'AdditionalInfo' => '', //From Nav,
                        'AccountNumber' => $member['accountNumber'], //FROM NAV
                        'AccountName' => $member['accountName'], //FROM NAV
                        'InstitutionCode' => $member['institutionCode'], //
                        'InstitutionName' => $member['institutionName']
                    ]
                ];
                $this->logger($response, 'account');
                return $response;
            } else {
                $response =  [
                    'header' => [
                        'messageID' =>  Yii::$app->security->generateRandomString(8),
                        'statusCode' => 404,
                        'statusDescription' => 'Could not Validate  Member',
                    ],
                    'response' => [
                        'TransactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                        'TransactionDate' => $params['request']['TransactionDate'],
                        'TotalAmount' => '0.00',
                        'Currency' => '',
                        'AdditionalInfo' => '', //From Nav,
                        'AccountNumber' => '', //FROM NAV
                        'AccountName' => '', //FROM NAV
                        'InstitutionCode' => '', //
                        'InstitutionName' => '',
                        'member' => $member
                    ]
                ];

                $this->logger($response, 'account');
                return $response;
            }
        } else {
            $response = [
                'header' => [
                    'messageID' =>  Yii::$app->security->generateRandomString(8),
                    'statusCode' => 401,
                    'statusDescription' => 'Unauthorized.',
                ],
                'response' => [
                    'TransactionReferenceCode' => $params['request']['TransactionReferenceCode'],
                    'TransactionDate' => $params['request']['TransactionDate'],
                    'TotalAmount' => '0.00',
                    'Currency' => '',
                    'AdditionalInfo' => '', //From Nav,
                    'AccountNumber' => '', //FROM NAV
                    'AccountName' => '', //FROM NAV
                    'InstitutionCode' => '', //
                    'InstitutionName' => '',
                    'Error' => 'Unauthorized'
                ]
            ];

            $this->logger($response, 'account');
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

    private function logger($message, $type)
    {
        if ($type == 'advice') {
            $filename = 'log/advice.log';
        } elseif ($type == 'account') {
            $filename = 'log/account.log';
        }

        $req_dump = print_r($message, TRUE);
        $fp = fopen($filename, 'a');
        fwrite($fp, $req_dump);
        fclose($fp);
    }
}
