<?php
require APP . 'lib/Bills.php';
require APP . 'lib/Payment.php';

class API {
	public $db;
	
	public function __construct() {
		header('Content-Type: application/json; charset=utf-8');
		$this->db = new MysqliDb(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		//$this->db = new MysqliDb('164.90.215.74', 'blueplanet', 'pP@00330106', 'prime_dime');
	}
	
	protected function authAccessKey($main_key, $subb_key) {
		$token = 'NONE'; 
		$headers = apache_request_headers();
		if(isset($headers['Authorization'])){
			$token = trim(str_replace('Bearer', '', $headers['Authorization']));
		}	
		$signature	= hash_hmac('sha256', $main_key, $subb_key);

		if(md5($token) === md5($signature)){	
			return true;
		}else{
			return false;
		}		
	}
	
	protected function generateRandomString($length = 12) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);		
	}
	
	protected function get_member_info($member_id){
		$this->db->where("member_id", $member_id);
		$result = $this->db->getOne("members");
		
		if(empty($result)){
			$result = array('member_id' => '',
							'access_key' => '',
							'status' => '');
		}else{
			$result = array('member_id' => $result['member_id'],
							'access_key' => $result['access_key'],
							'status' => $result['status']);
		}
		return $result;
	}
	
	protected function get_charge_fee($member_id, $method){
		$this->db->where("member_id", $member_id);
		$result = $this->db->getOne("payment_charges");
		if(empty($result)){
			return 0;
		}else{
			if($method == 'deposit'){
				return $result['charge_deposit'];
			}
			if($method == 'withdraw'){
				return $result['charge_withdraw'];
			}
			if($method == 'momo'){
				return $result['charge_momo'];
			}
			if($method == 'card'){
				return $result['charge_card'];
			}
		}
	}
	
	protected function updateBalance($member_id, $txref){
		$this->db->where("txref", $txref);
		$tx = $this->db->getOne("transactions");
		
		$amount = $tx['total_amount'];
		$currency = $tx['currency'];
		$tx_type = $tx['tx_type'];
		$timestamp = $tx['tx_timestamp'];
		
		$this->db->where("member_id", $member_id);
		$this->db->where("account_type", $currency );
		$wallet = $this->db->getOne("wallet_balance");
		$balance = (empty($wallet)) ? 0 : $wallet['balance'];
		
		$new_balance;
		if($tx_type == 'deposit'){
			$new_balance = $balance + $amount;
		}
		if($tx_type == 'withdraw'){
			$new_balance = $balance - $amount;
		}
		if($tx_type == 'mobile'){
			$new_balance = $balance + $amount;
		}
		if($tx_type == 'card'){
			$new_balance = $balance + $amount;
		}
		
		$this->db->where("member_id", $member_id);
		$this->db->where("account_type", $currency );
		$this->db->update("wallet_balance", ['balance' => $new_balance, 'last_update' => $timestamp]);
	}

	protected function cleanTranferAmmount($amount){
		$amount = str_replace("-","", $amount);
		$amount = str_replace(",","", $amount);
		$amount = intval($amount);
		$amount = preg_replace('/[^A-Za-z0-9\-]/', '',$amount );
		$amount = abs($amount);
		return $amount;
	}
	
	protected function validate_number($gsmNumber,$gsmLength = 9){
		//gms prefixes
		$bool = false;
		$return = "";
		$mtn = ["77", "76", "78"]; //01;
		$airtel = ["70", "74", "75"]; //02;

		$AirtelKenya 	= "^(?:254|\+254|0)?((?:(?:7(?:(?:3[0-9])|(?:5[0-6])|(8[5-9])))|(?:1(?:[0][0-2])))[0-9]{6})$";
		$Orange			= "^(?:254|\+254|0)?(77[0-6][0-9]{6})$";
		$Equitel		= "^(?:254|\+254|0)?(76[34][0-9]{6})$";
		$Safaricom 		= "^(?:254|\+254|0)?((?:(?:7(?:(?:[01249][0-9])|(?:5[789])|(?:6[89])))|(?:1(?:[1][0-5])))[0-9]{6})$";

		$number = str_replace("+", "", $gsmNumber);
		
		if(substr($number, 0,3) == "256"){
			$number = str_replace("256","",$number);
		}
		if(strlen($number) == $gsmLength){
			$bool = true;
		}

		if(substr($number, 0,3) == "254"){
			$number = str_replace("254","",$number);
		}
		if(strlen($number) == $gsmLength){
			$bool = true;
		}
		
		return $bool;
	  
	}

	// History transfer transaction.
	public function transferHistory(){
		$history_list = [];
		$member_id = $_POST["member_id"];
		$this->db->orderby("tx_timestamp",'desc');
		$this->db->where("member_id", $member_id);
		$results = $this->db->get("transactions");
		echo json_encode($results);

	}
	
	
	// History withdraw transaction.
	public function withdrawHistory(){
		$history_list = [];
		$member_id = $_POST["member_id"];
		$this->db->orderby("tx_timestamp",'desc');
		$this->db->where("member_id", $member_id);
		$results = $this->db->get("transactions");
		echo json_encode($results);

	}
	
	
	// History utility transaction.
	public function utilityHistory(){
		$history_list = [];
		$member_id = $_POST["member_id"];
		$utility_type = isset($_POST['utility']) ? $_POST['utility'] : "";
		$this->db->orderby("tx_timestamp",'desc');
		$this->db->where("member_id", $member_id);
		if($utility_type !== '') { $this->db->where("tx_type", $utility_type); }
		$results = $this->db->get("utility_payments");
		echo json_encode($results);

	}
	
	
	
	// Mobile money Payment Initialization
	public function Payment_MoMo(){
		$obj = new stdClass();
		$body = file_get_contents("php://input");
		if(empty($body)){
			$obj->status = "failed";
			$obj->message = "Empty request sent";
			echo json_encode($obj);
			return;
		}
		
		$dataObject = json_decode($body);
		if (json_last_error() <> JSON_ERROR_NONE) {
			$obj->status = "failed";
			$obj->message = "Invalid Json request";
			echo json_encode($obj);
			return;
		}
		
		$member_id = isset($dataObject->member_id) ? $dataObject->member_id : "" ;
		$account = $this->get_member_info($member_id);
		if(empty($account['status'])){
			$obj->status = "failed";
			$obj->message = "Invalid Member ID";
			echo json_encode($obj);
			return;
		}
		
		$status = $account['status'];
		if(!(isset($status)) || !($status == "active")){
			$obj->status = "failed";
			$obj->message = "Member Account is Not Active";
			echo json_encode($obj);
			return;
		}
		
		$access_key = $account['access_key'];
		if($this->authAccessKey($member_id,$access_key) === false){
			$obj->status = "failed";
			$obj->message = "Invalid Authorization token ";
			echo json_encode($obj);
			return;
		}
		
		$email 			= isset($dataObject->email)		? $dataObject->email		:"";
		$cxs 			= isset($dataObject->currency)	? $dataObject->currency		:"";
		$phone 			= isset($dataObject->phone)		? $dataObject->phone		:"";
		$amount 		= isset($dataObject->amount)	? $dataObject->amount		:"";
		$usr_call_back 	= isset($dataObject->callback)	? $dataObject->callback		:"";
		$call_back 		= DOMAIN.'api/webhook';
		$txref 			= md5(microtime());
		$cxs 			= strtoupper($cxs);
		
		
		if($email == '' || $cxs == '' || $phone == '' || $amount == '' || $txref == '' || $usr_call_back == '' ){
			$missing = '{';
			$missing = $missing. (($email == '') ? ' email,' : '');
			$missing = $missing. (($phone == '') ? ' phone,' : '');
			$missing = $missing. (($amount == '') ? ' amount,' : '');
			$missing = $missing. (($cxs == '') ? ' currency,' : '');
			$missing = $missing. (($usr_call_back == '') ? ' callback,' : '');
			$missing = $missing. '}';
			
			
			$obj->status = "failed";
			$obj->message = "Missing parameter ".$missing;
			echo json_encode($obj);
			return;
		}
		
		$amount = $this->cleanTranferAmmount($amount);
		
		if(!(filter_var($email, FILTER_VALIDATE_EMAIL))) {
			$obj->status = "failed";
			$obj->message = "Invalid Email Address";
			echo json_encode($obj);
			return;
		}
		
		if($cxs <> 'UGX' && $cxs <> 'KES' && $cxs <> 'USD'){
			$obj->status = "failed";
			$obj->message = "Invalid currency";
			echo json_encode($obj);
			return;
		}
		
		$phone_validater = 'XXX';
		if($cxs <> 'USD'){
			if($cxs == 'UGX'){$phone_validater = '256';}
			if($cxs == 'KES'){$phone_validater = '254';}
			if(substr($phone, 0, 3) <> $phone_validater){
				$obj->status = "failed";
				$obj->message = "Phone number not set to correct format, please start with `".$phone_validater."` country code";
				echo json_encode($obj);
				return;
			}
		}else{
			if(substr($phone, 0, 3) == '256'){
				$wallet = 'USD';
				$cxs	= 'UGX';
			}
			if(substr($phone, 0, 3) == '254'){
				$wallet = 'USD';
				$cxs	= 'KES';
			}
		}
		
		if(!($this->validate_number($phone, 9))){
			$obj->status = "failed";
			$obj->message = "Invalid Phone Number";
			echo json_encode($obj);
			exit();
		}
		
		
		if($cxs == "KES"){
			$percentage = 5;
		}else{
			$percentage = $this->get_charge_fee($member_id, 'momo');
		}
		$charge = ($percentage / 100) * $amount;
		//lets insert this info in a payments trial databse. 
		$data = [
			'txref' => $txref,
			'member_id' => $member_id,
			'email_address'=>$email,
			'callback_url'=>$usr_call_back,
			'mobile_number'=>$phone,
			'amount'=> $amount,
			'amount_charge' => $charge,
			'tx_fee' => 0,
			'total_amount' => ($amount - $charge),
			'currency' => $cxs,
			'tx_type' => 'mobile',
			'status' => 'pending',
			'tx_timestamp' => date("Y-m-d H:i:s")
		];
		
		//Insert into Database and init transaction 
		if($this->db->insert("transactions",$data)){
			$query = init_payment_momo($cxs,$phone,$amount,$email,$call_back,$txref);	
			$query = json_decode($query);			
			echo json_encode($query);
		}else{
			$obj->status = "failed";
			$obj->message = "transactions failed to initiate";
			echo json_encode($obj);
			return;
		}	
	}


	// Debit Card Payment Initialization
	public function Payment_Card(){
		$obj = new stdClass();

		$body = file_get_contents("php://input");
		if(empty($body)){
			$obj->status = "failed";
			$obj->message = "Empty request sent";
			echo json_encode($obj);
			return;
		}
		
		$dataObject = json_decode($body);
		if (json_last_error() <> JSON_ERROR_NONE) {
			$obj->status = "failed";
			$obj->message = "Invalid Json request";
			echo json_encode($obj);
			return;
		}
		
		$member_id = isset($dataObject->member_id) ? $dataObject->member_id : "" ;
		$account = $this->get_member_info($member_id);
		if(empty($account['status'])){
			$obj->status = "failed";
			$obj->message = "Invalid API Key";
			echo json_encode($obj);
			return;
		}
		
		$status = $this->get_member_info($member_id)['status'];
		if(!(isset($status)) || !($status == "active")){
			$obj->status = "failed";
			$obj->message = "Member Account is Not Active";
			echo json_encode($obj);
			return;
		}
		
		$access_key = $this->get_member_info($member_id)['access_key'];
		if($this->authAccessKey($member_id,$access_key) === false){
			$obj->status = "failed";
			$obj->message = "Invalid Authorization token ";
			echo json_encode($obj);
			return;
		}
		
		
		$email 			= isset($dataObject->email)			? $dataObject->email		:"";
		$cxs 			= isset($dataObject->currency)		? $dataObject->currency		:"";
		$phone 			= isset($dataObject->phone)			? $dataObject->phone		:"";
		$amount 		= isset($dataObject->amount)		? $dataObject->amount		:"";
		$desc 			= isset($dataObject->description)	? $dataObject->description	:"";
		$fname			= isset($dataObject->fname)			? $dataObject->fname		:"";
		$lname 			= isset($dataObject->lname)			? $dataObject->lname		:"";
		$usr_callback 	= isset($dataObject->callback)		? $dataObject->callback		:"";
		$good_callback 	= isset($dataObject->success_url)	? $dataObject->success_url	:"";
		$bad_callback 	= isset($dataObject->failure_url)	? $dataObject->failure_url	:"";
		$call_back 		= DOMAIN.'api/webhook';
		$txref 			= md5(microtime());
		
		if($email == '' || $phone == '' || $amount == '' || $desc == '' || $fname == '' || $lname == '' || $good_callback == '' || $bad_callback == '' || $txref == '' || $usr_callback == ''){
			$missing = '{';
			$missing = $missing. (($email == '') ? ' email,' : '');
			$missing = $missing. (($phone == '') ? ' phone,' : '');
			$missing = $missing. (($amount == '') ? ' amount,' : '');
			$missing = $missing. (($desc == '') ? ' description,' : '');
			$missing = $missing. (($fname == '') ? ' fname,' : '');
			$missing = $missing. (($lname == '') ? ' lname,' : '');
			$missing = $missing. (($good_callback == '') ? ' success_url,' : '');
			$missing = $missing. (($bad_callback == '') ? ' failure_url,' : '');
			$missing = $missing. (($usr_callback == '') ? ' callback,' : '');
			$missing = $missing. '}';
			
			
			$obj->status = "failed";
			$obj->message = "Missing parameter ".$missing;
			echo json_encode($obj);
			return;
		}
		
		if(!(filter_var($email, FILTER_VALIDATE_EMAIL))) {
			$obj->status = "failed";
			$obj->message = "Invalid Email Address";
			echo json_encode($obj);
			return;
		}
		
		if($cxs != "USD"){
			$obj->status = "failed";
			$obj->message = "Invalid currency, Currency should be  USD";
			echo json_encode($obj);
			return;
		}

		$amount = $this->cleanTranferAmmount($amount);
		
		$phone_validater = 'XXX';
		
		$wallet = "USD";
		$percentage = $this->get_charge_fee($member_id, 'card');
		$charge = ($percentage / 100) * $amount;
		
		//lets insert this info in a payments trial databse. 
		$data = [
			'txref' => $txref,
			'member_id' => $member_id,
			'email_address'=>$email,
			'callback_url'=>$usr_callback,
			'mobile_number'=>$phone,
			'amount'=>$amount,
			'amount_charge' => $charge,
			'tx_fee' => 0,
			'total_amount' => ($amount - $charge),
			'currency' => "USD",
			'tx_type' => 'card',
			'status' => 'pending',
			'tx_timestamp' => date("Y-m-d H:i:s")
		];
		
		//Insert into Database and init transaction. 
		if($this->db->insert("transactions",$data)){
			$query = init_payment_card($cxs="USD",$phone,$amount,$email,$call_back,$txref,$desc,$fname,$lname,$good_callback,$bad_callback);
			$query = json_decode($query);
			$query->txRef = $txref;			
			echo json_encode($query, JSON_UNESCAPED_SLASHES);	
		}else{
			$obj->status = "failed";
			$obj->message = "transactions failed to initiate";
			echo json_encode($obj);
			return;
		}
	}
		
	
	// Withdraw Money Payment Initialization
	public function Payment_Withdraw(){
		$obj = new stdClass();
		$body = file_get_contents("php://input");
		if(empty($body)){
			$obj->status = "failed";
			$obj->message = "Empty request sent";
			echo json_encode($obj);
			return;
		}
		
		$dataObject = json_decode($body);
		if (json_last_error() <> JSON_ERROR_NONE) {
			$obj->status = "failed";
			$obj->message = "Invalid Json request";
			echo json_encode($obj);
			return;
		}
		
		$member_id = isset($dataObject->member_id) ? $dataObject->member_id : "" ;
		$account = $this->get_member_info($member_id);
		if(empty($account['status'])){
			$obj->status = "failed";
			$obj->message = "Invalid Member ID";
			echo json_encode($obj);
			return;
		}
		
		$status = $this->get_member_info($member_id)['status'];
		if(!(isset($status)) || !($status == "active")){
			$obj->status = "failed";
			$obj->message = "Member Account is Not Active";
			echo json_encode($obj);
			return;
		}
		
		$access_key = $this->get_member_info($member_id)['access_key'];
		
		if($this->authAccessKey($member_id,$access_key) === false){
			$obj->status = "failed";
			$obj->message = "Invalid Authorization token ";
			echo json_encode($obj);
			return;
		}
		
		
		
		$email 			= isset($dataObject->email)			? $dataObject->email		:"";
		$cxs 			= isset($dataObject->currency)		? $dataObject->currency		:"";
		$phone 			= isset($dataObject->phone)			? $dataObject->phone		:"";
		$amount 		= isset($dataObject->amount)		? $dataObject->amount		:"";
		$reason			= isset($dataObject->reason)		? $dataObject->reason		:"";
		$wallet_debit 	= isset($dataObject->wallet)		? $dataObject->wallet		:"UGX";
		$usr_call_back 	= isset($dataObject->callback)		? $dataObject->callback		:"";
		$call_back 		= DOMAIN.'api/webhook';
		$txref 			= md5(microtime());
		$cxs 			= strtoupper($cxs);
		$wallet 		= $cxs;
		
		if($email == '' || $phone == '' || $cxs == '' || $amount == '' || $usr_call_back == '' || $wallet_debit == '' || $txref == ''){
			$missing = '{';
			$missing = $missing. (($email == '') ? ' email,' : '');
			$missing = $missing. (($phone == '') ? ' phone,' : '');
			$missing = $missing. (($amount == '') ? ' amount,' : '');
			$missing = $missing. (($cxs == '') ? ' currency,' : '');
			$missing = $missing. (($wallet_debit == '') ? ' wallet,' : '');
			$missing = $missing. (($usr_call_back == '') ? ' callback,' : '');
			$missing = $missing. '}';
			
			
			$obj->status = "failed";
			$obj->message = "Missing parameter ".$missing;
			echo json_encode($obj);
			return;
		}
		
		
		
		if(!(filter_var($email, FILTER_VALIDATE_EMAIL))) {
			$obj->status = "failed";
			$obj->message = "Invalid Email Address";
			echo json_encode($obj);
			return;
		}
		
		if($cxs <> 'UGX' && $cxs <> 'KES' && $cxs <> 'USD'){
			$obj->status = "failed";
			$obj->message = "Invalid currency";
			echo json_encode($obj);
			return;
		}
		
		if($wallet_debit <> 'UGX' && $wallet_debit <> 'KES' && $wallet_debit <> 'USD'){
			$obj->status = "failed";
			$obj->message = "Invalid Wallet";
			echo json_encode($obj);
			return;
		}
		
		$phone_validater = 'XXX';
		if($cxs <> 'USD'){
			if($cxs == 'UGX'){$phone_validater = '256';}
			if($cxs == 'KES'){$phone_validater = '254';}
			if(substr($phone, 0, 3) <> $phone_validater){
				$obj->status = "failed";
				$obj->message = "Phone number not set to correct format, please start with `".$phone_validater."` country code";
				echo json_encode($obj);
				return;
			}
		}else{
			if(substr($phone, 0, 3) == '256'){
				$wallet = 'USD';
				$cxs	= 'UGX';
			}
			if(substr($phone, 0, 3) == '254'){
				$wallet = 'USD';
				$cxs	= 'KES';
			}
		}
		
		if(!($this->validate_number($phone, 9))){
			$obj->status = "failed";
			$obj->message = "Invalid Phone Number";
			echo json_encode($obj);
			exit();
		}
		
		
		$tariff = $this->get_charge_fee($member_id, 'withdraw');
		$tariff = $this->cleanTranferAmmount($tariff);
		$amount = $this->cleanTranferAmmount($amount);
		
		if(!(is_numeric($amount))){
			$obj->status = "failed";
			$obj->message = "Invalid Amount";
			echo json_encode($obj);
			return;
		}
		
		$charge = $tariff + $amount;
		
		$float_number = $amount + $tariff;
		$payout_number = $amount + 800; // Consider Silicon Pay charges also that is 800
	
		$this->db->where("member_id",$member_id);
		$suspend = $this->db->get("suspend_balance", null, array("SUM('balance') as Total"));
		$suspend_cash = isset($suspend["Total"]) ? $suspend["Total"] : 0;
		
		$this->db->where("member_id", $member_id);
		$this->db->where("account_type", strtoupper($wallet_debit));
		$balance = $this->db->getOne("wallet_balance");
		if(empty($balance) || $float_number > ($balance['balance'] - $suspend_cash) || $float_number == 0){
			$obj->status = "failed";
			$obj->message = "Cannot start transaction because your Wallet has insufficient balance.";
			//$obj->balance = $balance['balance'];
			//$obj->suspend = $suspend_cash;
			//$obj->float = $float_number;
			//$obj->arrgument = ($float_number > ($balance['balance'] - $suspend_cash));
			echo json_encode($obj);
			return;
		}
			
		//lets insert this info in a payments trial databse. 
		$data = [
			'txref' => $txref,
			'member_id' => $member_id,
			'email_address'=>$email,
			'callback_url'=>$usr_call_back,
			'mobile_number'=>$phone,
			'amount'=> $amount,
			'amount_charge' => $tariff,
			'tx_fee' => 0,
			'total_amount' => $float_number,
			'currency' => $wallet_debit,
			'tx_type' => 'withdraw',
			'status' => 'pending',
			'tx_timestamp' => date("Y-m-d H:i:s")
		];
			
			
		if($this->db->insert("transactions",$data)){
			$query = init_payment_withdraw($txref,$phone,$cxs,$payout_number,$email,$call_back,$reason,$wallet_debit);	
			$query = json_decode($query);
			
			if(strtolower($query->status) == "success" || strtolower($query->status) == "successful" || $query->status == 200){
				$suspend_info = [
					'member_id'=>$member_id,
					'txref'=>$txref,
					'balance'=>$payout_number,
					'currency'=>$cxs,
					'last_update' => date("Y-m-d H:i:s")
				];
				$this->db->insert("suspend_balance",$suspend_info);
			}			
			
			
			
			$json_data = json_encode ((array) $query);
			echo $json_data;
		}else{
			$obj->status = "failed";
			$obj->message = "transactions failed to initiate";
			echo json_encode($obj);
			return;
		}	
	}


	// Deposit Money Payment Initialization
	public function Payment_Deposit(){
		$obj = new stdClass();
		$body = file_get_contents("php://input");
		
		if(empty($body)){
			$obj->status = "failed";
			$obj->message = "Empty request sent";
			echo json_encode($obj);
			return;
		}
		
		$dataObject = json_decode($body);
		if (json_last_error() <> JSON_ERROR_NONE) {
			$obj->status = "failed";
			$obj->message = "Invalid Json request";
			echo json_encode($obj);
			return;
		}
		
		$member_id = isset($dataObject->member_id) ? $dataObject->member_id : "" ;
		$account = $this->get_member_info($member_id);
		if(empty($account['status'])){
			$obj->status = "failed";
			$obj->message = "Invalid API Key";
			echo json_encode($obj);
			return;
		}
		
		$status = $this->get_member_info($member_id)['status'];
		if(!(isset($status)) || !($status == "active")){
			$obj->status = "failed";
			$obj->message = "Member Account is Not Active";
			echo json_encode($obj);
			return;
		}
		
		$access_key = $this->get_member_info($member_id)['access_key'];
		if($this->authAccessKey($member_id,$access_key) === false){
			$obj->status = "failed";
			$obj->message = "Invalid Authorization token ";
			echo json_encode($obj);
			return;
		}
		
		
		$email 			= isset($dataObject->email)			? $dataObject->email		:"";
		$cxs 			= isset($dataObject->currency)		? $dataObject->currency		:"";
		$phone 			= isset($dataObject->phone)			? $dataObject->phone		:"";
		$amount 		= isset($dataObject->amount)		? $dataObject->amount		:"";
		$usr_call_back 	= isset($dataObject->callback)		? $dataObject->callback		:"";
		$call_back 		= DOMAIN.'api/webhook';
		$txref 			= md5(microtime());
		$cxs 			= strtoupper($cxs);
		
		if($email == '' || $phone == '' || $cxs == '' || $amount == '' || $usr_call_back == '' || $txref == '' ){
			$missing = '{';
			$missing = $missing. (($email == '') ? ' email,' : '');
			$missing = $missing. (($phone == '') ? ' phone,' : '');
			$missing = $missing. (($amount == '') ? ' amount,' : '');
			$missing = $missing. (($cxs == '') ? ' currency,' : '');
			$missing = $missing. (($usr_call_back == '') ? ' callback,' : '');
			$missing = $missing. '}';
			
			
			$obj->status = "failed";
			$obj->message = "Missing parameter ".$missing;
			echo json_encode($obj);
			return;
		}
		
		$amount = $this->cleanTranferAmmount($amount);
		
		if(!(filter_var($email, FILTER_VALIDATE_EMAIL))) {
			$obj->status = "failed";
			$obj->message = "Invalid Email Address";
			echo json_encode($obj);
			return;
		}
		
		if($cxs <> 'UGX' && $cxs <> 'KES' && $cxs <> 'USD'){
			$obj->status = "failed";
			$obj->message = "Invalid currency";
			echo json_encode($obj);
			return;
		}

		$phone_validater = 'XXX';
		if($cxs <> 'USD'){
			if($cxs == 'UGX'){$phone_validater = '256';}
			if($cxs == 'KES'){$phone_validater = '254';}
			if(substr($phone, 0, 3) <> $phone_validater){
				$obj->status = "failed";
				$obj->message = "Phone number not set to correct format, please start with `".$phone_validater."` country code";
				echo json_encode($obj);
				return;
			}
		}else{
			if(substr($phone, 0, 3) == '256'){
				$wallet = 'USD';
				$cxs	= 'UGX';
			}
			if(substr($phone, 0, 3) == '254'){
				$wallet = 'USD';
				$cxs	= 'KES';
			}
		}
		
		if(!($this->validate_number($phone, 9))){
			$obj->status = "failed";
			$obj->message = "Invalid Phone Number";
			echo json_encode($obj);
			exit();
		}
				
		$percentage = $this->get_charge_fee($member_id, 'momo');
		$charge = ($percentage / 100) * $amount;
		
		//lets insert this info in a payments trial databse. 
		$data = [
			'txref' => $txref,
			'member_id' => $member_id,
			'email_address'=>$email,
			'callback_url'=>$usr_call_back,
			'mobile_number'=>$phone,
			'amount'=> $amount,
			'amount_charge' => $charge,
			'tx_fee' => 0,
			'total_amount' => ($amount - $charge),
			'currency' => $cxs,
			'tx_type' => 'deposit',
			'status' => 'pending',
			'tx_timestamp' => date("Y-m-d H:i:s")
		];
		
		//Insert into Database and init Transaction. 
		if($this->db->insert("transactions",$data)){
			$query = init_payment_momo($cxs,$phone,$amount,$email,$call_back,$txref);	
			$query = json_decode($query);
			$json_data = json_encode ((array) $query);
			echo $json_data;
		}else{
			$obj->status = "failed";
			$obj->message = "transactions failed to initiate";
			echo json_encode($obj);
			return;
		}
	}
	
		
	// Recieve IPN/Call Back
	public function IPN_Webhook(){
		$myfile = fopen(DATA.'webhook_call.logs', "a") or die("Unable to open file!");
		fwrite($myfile, file_get_contents("php://input").", /n/r  ");
		fclose($myfile);
		
		if(empty(file_get_contents("php://input"))){
			$obj = new stdClass();
			$obj->status = 'error';
			$obj->message = 'invalid parameter sent to web';
			echo json_encode($obj);
			return;
		}
		// Recieve IPN. 
		$body = file_get_contents("php://input");
		$dataObject = json_decode($body);
		
		$reference   = $dataObject->txRef;
		$secure_hash = $dataObject->secure_hash;
		$secrete_key = SECRET_KEY;

		// Generate a secure hash on your end.
		$cipher = 'aes-256-ecb';
		$generated_hash = openssl_encrypt($reference, $cipher, $secrete_key);
		
		
		if($generated_hash == $secure_hash){
			// The call back came from us. 
			// Give value to your customers.
			
			// Generate a secure hash on your end.
			$member_id = $this->db->where('txref', $reference)->getOne("transactions")['member_id'];
			$member_hash = openssl_encrypt($reference, $cipher, $member_id);
						
			if($dataObject->status == 'successful'){
				$this->db->where('txref', $reference)->update("transactions", ['status' => 'success']);
				$this->updateBalance($member_id, $reference);
		
				$callback = $this->db->where('txref', $reference)->getOne("transactions")['callback_url'];
				
				
				$txReply = new stdClass();
				$txReply->status = $dataObject->status ;
				$txReply->amount = $dataObject->amount;
				$txReply->txref = $reference;
				$txReply->msisdn = $dataObject->msisdn;
				$txReply->secure_hash = $member_hash;
				
				$curl = curl_init();
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $callback,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => '',
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 0,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => 'POST',
				  CURLOPT_POSTFIELDS =>json_encode($txReply),
				  CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
				  ),
				));
				$response = curl_exec($curl);
				curl_close($curl);
			}
			
			$this->db->where('txref', $reference);
			if($this->db->has("suspend_balance")){
				$this->db->where('txref', $reference);
				$this->db->delete("suspend_balance");
			}
		}		
	}

	function cron_daily(){
		//
		$this->db->where('status','pending');
		$this->db->where('tx_type','mobile');
		$this->db->orWhere('tx_type','deposit');
		$pending_transactions = $this->db->get("transactions");

		foreach($pending_transactions as $transactions):
			$query_response = query_txt_status($transactions['txref']);

			//lets decode the response. 
			$dec = json_decode($query_response);

			
			$status = strtolower(isset($dec->status)?$dec->status: "pending");
			$code = $dec->code;
			
			if($status == 'successful'){
				$status = 'success';
			}
			
			$update_data = [
				'status'=>$status
			];
			
			$this->db->where('txref',$transactions['txref']);
			$this->db->update('transactions',$update_data );

		endforeach;

	}
	


	function cron_transfer_daily(){
		//
		$this->db->where('status','pending');
		$this->db->where('tx_type','withdraw');
		
		$pending_transactions = $this->db->get("transactions");

		foreach($pending_transactions as $transactions):
			$query_response = transfer_status_check($transactions['txref']);

			//lets decode the response. 
			$dec = json_decode($query_response);

			
			$status = strtolower(isset($dec->status)?$dec->status: "pending");
			$code = $dec->code;
			
			if($status == 'successful'){
				$status = 'success';
			}
			
			$update_data = [
				'status'=>$status
			];
			
			$this->db->where('txref',$transactions['txref']);
			$this->db->update('transactions',$update_data );

		endforeach;

	}
}


	
	

?>