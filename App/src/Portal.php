<?php
class Portal {
	public $member_id;	
	public $member;
	public $db;
	
	public function __construct() {
		session_start();
		$this->db = new MysqliDb(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if(!isset($_SESSION['user_access_id'])){
			header('location: '.DOMAIN.'login');
			exit();
		}else{
			$this->member_id = $_SESSION['user_access_id'];
			$this->db->where('member_id', $this->member_id);
			$this->member = $this->db->getOne('members');
			if(empty($this->member)){
				unset($_SESSION['user_access_id']);
				header('location: '.DOMAIN.'login');
				exit();
			}
		}
	}
	
	protected function render($view_file,$view_data){
		$this->view_file = $view_file;
		$this->view_data = $view_data;
		if(file_exists(APP . 'view/' . $view_file . '.phtml'))
		{
		  include APP . 'view/' . $view_file . '.phtml';
		}
	}
	
	public function index() {
		$member_id = $this->member_id;
		
		$tx_list = $this->db->get('transactions', Array (0, 10));
		$tx_sum = $this->db->get('transactions');
		
		$this->db->where('member_id', $member_id);
		$status = $this->db->getOne('members')['status'];
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'portal', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/index', ['member_status' => $this->member['status'],'list' => $tx_list, 'txsum' => $tx_sum, 'status' => $status]);
		$this->render('portal/include/footer', []);
	}
	
	public function listCustomers() {
		
		$member_id = $this->member_id;
		$this->db->where('member_id', $member_id);
		$this->db->where("role", "user");
		$users = $this->db->get('members');
		
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/customers', ['users' => $users]);
		$this->render('portal/include/footer', []);
	}
	
	public function viewCustomer() {
		
		$this->db->where("role", "user");
		$users = $this->db->get('members');
		
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/customer_view', ['users' => $users]);
		$this->render('portal/include/footer', []);
	}
	
	public function listTransactions() {
		$member_id = $this->member_id;//"askdjfaksjbdfkajsbdf"; //FOR TESTING PURPOSE ONLY
		
		$this->db->where('member_id', $member_id);
		$this->db->where("(tx_type = ? OR tx_type = ?)", Array("mobile","card"));
		$this->db->orderBy("tx_timestamp","DESC");
		$tx_list = $this->db->get('transactions');
		
		$volume = new stdClass();
		$volume->ugx = 0;
		$volume->usd = 0;
		$volume->kes = 0;
		$this->db->where('member_id', $member_id);
		$this->db->orderBy("tx_timestamp","Desc");
		$all_tx = $this->db->get('transactions');
		foreach($all_tx as $query){
			if($query['status'] == 'success'){
				if($query['currency'] == 'UGX'){
					$volume->ugx += $query['total_amount'];
				}
				if($query['currency'] == 'USD'){
					$volume->usd += $query['total_amount'];
				}
				if($query['currency'] == 'KES'){
					$volume->kes += $query['total_amount'];
				}
			}	
		}
				
		$nowyear = date('Y');
		$khart = [];
		foreach($tx_list as $query){
			$year = date('Y', strtotime($query['tx_timestamp']));
			if($year == $nowyear){
				$month = date('M', strtotime($query['tx_timestamp']));
				if(isset($khart[$month])){ 
					$khart[$month] += 1; 
				}else{
					$khart[$month] = 1; 
				}
			}
		}
		$month_name = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Set', 'Oct', 'Nov', 'Dec'];
		$chart = [];
		foreach($month_name as $mnth){
			$num_count = 0;
			if(isset($khart[$mnth])){$num_count = $khart[$mnth];}
			$chart[$mnth] = $num_count;
		}
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "card");
		$query = $this->db->get('transactions');
		$card_ratio = (empty($query)) ? 0 : count($query);
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "mobile");
		$query = $this->db->get('transactions');
		$momo_ratio = (empty($query)) ? 0 : count($query);
		
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'transactions', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/transactions', ['member_status' => $this->member['status'], 'volume' => $volume, 'transactions' => $tx_list, 'chart' => $chart, 'momo_ratio' => $momo_ratio, 'card_ratio' => $card_ratio]);
		$this->render('portal/include/footer', []);
	}
	
	public function viewWallets() {
		$member_id = $this->member_id;//"askdjfaksjbdfkajsbdf"; //FOR TESTING PURPOSE ONLY
		
		$this->db->where('member_id', $member_id);
		$this->db->where('account_type', 'UGX');
		$sum_ugx = $this->db->getOne ('wallet_balance', "balance");	
		if($sum_ugx == null){$sum_ugx = 0;}else{$sum_ugx = $sum_ugx['balance'];}
		
		$this->db->where('member_id', $member_id);
		$this->db->where('account_type', 'KES');
		$sum_kes = $this->db->getOne ('wallet_balance', "balance");	
		if($sum_kes == null){$sum_kes = 0;}else{$sum_kes = $sum_kes['balance'];}
		
		$this->db->where('member_id', $member_id);
		$this->db->where('account_type', 'USD');
		$sum_usd = $this->db->getOne ('wallet_balance', "balance");	
		if($sum_usd == null){$sum_usd = 0;}else{$sum_usd = $sum_usd['balance'];}
		
		$sum_amount = ($sum_usd * 3500) + ($sum_kes * 31) + $sum_ugx;
		
		$ugx_ratio = 0;
		$kes_ratio = 0;
		$usd_ratio = 0;
		if($sum_amount > 0){
			$ugx_ratio =  ($sum_ugx * 100) / $sum_amount;
			$kes_ratio = (($sum_kes * 31) * 100) / $sum_amount;
			$usd_ratio = (($sum_usd * 3500) * 100) / $sum_amount;
		}
		
		$balance = new stdClass();
		$balance->ugx = $sum_ugx;
		$balance->kes = $sum_kes;
		$balance->usd = $sum_usd;
		
		$this->db->where('member_id', $member_id);
		$this->db->orderBy("tx_timestamp","Desc");
		$tx_list = $this->db->get('transactions', Array (0, 10));
		$tx_sum = $this->db->get('transactions');
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'wallets', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/wallets', ['list' => $tx_list, 'member_status' => $this->member['status'], 'sum' => $balance, 'ugx_ratio' => $ugx_ratio, 'kes_ratio' => $kes_ratio, 'usd_ratio' => $usd_ratio]);
		$this->render('portal/include/footer', []);
	}
	
	public function viewAccount() {
		$member_id = $this->member_id;
		$this->db->where('member_id', $member_id);
		$account = $this->db->getOne('members');	
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'account', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/account', ['account' => $account]);
		$this->render('portal/include/footer', []);
	}
	
	public function verifyAccount() {
		if($this->member['status'] == 'pending'){
			if(isset($_POST['submit'])){
				$member_id = $this->member_id;
				if(!empty($_FILES['docimg'])){
					$sourcePath = $_FILES['docimg']['tmp_name'];
					$targetPath = DATA."kyc/".$member_id.".jpg";
					if(move_uploaded_file($sourcePath,$targetPath)) {
						$data = [
							'member_id' 	=> $member_id,
							'approval'		=> 'request',
							'source'		=> DOMAIN."storage/kyc/".$member_id.".jpg"
						];						
						if($this->db->insert("kyc",$data)){
							$newdata = Array (
								'status' => 'review'
							);
							$this->db->where ('member_id', $member_id);
							if ($this->db->update ('members', $newdata)){
								header('location: '.DOMAIN.'portal/account');
								exit();
							}else{
								header('location: '.DOMAIN.'portal/account');
								exit();
							}
						}else{
							$this->render('include/header', []);
							$this->render('portal/account_kyc',  ['status' => 201, 'alert' => 'Error updating your Account Approval, please try again']);
						}
					}else{
						$this->render('include/header', []);
						$this->render('portal/account_kyc',  ['status' => 201, 'alert' => 'Error uploading file, please try again']);
					}
				}else{
					$this->render('include/header', []);
					$this->render('portal/account_kyc',  ['status' => 201, 'alert' => 'Please upload valid National ID before submitting']);
				}
				
			}else{
				$this->render('include/header', []);
				$this->render('portal/account_kyc',  ['status' => 200]);
			}
		}else{
			header('location: '.DOMAIN.'portal/account');
			exit();
		}
		
		
		
	}
	
	public function historyTransfer() {
		$member_id = $this->member_id;//"askdjfaksjbdfkajsbdf"; //FOR TESTING PURPOSE ONLY
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "deposit");
		$this->db->orderBy("tx_timestamp","Desc");
		$tx_deposit = $this->db->get('transactions');
		
		$nowyear = date('Y');
		$khart = [];
		foreach($tx_deposit as $query){
			$year = date('Y', strtotime($query['tx_timestamp']));
			if($year == $nowyear){
				$month = date('M', strtotime($query['tx_timestamp']));
				if(isset($khart[$month])){ 
					$khart[$month] += 1; 
				}else{
					$khart[$month] = 1; 
				}
			}
		}
		$month_name = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Set', 'Oct', 'Nov', 'Dec'];
		$chart = [];
		foreach($month_name as $mnth){
			$num_count = 0;
			if(isset($khart[$mnth])){$num_count = $khart[$mnth];}
			$chart[$mnth] = $num_count;
		}
		//print_r($chart);
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "deposit");
		$this->db->where("status", "success");
		$goodTotal = $this->db->getOne('transactions', "count(*) as goodTotal");
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "deposit");
		$this->db->where("status", "failed");
		$badTotal = $this->db->getOne('transactions', "count(*) as badTotal");
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'history', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/transfer_history', ['deposit' => $tx_deposit, 'chart' => $chart, 'numSuccess' => $goodTotal['goodTotal'], 'numFailed' => $badTotal['badTotal']]);
		$this->render('portal/include/footer', []);
	}
	
	public function historyWithdraw() {
		$member_id = $this->member_id;//"askdjfaksjbdfkajsbdf"; //FOR TESTING PURPOSE ONLY
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "withdraw");
		$this->db->orderBy("tx_timestamp","Desc");
		$tx_withdraw = $this->db->get('transactions');
		
		$nowyear = date('Y');
		$khart = [];
		foreach($tx_withdraw as $query){
			$year = date('Y', strtotime($query['tx_timestamp']));
			if($year == $nowyear){
				$month = date('M', strtotime($query['tx_timestamp']));
				if(isset($khart[$month])){ 
					$khart[$month] += 1; 
				}else{
					$khart[$month] = 1; 
				}
			}
		}
		$month_name = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Set', 'Oct', 'Nov', 'Dec'];
		$chart = [];
		foreach($month_name as $mnth){
			$num_count = 0;
			if(isset($khart[$mnth])){$num_count = $khart[$mnth];}
			$chart[$mnth] = $num_count;
		}
		//print_r($chart);
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "withdraw");
		$this->db->where("status", "success");
		$goodTotal = $this->db->getOne('transactions', "count(*) as goodTotal");
		
		$this->db->where('member_id', $member_id);
		$this->db->where("tx_type", "withdraw");
		$this->db->where("status", "failed");
		$badTotal = $this->db->getOne('transactions', "count(*) as badTotal");
		
		$this->render('portal/include/header', []);
		$this->render('portal/include/sidenav', ['active' => 'history', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('portal/withdraw_history', ['withdraw' => $tx_withdraw, 'chart' => $chart, 'numSuccess' => $goodTotal['goodTotal'], 'numFailed' => $badTotal['badTotal']]);
		$this->render('portal/include/footer', []);
	}
	
	public function sendWithdraw() {
		$member_id = $this->member_id;
		$obj = new stdClass();
		if(isset($_POST["currency"]) && isset($_POST["phone"]) && isset($_POST["amount"])){
			$token = hash_hmac('sha256', $member_id, $this->member['access_key']);
			
			$data_req = new stdClass();
			$data_req->member_id = $member_id;
			$data_req->email	 = $this->member['email'];
			$data_req->currency  = $_POST["currency"];
			$data_req->phone 	 = $_POST["phone"];
			$data_req->amount 	 = $_POST["amount"];
			$data_req->reason 	 = 'Customer WebPortal Withdraw';
			$data_req->callback  = 'web';
			
			$headers  = [
				'Authorization: Bearer ' . $token,
				'Content-Type: application/x-www-form-urlencoded'
			];
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => DOMAIN.'api/payment/withdraw',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => json_encode($data_req),
				CURLOPT_HTTPHEADER => $headers,
			));

			$response = curl_exec($curl);
			curl_close($curl);
			echo $response;
		}else{
			$obj->status = "failed";
			$obj->message = "invalid parameters sent";
			//$obj->post = json_encode($_POST);
			echo json_encode($obj);
			return;
		}
	}
	
	public function sendDeposit() {
		$member_id = $this->member_id;
		$obj = new stdClass();
		if(isset($_POST["phone"]) && isset($_POST["amount"]) && isset($_POST["currency"])){
			$token = hash_hmac('sha256', $member_id, $this->member['access_key']);
			$currency = $_POST["currency"];
			
			$data_req = new stdClass();
			$data_req->member_id = $member_id;
			$data_req->email	 = $this->member['email'];
			$data_req->currency  = $currency;
			$data_req->phone 	 = $_POST["phone"];
			$data_req->amount 	 = $_POST["amount"];
			$data_req->callback  = 'web';
			
			$headers  = [
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json'
			];
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => DOMAIN.'api/payment/deposit',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => json_encode($data_req),
				CURLOPT_HTTPHEADER => $headers,
			));

			$response = curl_exec($curl);
			curl_close($curl);
			echo $response;
		}else{
			$obj->status = "failed";
			$obj->message = "invalid parameters sent";
			echo json_encode($obj);
			return;
		}
	}
}

?>