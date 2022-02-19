<?php
class Dashboard {
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
	
	protected function getBalancefromSilicoPay(){
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://silicon-pay.com/account_balance',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{"encryption_key": "'.ENCRYPT_KEY.'"}',
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response);
		return $response;
		//$ugx_balance =  $response->ugx_balance;
	}
	
	public function index() {		
		$this->db->where('account_type', 'UGX');
		$sum_ugx = $this->db->getOne ('wallet_balance', "SUM(balance) as TotalBalance");	
		if($sum_ugx == null){$sum_ugx = 0;}else{$sum_ugx = $sum_ugx['TotalBalance'];}
		
		$this->db->where('account_type', 'KES');
		$sum_kes = $this->db->getOne ('wallet_balance', "SUM(balance) as TotalBalance");
		if($sum_kes == null){$sum_kes = 0;}else{$sum_kes = $sum_kes['TotalBalance'];}
		
		$this->db->where('account_type', 'USD');
		$sum_usd = $this->db->getOne ('wallet_balance', "SUM(balance) as TotalBalance");	
		if($sum_usd == null){$sum_usd = 0;}else{$sum_usd = $sum_usd['TotalBalance'];}
		
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
		
		$this->db->orderBy("tx_timestamp","Desc");
		$tx_list = $this->db->get('transactions', Array (0, 10));
		$tx_sum = $this->db->get('transactions');
		
		$SiliconPay = $this->getBalancefromSilicoPay();
		
		$this->render('dashboard/include/header', []);
		$this->render('dashboard/include/sidenav', ['active' => 'dashboard', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('dashboard/index', ['list' => $tx_list, 'member_status' => $this->member['status'], 'sum' => $balance, 'ugx_ratio' => $ugx_ratio, 'kes_ratio' => $kes_ratio, 'usd_ratio' => $usd_ratio, 'mywallet' => $SiliconPay]);
		$this->render('dashboard/include/footer', []);
	}
	
	public function listCustomers() {
		$this->db->where("role", "user");
		$users = $this->db->get('members');
		
		$list_users = [];
		foreach($users as $user){
			$mid = $user['member_id']; 
			$this->db->where('member_id', $mid);
			$this->db->where('account_type', 'UGX');
			$sum_ugx = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_ugx == null){$sum_ugx = 0;}else{$sum_ugx = $sum_ugx['balance'];}
			
			$this->db->where('member_id', $mid);
			$this->db->where('account_type', 'KES');
			$sum_kes = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_kes == null){$sum_kes = 0;}else{$sum_kes = $sum_kes['balance'];}
			
			$this->db->where('member_id', $mid);
			$this->db->where('account_type', 'USD');
			$sum_usd = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_usd == null){$sum_usd = 0;}else{$sum_usd = $sum_usd['balance'];}
			
			$account = new stdClass();
			$account->ID = $user['member_id'];
			$account->name = $user['fullname'];
			$account->email = $user['email'];
			$account->phone = $user['phone_number'];
			$account->role = $user['role'];
			$account->status = $user['status'];
			$account->date = $user['created_date'];
			$account->ugx = $sum_ugx;
			$account->kes = $sum_kes;
			$account->usd = $sum_usd;
			
			array_push($list_users, $account);
		}
		
		
		$this->render('dashboard/include/header', []);
		$this->render('dashboard/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('dashboard/customers', ['users' => $list_users]);
		$this->render('dashboard/include/footer', []);
	}
	
	public function listKYC() {
		$this->db->where("role", "user");
		$this->db->where("status", "review");
		$users = $this->db->get('members');
		
		$this->render('dashboard/include/header', []);
		$this->render('dashboard/include/sidenav', ['active' => 'kyc', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('dashboard/kyc', ['users' => $users]);
		$this->render('dashboard/include/footer', []);
	}
	
	public function listTransactions() {
		$this->db->orderBy("tx_timestamp","Desc");
		$tx_list = $this->db->get('transactions');
		
		$volume = new stdClass();
		$volume->ugx = 0;
		$volume->usd = 0;
		$volume->kes = 0;
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
		
		$this->db->where("tx_type", "card");
		$query = $this->db->get('transactions');
		$card_ratio = (empty($query)) ? 0 : count($query);
		
		$this->db->where("tx_type", "mobile");
		$query = $this->db->get('transactions');
		$momo_ratio = (empty($query)) ? 0 : count($query);
		
		$this->render('dashboard/include/header', []);
		$this->render('dashboard/include/sidenav', ['active' => 'transactions', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('dashboard/transactions', ['txlist' => $tx_list, 'volume' => $volume, 'chart' => $chart, 'momo_ratio' => $momo_ratio, 'card_ratio' => $card_ratio]);
		$this->render('dashboard/include/footer', []);
	}
	
	public function listSetting() {
		$this->render('dashboard/include/header', []);
		$this->render('dashboard/include/sidenav', ['active' => 'settings', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
		$this->render('dashboard/setting', ['users' => $this->member]);
		$this->render('dashboard/include/footer', []);
	}
	
	
	public function viewCustomer() {
		if(isset($_GET['user'])){
			$user_id = $_GET['user'];
			$this->db->where("member_id", $user_id);
			$user = $this->db->getOne('members');
			
			$this->db->where("member_id", $user_id);
			$this->db->where("status", "success");
			$txsum = $this->db->getOne('transactions', "SUM(`member_id`) as TotalTx");
			$txsum = isset($txsum['TotalTx']) ? $txsum['TotalTx'] : 0;
			
			$this->db->where("member_id", $user_id);
			$tx = $this->db->get('transactions');
			
			$this->db->where('member_id', $user_id);
			$this->db->where('account_type', 'UGX');
			$sum_ugx = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_ugx == null){$sum_ugx = 0;}else{$sum_ugx = $sum_ugx['balance'];}
			
			$this->db->where('member_id', $user_id);
			$this->db->where('account_type', 'KES');
			$sum_kes = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_kes == null){$sum_kes = 0;}else{$sum_kes = $sum_kes['balance'];}
			
			$this->db->where('member_id', $user_id);
			$this->db->where('account_type', 'USD');
			$sum_usd = $this->db->getOne ('wallet_balance', "balance");	
			if($sum_usd == null){$sum_usd = 0;}else{$sum_usd = $sum_usd['balance'];}
			
			$balance = new stdClass();
			$balance->ugx = $sum_ugx;
			$balance->kes = $sum_kes;
			$balance->usd = $sum_usd;
			
			$this->db->where('member_id', $user_id);
			$charges = $this->db->getOne ('payment_charges');	
			
			$this->render('dashboard/include/header', []);
			$this->render('dashboard/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
			$this->render('dashboard/customer_view', ['user' => $user, 'sum' => $balance, 'txsum' => $txsum, 'tx' => $tx, 'charges' => $charges]);
			$this->render('dashboard/include/footer', []);
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function removeCustomer() {
		if(isset($_GET['user'])){
			$user_id = $_GET['user'];
			$this->db->where("member_id", $user_id);
			if($this->db->delete('members')){
				header("Location: ".DOMAIN."dashboard/customers?alert=success");
				exit;
			}else{
				header("Location: ".DOMAIN."dashboard/customers?alert=failed");
				exit;
			}
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function activeCustomer() {
		if(isset($_GET['user'])){
			$user_id = $_GET['user'];
			
			$data = Array (
				'status' => 'active'
			);
			
			$this->db->where("member_id", $user_id);
			if($this->db->update('members', $data)){
				header("Location: ".DOMAIN."dashboard/customers?alert=success");
				exit;
			}else{
				header("Location: ".DOMAIN."dashboard/customers?alert=failed");
				exit;
			}
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function deactiveCustomer() {
		if(isset($_GET['user'])){
			$user_id = $_GET['user'];
			
			$data = Array (
				'status' => 'inactive'
			);
			
			$this->db->where("member_id", $user_id);
			if($this->db->update('members', $data)){
				header("Location: ".DOMAIN."dashboard/customers?alert=success");
				exit;
			}else{
				header("Location: ".DOMAIN."dashboard/customers?alert=failed");
				exit;
			}
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function updateKYC() {
		if(isset($_GET['user']) && isset($_GET['action'])){
			$user_id = $_GET['user'];
			$action = $_GET['action'];
			if($action){
				$data = Array (
					'approval' => 'approved'
				);
				$dati = Array (
					'status' => 'active'
				);
			}else{
				$data = Array (
					'approval' => 'denied'
				);
				$dati = Array (
					'status' => 'rejected'
				);
			}
			$this->db->where("member_id", $user_id);
			$this->db->update('kyc', $data);
			
			$this->db->where("member_id", $user_id);
			if($this->db->update('members', $dati)){
				header("Location: ".DOMAIN."dashboard/kyc?alert=success");
				exit;
			}else{
				header("Location: ".DOMAIN."dashboard/kyc?alert=failed");
				exit;
			}
		}else{
			header("Location: ".DOMAIN."dashboard/kyc");
			exit;
		}
	}
		
	public function updatePayCharge() {
		if(isset($_GET['user'])){
			if(isset($_POST['ch_momo']) && isset($_POST['ch_card']) && isset($_POST['ch_withdraw']) && isset($_POST['user_id'])){
				$user_id = $_POST['user_id'];
				$ch_momo = $_POST['ch_momo'];
				$ch_card = $_POST['ch_card'];
				$ch_withdraw = $_POST['ch_withdraw'];
				
				$data = Array (
					'charge_deposit' => $ch_momo,
					'charge_withdraw' => $ch_withdraw,
					'charge_momo' => $ch_momo,
					'charge_card' => $ch_card 
				);
				$this->db->where('member_id', $user_id);
				if ($this->db->update('payment_charges', $data)){
					header("Location: ".DOMAIN."dashboard/customers/view?user=".$user_id."&alert=success");
					exit;
				}else{
					header("Location: ".DOMAIN."dashboard/customers/view?user=".$user_id."&alert=failed");
					exit;
				}
			}else{
				$user_id = $_GET['user'];
				$this->db->where('member_id', $user_id);
				$charges = $this->db->getOne ('payment_charges');	
				
				$this->render('dashboard/include/header', []);
				$this->render('dashboard/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
				$this->render('dashboard/paycharge', [ 'user_id' => $user_id, 'charges' => $charges]);
				$this->render('dashboard/include/footer', []);
			}
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function setWalletAmount() {
		if(isset($_GET['user'])){
			if(isset($_POST['wallet_ugx']) && isset($_POST['wallet_kes']) && isset($_POST['wallet_usd']) && isset($_POST['user_id'])){
				$user_id = $_POST['user_id'];
				$wallet_ugx = $_POST['wallet_ugx'];
				$wallet_kes = $_POST['wallet_kes'];
				$wallet_usd = $_POST['wallet_usd'];
								
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'UGX');
				$this->db->update('wallet_balance',  Array ( 'balance' => $wallet_ugx));
				
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'KES');
				$this->db->update('wallet_balance',  Array ( 'balance' => $wallet_kes));
				
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'USD');
				$this->db->update('wallet_balance',  Array ( 'balance' => $wallet_usd));
				
				header("Location: ".DOMAIN."dashboard/customers/view?user=".$user_id);
				exit;
			}else{
				$user_id = $_GET['user'];
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'UGX');
				$sum_ugx = $this->db->getOne ('wallet_balance', "balance");	
				if($sum_ugx == null){$sum_ugx = 0;}else{$sum_ugx = $sum_ugx['balance'];}
				
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'KES');
				$sum_kes = $this->db->getOne ('wallet_balance', "balance");	
				if($sum_kes == null){$sum_kes = 0;}else{$sum_kes = $sum_kes['balance'];}
				
				$this->db->where('member_id', $user_id);
				$this->db->where('account_type', 'USD');
				$sum_usd = $this->db->getOne ('wallet_balance', "balance");	
				if($sum_usd == null){$sum_usd = 0;}else{$sum_usd = $sum_usd['balance'];}
				
				$balance = new stdClass();
				$balance->ugx = $sum_ugx;
				$balance->kes = $sum_kes;
				$balance->usd = $sum_usd;
				
				$this->render('dashboard/include/header', []);
				$this->render('dashboard/include/sidenav', ['active' => 'customers', 'name' => $this->member['fullname'], 'email' => $this->member['email']]);
				$this->render('dashboard/setwallet', [ 'user_id' => $user_id, 'wallet' => $balance]);
				$this->render('dashboard/include/footer', []);
			}
		}else{
			header("Location: ".DOMAIN."dashboard/customers");
			exit;
		}
	}
	
	public function saveInfoConfg() {
		if(isset($_POST['fullname']) && isset($_POST['phone']) && isset($_POST['email'])){
			$name = $_POST['fullname'];
			$phone= $_POST['phone'];
			$email = $_POST['email'];
			
			$data = Array(
					'fullname' => $name,
					'email' => $email,
					'phone_number' => $phone
			);
			
			$this->db->where("member_id", $this->member_id);
			if($this->db->update('members', $data)){
				header("Location: ".DOMAIN."dashboard/settings?alert=success");
				exit;
			}else{
				header("Location: ".DOMAIN."dashboard/settings?alert=failed");
				exit;
			}
		}else{
			header("Location: ".DOMAIN."dashboard/settings");
			exit;
		}
	}
	
	public function savePasswordConfg() {
		if(isset($_POST['pwd']) && isset($_POST['repwd'])){
			$pwd = $_POST['pwd'];
			$repwd= $_POST['repwd'];
			
			if($pwd == $repwd){
				$hashpwd = hash('sha256', $pwd);
				$data = Array(
					'password' => $hashpwd
				);
				
				$this->db->where("member_id", $this->member_id);
				if($this->db->update('members', $data)){
					header("Location: ".DOMAIN."dashboard/settings?alert=success");
					exit;
				}else{
					header("Location: ".DOMAIN."dashboard/settings?alert=failed");
					exit;
				}
			}else{
				header("Location: ".DOMAIN."dashboard/settings?alert=mismatch");
				exit;
			}
			
			
		}else{
			header("Location: ".DOMAIN."dashboard/settings");
			exit;
		}
	}
	
	
	
}
?>