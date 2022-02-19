<?php

function init_payment_momo($currency,$phone_num,$amount,$email,$callback,$txref){
	$data_req = [
		"req"      		=> "mobile_money",
		"currency" 		=> $currency,
		"phone"    		=> $phone_num,
		"encryption_key"=> ENCRYPT_KEY,
		"amount"        => $amount,
		"emailAddress"  => $email,
		'call_back'		=> $callback,
		"txRef"			=> $txref
	];
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://silicon-pay.com/process_payments',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>json_encode($data_req),
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json',
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}


function init_payment_card($currency,$phone_num,$amount,$email,$callback,$txref,$desc,$fname,$lname,$good_callback,$bad_callback){
	$data_req = [
		"req"			=> "card_payment",
		"currency"		=> $currency,
		"fname"			=> $fname,
		"lname"			=> $lname,
		"encryption_key"=> ENCRYPT_KEY,
		"amount"		=> $amount,
		"emailAddress"	=> $email,
		'call_back'		=> $callback,
		"success_url"	=> $good_callback,
		"failure_url"	=> $bad_callback,
		"phone"			=> $phone_num,
		"description"	=> $desc,
		"txRef"			=> $txref
	];
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/process_payments',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}


function init_payment_withdraw($txref,$phone,$currency,$amount,$email,$callback,$reason){
	// Sample PHP pay Load
	$data_req = [
		"req"			=> "mm",
		"currency"		=> $currency,
		"txRef"			=> $txref,
		"encryption_key"=> ENCRYPT_KEY,
		"amount"		=> $amount,
		"emailAddress"	=> $email,
		"call_back"		=> $callback,
		"phone"			=> $phone,
		"reason"		=> $reason,
		"debit_wallet"	=> $currency
	];

	// Now Create a Signature that you shall pass in the header of your request.
	$secrete_key    = SECRET_KEY;
	$encryption_key = ENCRYPT_KEY;
	$phone_number   = $phone;

	$msg	=	hash('sha256',$encryption_key).$phone_number;
	$signature	= hash_hmac('sha256',$msg, $secrete_key);
	$headers  = [
	  "signature:". $signature,
	  'Content-Type: application/json'
	];

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://silicon-pay.com/api_withdraw',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>json_encode($data_req),
	  CURLOPT_HTTPHEADER => $headers,
	));

	$response = curl_exec($curl);

	curl_close($curl);
	return $response;
}


function query_txt_status($reference){

// Sample Pay Load
$payload = ["encryption_key"=>ENCRYPT_KEY];

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://silicon-pay.com/transaction_status/'.$reference,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>json_encode($payload),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return $response;

}

function transfer_status_check($reference){

// Sample Pay Load
$payload = ["encryption_key"=>ENCRYPT_KEY];

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://silicon-pay.com/tranfer_status/'.$reference,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>json_encode($payload),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return $response;

}
?>