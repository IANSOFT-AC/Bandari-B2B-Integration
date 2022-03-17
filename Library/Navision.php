<?php

namespace app\library;
use yii;
use yii\base\Component;
class Navision extends Component
{
    public function init()
    {
        parent::init();
    }
    public function isUp($url)
    {
        //Check if service is up
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        //echo function_exists('curl_version');
        //return($httpcode);
        curl_close($ch);
        if(($httpcode == "200" ) || ( $httpcode == "302" )|| ( $httpcode == "401" )){
            return true;
        }else{
            return $httpcode;
        }
    }
    public function readEntries($credentials, $soapWsdl, $filter='')
    {


        $client = $this->createClient($credentials, $soapWsdl);



        try {
            $result = $client->ReadMultiple(['filter' => $filter, 'setSize' => 1000]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

     public function readEntry($credentials, $soapWsdl,$filter,$filterValue)
    {
        $client = $this->createClient($credentials, $soapWsdl);

        if(!is_object($client))
        {
            return false;
        }
       // var_dump($client);exit;
        try {
            $result = $client->Read([$filter => $filterValue]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function Auth($credentials, $soapWsdl,$filter,$filterValue)
    {
        $client = $this->createClient($credentials, $soapWsdl, 'Auth');

        if(!is_object($client))
        {
            return false;
        }
       // var_dump($client);exit;
        try {
            $result = $client->Read([$filter => $filterValue]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // Get Record Id from Key

      public function getRecordID($credentials, $soapWsdl,$Key)
    {
        $client = $this->createClient($credentials, $soapWsdl);
        try {
            $result = $client->GetRecIdFromKey(['Key' => $Key]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    // ReaD By RecordId


     public function readByRecID($credentials, $soapWsdl,$Key)
    {

        $RecordId = $this->getRecordID($credentials, $soapWsdl, $Key);

        $client = $this->createClient($credentials, $soapWsdl);
        try {
            $result = $client->ReadByRecId(['recId' => $RecordId->GetRecIdFromKey_Result]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function addEntry($credentials, $soapWsdl, $Entry, $EntryID)
    {
        /* print '<pre>';
         print_r($Entry); exit;*/
        $client = $this->createClient($credentials, $soapWsdl);
        try {
            $result = $client->Create([$EntryID => $Entry]);
            return $result;
        } catch (\SoapFault $e) {
            return $e->getMessage();
        }

    }
   


     // General Code Unit Invocation method

     public function Codeunit($credentials, $soapWsdl, $Entry,$method)
    {
       
        $client = $this->createClient($credentials, $soapWsdl);
       
        try {
            $result = $client->$method($Entry);
            return $result;
        } catch (\SoapFault $e) {
            return $e->getMessage();
        }

    }



    //Navision Critical Functions

    private function createClient($credentials, $soapWsdl, $context = null)
    {



        if (!defined('USERPWD'))
            define('USERPWD', "$credentials->UserName:$credentials->PassWord");
        try {
            $opts = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                )
            );
            $oPtions = [
                'soap_version' => SOAP_1_2,
                'connection_timeout' => 180,
                'login' => $credentials->UserName,
                'password' => $credentials->PassWord,

                'trace' => 1,
                'stream_context' => stream_context_create($opts)
            ];

            $context = stream_context_create([
                'ssl' => [
                    // set some SSL/TLS specific options
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $options = array("login" => $credentials->UserName,
                "password" => $credentials->PassWord,
                "features" => SOAP_SINGLE_ELEMENT_ARRAYS,
                "stream_context" => $context);


               
            /*

            UNCOMMENT THIS FOR NAV PWD AUTH */
            if(Yii::$app->params['AuthMode']['Basic'] == True){

                $client = new \SoapClient($soapWsdl, $options);
            }else if(Yii::$app->params['AuthMode']['AD'] == True)
            {

                stream_wrapper_unregister('http');
                // we register the new HTTP wrapper //'\\common\\components\\NTLMStream'
                stream_wrapper_register('http', '\\app\\Library\\NTLMStream') or die("Failed to register protocol");
                $client = new NTLMSoapClient($soapWsdl, $options);
            }

            //print_r($client); exit;
            return $client;
        } catch (\Exception $e) {
            // throw new yii\web\HttpException('503', 'Service Error '.$e->getMessage());
            return false;
        }
        return false;
    }

}

/**va
 * Extend the native soap class to support NTLM authentication
 **/
class NTLMSoapClient extends \SoapClient
{
    function __doRequest($request, $location, $action, $version, $one_way = NULL)
    {
        $headers = array(
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
        );

        $this->__last_request_headers = $headers;
        $ch = curl_init($location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, USERPWD);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = @curl_exec($ch);

        return $response;

    }

    function __getLastRequestHeaders()
    {
        return implode("\n", $this->__last_request_headers) . "\n";
    }
}

/**
 * create new streamwrappers to overrride default http wrapper
 **/
class NTLMStream
{
    private $path;
    private $mode;
    private $options;
    private $opened_path;
    private $buffer;
    private $pos;

    /**
     * Open the stream
     *
     * @param unknown_type $path
     * @param unknown_type $mode
     * @param unknown_type $options
     * @param unknown_type $opened_path
     * @return unknown
     */
    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;
        $this->createBuffer($path);
        return true;
    }

    /**
     * Close the stream
     *
     */
    public function stream_close()
    {
        curl_close($this->ch);
    }

    /**
     * Read the stream
     *
     * @param int $count number of bytes to read
     * @return content from pos to count
     */
    public function stream_read($count)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }
        $read = substr($this->buffer, $this->pos, $count);
        $this->pos += $count;
        return $read;
    }

    /**
     * write the stream
     *
     * @param int $count number of bytes to read
     * @return content from pos to count
     */
    public function stream_write($data)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }
        return true;
    }

    /**
     *
     * @return true if eof else false
     */
    public function stream_eof()
    {
        return ($this->pos > strlen($this->buffer));
    }

    /**
     * @return int the position of the current read pointer
     */
    public function stream_tell()
    {
        return $this->pos;
    }

    /**
     * Flush stream data
     */
    public function stream_flush()
    {
        $this->buffer = null;
        $this->pos = null;
    }

    /**
     * Stat the file, return only the size of the buffer
     *
     * @return array stat information
     */
    public function stream_stat()
    {
        $this->createBuffer($this->path);
        $stat = array(
            'size' => strlen($this->buffer),
        );
        return $stat;
    }

    /**
     * Stat the url, return only the size of the buffer
     *
     * @return array stat information
     */
    public function url_stat($path, $flags)
    {
        $this->createBuffer($path);
        $stat = array(
            'size' => strlen($this->buffer),
        );
        return $stat;
    }

    /**
     * Create the buffer by requesting the url through cURL
     *
     * @param unknown_type $path
     */
    private function createBuffer($path)
    {
        if ($this->buffer) {
            return;
        }
        $this->ch = curl_init($path);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->ch, CURLOPT_USERPWD, USERPWD);
        $this->buffer = curl_exec($this->ch);

        $this->pos = 0;
    }
}
