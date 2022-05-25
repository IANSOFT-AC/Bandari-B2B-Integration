<?php

namespace app\library;

use yii;
use yii\base\Component;

class Logger extends Component
{
    public function log($message, $type)
    {
        $message = print_r($message, true);
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
