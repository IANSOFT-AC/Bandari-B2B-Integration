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
use jamguozhijun\yii\web\XmlParser;
use stdClass;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\rest\Controller as RestController;
use yii\web\HttpException;

class NcbaController extends Controller
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
                    'index' => ['post'],
                ],
            ],

            /* 'basicAuth' => [
                'class' => HttpBasicAuth::class,
                'auth' => function($username, $password) {
                    if(Yii::$app->request->getBodyParam('header')['connectionID'] === 'User1' && Yii::$app->request->getBodyParam('header')['connectionPassword'] == 'ijidjiwejie'){
                        return new User();
                    }else{
                        return null;
                    }
                }
            ],*/
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['index'],
                'formats' => [
                    // 'application/json' => Response::FORMAT_JSON,
                    'application/xml' => Response::FORMAT_XML,
                    'text/xml' => Response::FORMAT_XML,
                ]
            ]

        ];
    }

    public function beforeAction($action)
    {

        $ExceptedActions = [
            'index'
        ];

        if (in_array($action->id, $ExceptedActions)) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }


    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        //$currentTime = strtotime(date('Y-m-d'));
        //return $currentTime;
        //$Key = Yii::$app->security->generateRandomString(64);
        $data = trim(file_get_contents('php://input'));
        $xml = new \SimpleXMLElement($data);
        $items = $xml->xpath('*/NCBAPaymentNotificationRequest');
        $body = json_encode($items[0]);
        $request = json_decode($body);
        $service = Yii::$app->params['ServiceName']['CreditPortalManagement'];



        if ($request->User == env('API_USER') &&  $request->Password == env('API_PWD')) {
            // Fetch the user from Nav
            $NavPayload = [
                'transactionType' => $request->TransType,
                'transactionID' => $request->TransID,
                'transactionTime' => date('Y-m-d', trim($request->TransTime)),
                'transactionAmount' => $request->TransAmount,
                'accountNo' => $request->AccountNr,
                'phoneNo' => $request->PhoneNr,
                'customerName' => $request->CustomerName,
                'status' => $request->Status
            ];

            //return $NavPayload;
            $confirmation = Yii::$app->navhelper->Codeunit($service, $NavPayload, 'NCBAPaymentAdvice');

            if (!is_string($confirmation)) {
                $response = [
                    'NCBAPaymentNotificationResult' => [
                        'Result' => 'OK',
                        'payload' => $confirmation,
                        'time' => date('Y-m-d H:i:s'),
                    ]
                ];
                $this->logger($response, 'confirm');
                return $response;
            } else {
                $response = [
                    'NCBAPaymentNotificationResult' => [
                        'Result' => 'Fail',
                        'Error' => $confirmation,
                        'time' => date('Y-m-d H:i:s'),
                    ]
                ];

                $this->logger($response, 'confirm');
                return $response;
            }
        } else {
            $response = [
                'NCBAPaymentNotificationResult' => [
                    'Result' => 'FAIL',
                    'Error' => 'UnAuthorized',
                    'time' => date('Y-m-d H:i:s'),
                ]
            ];

            $this->logger($response, 'confirm');
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
        } elseif ($type == 'confirm') {
            $filename = 'log/confirm.log';
        }

        $req_dump = print_r($message, TRUE);
        $fp = fopen($filename, 'a');
        fwrite($fp, $req_dump);
        fclose($fp);
    }
}
