<?php

/**
 * Формирует данные для формы платежной системы Яндекс.Касса
 * 
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2018 OOO «Диафан» (http://www.diafan.ru/)
 */

if (!defined('DIAFAN')) {
	$path = __FILE__;
	while (!file_exists($path . '/includes/404.php')) {
		$parent = dirname($path);
		if ($parent == $path) exit;
		$path = $parent;
	}
	include $path . '/includes/404.php';
}

class Payment_expresspay_erip_model extends Diafan
{
	/**
	 * Формирует данные для формы платежной системы "YandexMoney"
	 * 
	 * @param array $params настройки платежной системы
	 * @param array $pay данные о платеже
	 * @return array
	 */
	public function get($params, $pay)
	{
		$order_id = $params["isTest"] ? "100" :  $pay["id"];
		$baseUrl = $params["isTest"] ? 'https://sandbox-api.express-pay.by/v1/' : 'https://api.express-pay.by/v1/';
		$amount = number_format($pay["summ"], 2, ',', '');
		$request_params = array(
			"ServiceId" => $params["serviceId"],
			"AccountNo" => $order_id,
			"Amount" => $amount,
			"Currency" => 933,
			"Surname" => $pay["details"]["firstname"],
			"FirstName" => $pay["details"]["lastname"],
			"Patronymic" => $pay["details"]["fathersname"],
			"City" => $pay["details"]["city"],
			"IsNameEditable" => $params["isNameEdit"] ? '1' : '0',
			"IsAddressEditable" => $params["isAddressEdit"] ? '1' : '0',
			"IsAmountEditable" => $params["isAmountEdit"] ? '1' : '0',
			"EmailNotification" =>$params["emailNotif"] ? $pay["details"]["email"] : "",
			"SmsPhone" => $params["smsNotif"] ? preg_replace('/[^0-9]/', '', $pay["details"]["phone"]) : "",
			"ReturnType" => "json"
		);

		$request_params['Signature'] = $this->compute_signature($request_params, $params['secretWord'], $params['token'], 'add_invoice');

		$url = $baseUrl . "web_invoices";

		$response = $this->sendRequestPOST($url, $request_params);

		$response = json_decode($response, true);

		if (isset($response['Errors'])) {
			$output_error =
				'<br />
            <h3>Ваш номер заказа: ##ORDER_ID##</h3>
            <p>При выполнении запроса произошла непредвиденная ошибка. Пожалуйста, повторите запрос позже или обратитесь в службу технической поддержки магазина</p>
            <input type="button" value="Продолжить" onClick=\'location.href="##HOME_URL##"\'>';

			$output_error = str_replace('##ORDER_ID##', $order_id,  $output_error);

			$output_error = str_replace('##HOME_URL##', BASE_PATH,  $output_error);

			$result["output"] = $output_error;

			return $result;
		} else {
			$output =
				'<table style="width: 100%;text-align: left;">
            <tbody>
                    <tr>
                        <td valign="top" style="text-align:left;">
                        <h3>Ваш номер заказа: ##ORDER_ID##</h3>
                            Вам необходимо произвести платеж в любой системе, позволяющей проводить оплату через ЕРИП (пункты банковского обслуживания, банкоматы, платежные терминалы, системы интернет-банкинга, клиент-банкинга и т.п.).
                            <br />
                            <br />1. Для этого в перечне услуг ЕРИП перейдите в раздел:  <b>##ERIP_PATH##</b> <br />
                            <br />2. В поле <b>"Номер заказа"</b> введите <b>##ORDER_ID##</b> и нажмите "Продолжить" <br />
                            <br />3. Укажите сумму для оплаты <b>##AMOUNT##</b><br />
                            <br />4. Совершить платеж.<br />
                        </td>
                            <td style="text-align: center;padding: 70px 20px 0 0;vertical-align: middle">
								##OR_CODE##
								<p><b>##OR_CODE_DESCRIPTION##</b></p>
								</td>
						</tr>
				</tbody>
            </table>
            <br />
            <input type="button" value="Продолжить" onClick=\'location.href="##HOME_URL##"\'>';

			$output = str_replace('##ORDER_ID##', $order_id,  $output);
			$output = str_replace('##ERIP_PATH##', $params['pathToErip'],  $output);
			$output = str_replace('##AMOUNT##', $amount,  $output);
			$output = str_replace('##HOME_URL##', BASE_PATH,  $output);

			if ($params["showQrCode"]) {
				$qr_code = $this->getQrCode($response['ExpressPayInvoiceNo'], $params['secretWord'], $params['token']);
				$output = str_replace('##OR_CODE##', '<img src="data:image/jpeg;base64,' . $qr_code . '"  width="200" height="200"/>',  $output);
				$output = str_replace('##OR_CODE_DESCRIPTION##', 'Отсканируйте QR-код для оплаты',  $output);
			} else {
				$output = str_replace('##OR_CODE##', '',  $output);
				$output = str_replace('##OR_CODE_DESCRIPTION##', '',  $output);
			}

			$result["output"] = $output;

			return $result;
		}
	}

