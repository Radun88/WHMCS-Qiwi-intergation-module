<?php
/**
 * �� ���� ������ �������� ����������� �� QIWI ��������.
 * SoapServer ������ �������� SOAP-������, ��������� �������� ����� login, password, txn, status,
 * �������� �� � ������ ������ Param � �������� ������� updateBill ������� ������ QiwiServer.
 *
 * ������ ��������� ��������� ����������� ������ ���� � updateBill.
 */

$silent = 'true'; 
include 'functions.php';
include '../../../../dbconnect.php';
include '../../../../includes/functions.php';
include '../../../../includes/gatewayfunctions.php';
include '../../../../includes/invoicefunctions.php';
  
if ( !defined('__DIR__') ) define('__DIR__', dirname(__FILE__));

$s = new SoapServer('IShopClientWS.wsdl', array('classmap' => array('tns:updateBill' => 'Param', 'tns:updateBillResponse' => 'Response')));
$s->setClass('QiwiServer');
$s->handle();

 class Response {
  public $updateBillResult;
 }

 class Param {
  public $login;
  public $password;
  public $txn;      
  public $status;
 }

 class QiwiServer {
  function updateBill($param) {

	$emailTo = 'mail@server.com';
	$emailToName = 'Name To';
	$emailFrom = 'noreply@server.com';
	$emailFromName = 'Qiwi Robot';

	$debugreport = '' . date(DATE_RFC822) . "\n";
	foreach ($param as $k => $v)
	{
		$debugreport .= '' . $k . ' => ' . $v . "\n";
	}

	if ($param->status == 60) {
	
		// ��������� ������ �������
		$result = mysql_query ('SELECT tblinvoices.*, tblpaymentgateways.value FROM tblinvoices INNER JOIN tblpaymentgateways ON  tblpaymentgateways.gateway = tblinvoices.paymentmethod WHERE tblpaymentgateways.setting = \'name\' AND tblinvoices.id = \'' . $param->txn . '\' AND tblinvoices.status = \'Unpaid\'');
		$data = mysql_fetch_array ($result);
		
		if (!empty($data['id']))
		{
			$pay_id = $data['id'];
			$pay_total = $data['total'];
			$debugreport .= (('' . $pay_id . ' => ' . $pay_total . '') . '');
		
		addinvoicepayment ($pay_id,$param->txn, $pay_total, '', 'qiwi');
		logtransaction ('QiwiTransfer', $debugreport, 'Successful');
		}
		else {
			$debugreport .= (('Invoice not found') . '');
			logtransaction ('QiwiTransfer', $debugreport, 'Error');
		}
	} else if ($param->status > 100) {
	
		// ����� �� ������� (������� �������������, ������������ ������� �� ������� � �.�.)
		// ����� ����� �� ������ ����� ($param->txn), �������� ��� ������������
		
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','����� �� �������',"������, \r\n ������ ������ �������� �������� ����: " . $param->txn . ". ������ ������� � ���� - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport , 'Error');
		
	} else if ($param->status >= 50 && $param->status < 60) {
		// ���� � �������� ����������
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','C��� � �������� ����������',"������, \r\n ������ ������ �������� �������� ����: " . $param->txn . ". ������ ������� � ���� - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport, 'Error');
	} else {
		// ����������� ������ ������
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','����������� ������ ������',"������, \r\n ������ ������ �������� �������� ����: " . $param->txn . ". ������ ������� � ���� - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport, 'Error');
	}

	// ��������� ����� �� �����������
	// ���� ��� �������� �� ���������� ������� ������ � �������� ������ �������, �������� ����� 0
	// $temp->updateBillResult = 0
	// ���� ��������� ��������� ������ (��������, ������������� ��), �������� ��������� �����
	// � ���� ������ QIWI ������ ����� ������������ �������� ��������� ����������� ���� �� ������� ��� 0
	// ��� �� ������� 24 ����
	
	$temp = new Response();
	$temp->updateBillResult = 0;
	return $temp;
  }
 }
?>
