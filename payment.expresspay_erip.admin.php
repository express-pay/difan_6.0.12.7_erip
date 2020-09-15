<?php

/**
 * Настройки платежной системы Экспресс платежи: ЕРИП для административного интерфейса
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

class Payment_expresspay_erip_admin
{
	public $config;
	private $diafan;

	public function __construct(&$diafan)
	{
		$this->diafan = &$diafan;
		$this->config = array(
			"name" => 'Эксперсс Платежи: ЕРИП',
			"params" => array(
				'isTest' => array(
					'name' => 'Использовать тестовый режим',
					'type' => 'checkbox',
				),
				'serviceId' => array(
					'name' => 'Номер услуги',
					'help' => 'Можно узнать в личном кабинете сервиса "Экспресс Платежи" в настройках услуги.'
				),
				'token' => array(
					'name' => 'Токен',
					'help' => 'Можно узнать в личном кабинете сервиса "Экспресс Платежи" в настройках услуги.'
				),
				'useSignature' => array(
					'name' => 'Использовать цифровую подпись для выставления счетов',
					'type' => 'checkbox',
					'help' => 'Значение должно совпадать со значением, установленным в личном кабинете сервиса "Экспресс Платежи".'
				),
				'secretWord' => array(
					'name' => 'Секретное слово',
					'help' => 'Задается в личном кабинете, секретное слово должно совпадать с секретным словом, установленным в личном кабинете сервиса "Экспресс Платежи".'
				),
				'notifUrl' => array(
					'type' => 'function'
				),
				'useSignatureForNotif' => array(
					'name' => 'Использовать цифровую подпись для уведомлений',
					'type' => 'checkbox',
					'help' => 'Значение должно совпадать со значением, установленным в личном кабинете сервиса "Экспресс Платежи".'
				),
				'secretWordForNotif' => 'Секретное слово для уведомлений',
				'showQrCode' => array(
					'name' => 'Показывать Qr-код',
					'type' => 'checkbox',
				),
				'pathToErip' => 'Путь по ветке ЕРИП',
				'isNameEdit' => array(
					'name' => 'Разрешено изменять ФИО',
					'type' => 'checkbox'
				),
				'isAmountEdit' => array(
					'name' => 'Разрешено изменять сумму',
					'type' => 'checkbox'
				),
				'isAddressEdit' => array(
					'name' => 'Разрешено изменять адрес',
					'type' => 'checkbox'
				),
				'smsNotif' => array(
					'name' => 'Отсылать уведомления плательщикам по SMS',
					'type' => 'checkbox'
				),
				'emailNotif' => array(
					'name' => 'Отсылать уведомления плательщикам по электронной почте',
					'type' => 'checkbox'
				),
			)
		);
	}

	public function edit_variable_notifUrl()
    {
		$url = BASE_PATH.'payment/get/expresspay_erip';
		$notifUrl = '<div class="unit tr_payment" payment="notifUrl" style="display:none">
		<div class="infofield">Адрес для получения уведомлений</div>
				##URL##
		</div>';
		$notifUrl = str_replace('##URL##', $url,  $notifUrl);
        echo $notifUrl;
    }
}