	//Получение Qr-кода
	public function getQrCode($ExpressPayInvoiceNo, $secretWord, $token)
	{
		$request_params_for_qr = array(
			"Token" => $token,
			"InvoiceId" => $ExpressPayInvoiceNo,
			'ViewType' => 'base64'
		);
		$request_params_for_qr["Signature"] = $this->compute_signature($request_params_for_qr, $secretWord, $token, 'get_qr_code');

		$request_params_for_qr  = http_build_query($request_params_for_qr);
		$response_qr = $this->sendRequestGET('https://api.express-pay.by/v1/qrcode/getqrcode/?' . $request_params_for_qr);
		$response_qr = json_decode($response_qr);
		$qr_code = $response_qr->QrCodeBody;
		return $qr_code;
	}

	// Отправка POST запроса
	public function sendRequestPOST($url, $params)
	{
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
		} catch (Exception $e) {
			$this->log_info('receipt_page', 'Get response; ORDER ID - ' . $params['AccountNo'] . '; RESPONSE - ' . $response, $e);
		}
	}

	// Отправка GET запроса
	public function sendRequestGET($url)
	{
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
		} catch (Exception $e) {
			$this->log_info('receipt_page', 'RESPONSE - ' . $response, $e);
		}

	}

	//Вычисление цифровой подписи
	public function compute_signature($request_params, $secret_word, $token, $method = 'add_invoice')
	{
		$secret_word = trim($secret_word);
		$normalized_params = array_change_key_case($request_params, CASE_LOWER);
		$api_method = array(
			'add_invoice' => array(
				"serviceid",
				"accountno",
				"amount",
				"currency",
				"expiration",
				"info",
				"surname",
				"firstname",
				"patronymic",
				"city",
				"street",
				"house",
				"building",
				"apartment",
				"isnameeditable",
				"isaddresseditable",
				"isamounteditable",
				"emailnotification",
				"smsphone",
				"returntype",
				"returnurl",
				"failurl"
			),
			'get_qr_code' => array(
				"invoiceid",
				"viewtype",
				"imagewidth",
				"imageheight"
			),
			'add_invoice_return' => array(
				"accountno",
				"invoiceno"
			)
		);

		$result =  $token;

		foreach ($api_method[$method] as $item)
			$result .= (isset($normalized_params[$item])) ? $normalized_params[$item] : '';

		$hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

		return $hash;
	}

	private function log_info($name, $message)
	{
		$this->log($name, "INFO", $message);
	}

	private function log($name, $type, $message)
	{
		$log_url = dirname(__FILE__) . '/log';

		if (!file_exists($log_url)) {
			$is_created = mkdir($log_url, 0777);

			if (!$is_created)
				return;
		}

		$log_url .= '/express-pay-' . date('Y.m.d') . '.log';

		file_put_contents($log_url, $type . " - IP - " . $_SERVER['REMOTE_ADDR'] . "; DATETIME - " . date("Y-m-d H:i:s") . "; USER AGENT - " . $_SERVER['HTTP_USER_AGENT'] . "; FUNCTION - " . $name . "; MESSAGE - " . $message . ';' . PHP_EOL, FILE_APPEND);
	}
}
