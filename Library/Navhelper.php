<?php

namespace app\library;

use yii;
use yii\base\Component;
use app\models\Services;
use yii\helpers\ArrayHelper;
use yii\web\Response;
//http://app-svr.rbss.com:7047/BC130/WS/RBA UAT/Page/Recruitment_Needs

/*
 * Written By francnjamb@gmail.com
 * Lots of love too....
 * */

class Navhelper extends Component
{
    //read data-> pass filters as get params
    public function getData($service, $params = [])
    {

        # return true; //comment after dev or after testing outside Navision scope env
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;

        $url = new Services($service);

        $soapWsdl = $url->getUrl();

        $filter = [];
        if (sizeof($params)) {
            foreach ($params as $key => $value) {
                $filter[] = ['Field' => $key, 'Criteria' => $value];
            }
        }


        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            // throw new \yii\web\HttpException(503, 'Service unavailable');
            Yii::$app->session->setFlash('error', 'Service unavailable.');
            $soapWsdl = null;
            return [];
        }
        // Yii::$app->recruitment->printrr($creds);
        //add the filter
        $results = Yii::$app->navision->readEntries($creds, $soapWsdl, $filter);


        //return array of object
        if (is_object($results->ReadMultiple_Result) && property_exists($results->ReadMultiple_Result, $service)) {
            $lv = (array)$results->ReadMultiple_Result;
            return $lv[$service];
        } else {
            return $results;
        }
    }



    /*Read a single entry*/


    public function findOne($service, $credentials = [], $filterKey, $filterValue)
    {

        $url  =  new Services($service);
        $wsdl = $url->getUrl();

        if (empty($credentials)) {
            $username =  Yii::$app->params['NavisionUsername'];
            $password =  Yii::$app->params['NavisionPassword'];
            $credentials = (object)[];
            $credentials->UserName = $username;
            $credentials->PassWord = $password;
        }

        if (!Yii::$app->navision->isUp($wsdl, $credentials)) {

            return ['error' => 'Service unavailable.'];
        }


        $res = (array)$result = Yii::$app->navision->readEntry($credentials, $wsdl, $filterKey, $filterValue);
        //var_dump($res); exit;
        if (count($res) && !isset($res[0])) {
            return $res[$service];
        } else {
            return $res;
        }
    }


    public function authEntry($service, $credentials = [], $filterKey, $filterValue)
    {

        $url  =  new Services($service);
        $wsdl = $url->getUrl();

        //$credentials = '';

        if (empty($credentials)) {
            $username =  Yii::$app->params['NavisionUsername'];
            $password =  Yii::$app->params['NavisionPassword'];
            $credentials = (object)[];
            $credentials->UserName = $username;
            $credentials->PassWord = $password;
        }

        if (!Yii::$app->navision->isUp($wsdl, $credentials)) {

            return ['error' => 'Service unavailable.'];
        }


        $res = $this->findOne($service, $credentials, $filterKey, $filterValue);
        return $res;
    }



    /*Read a single Record By Key*/


    public function readByKey($service, $Key)
    {

        $url  =  new Services($service);
        $wsdl = $url->getUrl();
        $username = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity->{'User ID'} : Yii::$app->params['NavisionUsername'];
        $password = Yii::$app->session->has('IdentityPassword') ? Yii::$app->session->get('IdentityPassword') : Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;

        if (!Yii::$app->navision->isUp($wsdl, $creds)) {

            return ['error' => 'Service unavailable.'];
        }


        $res = (array)$result = Yii::$app->navision->readByRecID($creds, $wsdl, $Key);

        if (count($res)) {
            return $res[$service];
        } else {
            return false;
        }
    }


    //create record(s)-----> post data
    public function postData($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];
        $post = Yii::$app->request->post();

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];
        $entryID = $service;
        foreach ($data as $key => $value) {
            if ($key !== '_csrf-backend') {
                $entry->$key = $value;
            }
        }
        //exit('lll');
        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        // $results = Yii::$app->navision->readEntries($creds, $soapWsdl,$filter);
        $results = Yii::$app->navision->addEntry($creds, $soapWsdl, $entry, $entryID);

        if (is_object($results)) {
            $lv = (array)$results;

            return $lv[$service];
        } else {
            return $results;
        }

        /*print '<pre>'; print_r($results); exit;
        $lv =(array)$results;

        return $lv[$service];*/
    }
    //update data   -->post data
    public function updateData($service, $data, $exception = [])
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];
        $post = Yii::$app->request->post();

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];
        $entryID = $service;
        foreach ($data as $key => $value) {
            if ($key !== '_csrf-backend' && !in_array($key, $exception, TRUE)) {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        // $results = Yii::$app->navision->readEntries($creds, $soapWsdl,$filter);
        $results = Yii::$app->navision->updateEntry($creds, $soapWsdl, $entry, $entryID);
        //add the filter so you don't display all loans to all and sundry.... just logic!!!
        if (is_object($results)) {
            $lv = (array)$results;

            return $lv[$service];
        } else {
            return $results;
        }
    }
    //purge data --> pass key as get param
    public function deleteData($service, $key)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];
        $url = new Services($service);
        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $soapWsdl = $url->getUrl();
        $result = Yii::$app->navision->deleteEntry($creds, $soapWsdl, $key);
        //just return the damn object
        return $result;
    }

    //Generate Invoice
    public function GenerateInvoice($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username = Yii::$app->params['NavisionUsername'];
        $password = Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-backend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->GenerateInvoice($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Create Customer
    public function CreateCustomer($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username = Yii::$app->params['NavisionUsername'];
        $password = Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-backend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->CreateCustomer($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Leave Mgt

    public function SendLeaveApprovalRequest($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->SendLeaveRequestApproval($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Cancel leave approval request

    public function CancelLeaveApprovalRequest($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username = (!Yii::$app->user->isGuest) ? Yii::$app->user->identity->{'User ID'} : Yii::$app->params['NavisionUsername'];
        $password = Yii::$app->session->has('IdentityPassword') ? Yii::$app->session->get('IdentityPassword') : Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->CancelLeaveRequestApproval($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Approve Leave Request

    public function ApproveLeaveRequest($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanApproveLeaveApplication($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }



    //Reject Leave Application

    public function RejectLeaveRequest($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanRejectLeaveApplication($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }




    //Submit a Job Application

    public function SubmitJobApplication($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanCreateJobApplication($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    /*Generate Payslip*/


    public function IanGeneratePayslip($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanGeneratePayslip($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Generate P9

    public function IanGenerateP9($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanGenerateP9($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    // Medical Claims Report


    public function IanGenerateMedicalStatementReport($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanGenerateMedicalStatementReport($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }




    //Generate Appraisal Report

    public function IanGenerateAppraisalReport($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanGenerateAppraisalReport($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    //Submit AN Appraisal for Approval

    public function IanSendGoalSettingForApproval($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendGoalSettingForApproval($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Approve Goal setting of an Appraisal

    public function IanApproveGoalSetting($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password = Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanApproveGoalSetting($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    //Reject and Return Appraisal goal setting to appraisee

    public function IanSendGoalSettingBackToAppraisee($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendGoalSettingBackToAppraisee($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Send MY Appraisal for Approval

    public function IanSendMYAppraisalForApproval($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendMYAppraisalForApproval($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Approve MY Appraisal

    public function IanApproveMYAppraisal($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanApproveMYAppraisal($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Reject Mid Year Appraisal

    public function IanSendMYAppraisaBackToAppraisee($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendMYAppraisaBackToAppraisee($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Send End Year Appraisal for Approval

    public function IanSendEYAppraisalForApproval($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisalForApproval($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Approve End Year Appraisal

    public function IanApproveEYAppraisal($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanApproveEYAppraisal($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Reject End Year Appraisal

    public function IanSendEYAppraisaBackToAppraisee($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisaBackToAppraisee($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //send appraisal to peer 1

    public function IanSendEYAppraisalToPeer1($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisalToPeer1($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //send appraisal to peer 2

    public function IanSendEYAppraisalToPeer2($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisalToPeer2($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //send End Year Appraisal back to supervisor from peer

    public function IanSendEYAppraisaBackToSupervisorFromPeer($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisaBackToSupervisorFromPeer($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //Send End-Year Appraisal to Agreement Level

    public function IanSendEYAppraisalToAgreementLevel($service, $data)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->IanSendEYAppraisalToAgreementLevel($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }



    public function Contractworkflow($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->$method($creds, $soapWsdl, $entry);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    /*COGRI Portal Workflows */

    public function PortalWorkFlows($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }


        $results = Yii::$app->navision->CongriApprovalWorkFlow($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    // Call Imprest Request

    public function Imprest($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }


        $results = Yii::$app->navision->CongriImprest($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    public function PortalReports($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }


        $results = Yii::$app->navision->PortalReports($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    public function Jobs($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }


        $results = Yii::$app->navision->PortalReports($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    /*
     * Employee Exit Code Unit Invocation
     * */

    public function EmployeeExit($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();

        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }

        $results = Yii::$app->navision->EmployeeExit($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }

    //General Code unit invocation implementation method

    public function Codeunit($service, $data, $method)
    {
        $identity = \Yii::$app->user->identity;
        $username =  Yii::$app->params['NavisionUsername'];
        $password =  Yii::$app->params['NavisionPassword'];

        $creds = (object)[];
        $creds->UserName = $username;
        $creds->PassWord = $password;
        $url = new Services($service);
        $soapWsdl = $url->getUrl();
        //exit($soapWsdl);
        $entry = (object)[];

        foreach ($data as $key => $value) {
            if ($key !== '_csrf-frontend') {
                $entry->$key = $value;
            }
        }

        if (!Yii::$app->navision->isUp($soapWsdl, $creds)) {
            throw new \yii\web\HttpException(503, 'Service unavailable');
        }


        $results = Yii::$app->navision->Codeunit($creds, $soapWsdl, $entry, $method);

        if (is_object($results)) {
            $lv = (array)$results;
            return $lv;
        } else {
            return $results;
        }
    }


    /*Method to commit single field data to services*/

    public function Commit($commitervice, $field = [], $Key)
    {



        $fieldName = $fieldValue = '';
        if (sizeof($field)) {
            foreach ($field as $key => $value) {
                $fieldName = $key;
                $fieldValue = $value;
            }
        }

        $service = Yii::$app->params['ServiceName'][$commitervice];
        // Yii::$app->recruitment->printrr($Key);

        $request = $this->readByKey($service, $Key);




        $data = [];
        if (is_object($request)) {
            $data = [
                'Key' => $request->Key,
                $fieldName => $fieldValue
            ];
        } else {
            Yii::$app->response->format = \yii\web\response::FORMAT_JSON;
            return ['error' => $request];
        }



        $result = Yii::$app->navhelper->updateData($service, $data);

        Yii::$app->response->format = \yii\web\response::FORMAT_JSON;

        return $result;
    }



    /** Authenticate user via any given page -  similar to findOne except doesnt through service availability exception */

    public function Auth($service, $credentials = [], $filterKey, $filterValue)
    {

        $url  =  new Services($service);
        $wsdl = $url->getUrl();

        if (empty($credentials)) {
            $username =  Yii::$app->params['NavisionUsername'];
            $password =  Yii::$app->params['NavisionPassword'];
            $credentials = (object)[];
            $credentials->UserName = $username;
            $credentials->PassWord = $password;
        }


        $result = Yii::$app->navision->readAuthEntry($credentials, $wsdl, $filterKey, $filterValue);

        return $result;
    }

    /**Auxilliary methods for working with models */

    public function loadmodel($obj, $model, $exception = [])
    { //load object data to a model, e,g from service data to model

        if (!is_object($obj)) {
            return false;
        }
        $modeldata = (get_object_vars($obj));
        foreach ($modeldata as $key => $val) {
            if (is_object($val) || in_array($key, $exception)) continue;
            $model->$key = $val;
        }

        return $model;
    }

    public function loadpost($post, $model, $exception = [])
    { // load form data to a model, e.g from html form-data to model


        $modeldata = (get_object_vars($model));

        foreach ($post as $key => $val) {
            if (is_object($val) || in_array($key, $exception)) continue;
            $model->$key = $val;
        }

        return $model;
    }

    // Refactor an array with valid and existing data

    public function refactorArray($arr, $from, $to)
    {
        $list = [];
        if (is_array($arr)) {

            foreach ($arr as $item) {
                if (!empty($item->$from) && !empty($item->$to)) {
                    $list[] = [
                        $from => $item->$from,
                        $to => $item->$to
                    ];
                }
            }

            return  yii\helpers\ArrayHelper::map($list, $from, $to);
        }

        return $list;
    }



    public function dropdown($service, $from, $to, $filterValues = [])
    {

        $service = Yii::$app->params['ServiceName'][$service];

        $filter = [];
        if (count($filterValues) && is_array($filterValues)) {
            foreach ($filterValues  as $key => $value) {
                $filter = [$key => $value];
            }
        } else {
            $filter = [];
        }

        // Yii::$app->recruitment->printrr($filter);

        $result = \Yii::$app->navhelper->getData($service, $filter);
        return Yii::$app->navhelper->refactorArray($result, $from, $to);
    }
}
