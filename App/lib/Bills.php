<?php
function get_area(){
	$data_req = ["encryption_key"=>ENCRYPT_KEY];
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/get_areas',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return  $response;
}


function nwsc_get_customer($area, $account_number){
	$data_req = [
		"encryption_key"=>ENCRYPT_KEY,
		"area"=> $area ,
		"account_number"=>$account_number
	];

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/nwsc_customer_details',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER =>array(),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

function yaka_get_customer($account_number){
	$data_req = [
		"encryption_key"=>ENCRYPT_KEY,
		"account_number"=>$account_number
	];

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/yaka_customer_details',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER =>array(),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}


function get_ura_prn_customer_details($prn){
	$data_req = [
		"encryption_key"=>ENCRYPT_KEY,
		"prn"=>$prn
	];

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/ura_customer_details',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER =>array(
			"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}


function pay_tv_providers(){
	$curl = curl_init();
	$data_req = ["encryption_key"=>ENCRYPT_KEY];
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/get_pay_tv_providers',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}



function pay_tv_tariffs($short_code){
	$data_req = [
		"encryption_key"=>ENCRYPT_KEY,
		"short_code" => $short_code
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/get_pay_tv_tariffs',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER => array(
			"Content-Type: application/json"
		),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
	return $response;
}



// get the pay TV customer details.
function pay_tv_customer_details($payment_code, $smart_card_number){
	$data_req = [
		"encryption_key"=>ENCRYPT_KEY,
		"payment_code"=>$payment_code,
		"smart_card_number"=>$smart_card_number
	];

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://silicon-pay.com/get_pay_tv_customer_details',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_POSTFIELDS =>json_encode($data_req),
		CURLOPT_HTTPHEADER =>array(
			"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}
?>