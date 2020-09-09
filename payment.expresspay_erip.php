<?php
/**
 * Работа с платежной системой Яндекс.Касса
 *
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2018 OOO «Диафан» (http://www.diafan.ru/)
 */

if (! defined('DIAFAN'))
{
	$path = __FILE__;
	while(! file_exists($path.'/includes/404.php'))
	{
		$parent = dirname($path);
		if($parent == $path) exit;
		$path = $parent;
	}
	include $path.'/includes/404.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Получение данных
    $json = $_POST['Data'];
    $signature = $_POST['Signature'];

    // Преобразуем из JSON в Array
    $data = json_decode($json, true);

    $id = $data['AccountNo'];

    $pay = $this->diafan->_payment->check_pay($id, 'expresspay_erip');

    $isUseSignature = $pay["params"]['useSignatureForNotif'] ? true : false;

    if ($isUseSignature) {

        $secretWord = $pay["params"]['secretWord'];

        if ($signature == computeSignature($json, $secretWord)) {
            if ($data['CmdType'] == '3' && $data['Status'] == '3' || $data['Status'] == '6') {
				$this->diafan->_payment->success($pay);
                header("HTTP/1.0 200 OK");
                print $status = 'OK | payment received'; //Все успешно
            }elseif ($data['CmdType'] == '3' && $data['Status'] == '5')
            {
				$this->diafan->_payment->fail($pay); // Изменение статуса заказа на отменён
                header("HTTP/1.0 200 OK");
                print $status = 'OK | payment received'; //Все успешно
            }
        } else {
            header("HTTP/1.0 400 Bad Request");
            print $status = 'FAILED | wrong notify signature  '; //Ошибка в параметрах
        }
    } elseif ($data['CmdType'] == '3' && $data['Status'] == '3' || $data['Status'] == '6') 
        {   
            echo $data['Status'];
			$this->diafan->_payment->success($pay); // Изменение статуса заказа на оплачен
            header("HTTP/1.0 200 OK");
            print $status = 'OK | payment received'; //Все успешно
        }elseif ($data['CmdType'] == '3' && $data['Status'] == '5')
        {
            echo $data['Status'];
			$this->diafan->_payment->fail($pay); // Изменение статуса заказа на отменён
            header("HTTP/1.0 200 OK");
            print $status = 'OK | payment received'; //Все успешно
        }
    } else {
        header("HTTP/1.0 200 Bad Request");
        print $status = 'FAILED | ID заказа неизвестен';
    }

// Проверка электронной подписи
function computeSignature($json, $secretWord)
{
    $hash = NULL;

    $secretWord = trim($secretWord);

    if (empty($secretWord))
        $hash = strtoupper(hash_hmac('sha1', $json, ""));
    else
        $hash = strtoupper(hash_hmac('sha1', $json, $secretWord));
    return $hash;
}

