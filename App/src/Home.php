<?php
class Home {
	public $db;
	
	public function __construct() {
		$this->db = new MysqliDb(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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
		$this->render('include/header', []);
		$this->render('index',  []);
		//$this->render('include/footer', []);
	}
	
	public function login() {		
		session_start();

		if(isset($_SESSION['user_access_id'])){
			$member_id = $_SESSION['user_access_id'];
			$this->db->where('member_id', $member_id);
			$member = $this->db->getOne('members');
			if(empty($member)){
				unset($_SESSION['user_access_id']);
			}
			else{
				if($member['role'] == 'user'){
					header('location: '.DOMAIN.'portal/home');
					exit();
				}
				if($member['role'] == 'admin'){
					header('location: '.DOMAIN.'dashboard/home');
					exit();
				}
			}
		}
		
		$alert;
		if(isset($_POST['submit'])){
			if(isset($_POST['email']) && isset($_POST['password'])){
				$email = $_POST['email'];
				$password = $_POST['password'];
				$hashedpass = hash("sha256", $password);
				
				$this->db->where ("email", $email);
				$this->db->where ("password", $hashedpass);
				$data = $this->db->getOne ("members");
				if(empty($data)){
					$alert = 'Wrong Email or Password, please try again';
					$this->render('include/header', []);
					$this->render('login',  ['status' => '201', 'alert' => $alert]);
				}else{
					$_SESSION['user_access_id'] = $data['member_id'];
					//echo $data['role'];
					//echo $_SESSION['user_access_id'];
					if($data['role'] == 'user'){
						header('location: '.DOMAIN.'portal/home');
						exit();
					}
					
					if($data['role'] == 'admin'){
						header('location: '.DOMAIN.'dashboard/home');
						exit();
					}
				}
			}else{
				$alert = 'Please fill all Fields before submitting';
				$this->render('include/header', []);
				$this->render('login',  ['status' => '201', 'alert' => $alert]);
			}
		}else{
			$this->render('include/header', []);
			$this->render('login',  ['status' => '200']);
		}
	}
	
	public function signUp() {	
		$alert;
		if(isset($_POST['submit'])){
			if(isset($_POST['fullname']) && isset($_POST['phone']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['repassword'])){
				$name = $_POST['fullname'];
				$phone = $_POST['phone'];
				$email = $_POST['email'];
				$pwd = $_POST['password'];
				$repwd = $_POST['repassword'];
				$hashedpass = hash("sha256", $pwd);
				
				//if(!(substr($phone, 0, 4) === '2567')){
				//	$alert = 'Please enter valid Phone number that starts with 256';
				//	$this->render('include/header', []);
				//	$this->render('signup',  ['status' => '201', 'alert' => $alert]);
				//	return ;
				//}
				
				if(!($pwd === $repwd)){
					$alert = 'Password mismatch, please retype password';
					$this->render('include/header', []);
					$this->render('signup',  ['status' => '201', 'alert' => $alert]);
					return ;
				}
				
				$this->db->where("email", $email);
				$checkup = $this->db->get("members");
				if(!(empty($checkup))){
					$alert = 'Email Address already exists, please try another email';
					$this->render('include/header', []);
					$this->render('signup',  ['status' => '201', 'alert' => $alert]);
					return ;
				}
				
				$member_id = $this->generateMemberID();
				$access_key = $this->generateAccessKey();
				
				$data = [
					'member_id' 	=> $member_id,
					'fullname'		=> $name,
					'email'			=> $email,
					'password'		=> $hashedpass,
					'phone_number'	=> $phone,
					'role' 			=> 'user',
					'access_key' 	=> $access_key,
					'status' 		=> 'pending',
					'created_date'	=> date("Y-m-d")
				];
				
				if($this->db->insert("members",$data)){
					if($this->createNewAccountWallets($member_id)){
						$_SESSION['user_access_id'] = $member_id;
						header('Location: '.DOMAIN.'portal/home');
						exit;
					}else{
						///Send Message to Admin Email
						$_SESSION['user_access_id'] = $member_id;
						header('Location: '.DOMAIN.'portal/home');
						exit;
					}
				}else{
					$alert = 'Error creating your account, Please try again';
					$this->render('include/header', []);
					$this->render('signup',  ['status' => '201', 'alert' => $alert]);
				}	
			}else{
				$alert = 'Please fill all Fields before submitting';
				$this->render('include/header', []);
				$this->render('signup',  ['status' => '201', 'alert' => $alert]);
			}
		}else{
			$this->render('include/header', []);
			$this->render('signup',  ['status' => '200']);
		}
	}
	
	public function forgotPassword() {
		$this->render('include/header', []);
		$this->render('forgot',  ['status' => '200']);
	}
	
	public function logout() {
		session_start();
		unset($_SESSION['user_access_id']);
		header('location: home');
	}
	
	function generateMemberID() {
		$bool = false;
		$length = 20;
		$member_id;
		do {
			$member_id = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
			$this->db->where("member_id", $member_id);
			$avalable = $this->db->getOne("members");
			if(empty($avalable)){
				$bool = true;
			}
		} while (!($bool));
		return $member_id;
	}
	
	function generateAccessKey() {
		$bool = false;
		$length = 21;
		$access_key;
		do {
			$access_key = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@.%$', ceil($length/strlen($x)) )),1,$length);
			$this->db->where("access_key", $access_key);
			$avalable = $this->db->getOne("members");
			if(empty($avalable)){
				$bool = true;
			}
		} while (!($bool));
		return $access_key;
	}
	
	function createNewAccountWallets($member_id) {
		$bool = true;
		$date = date("Y-m-d");
		$balance_ugx = [
			'member_id' 	=> $member_id,
			'balance'		=> 0,
			'account_type'	=> 'UGX',
			'last_update'	=> $date
		];
		$balance_kes = [
			'member_id' 	=> $member_id,
			'balance'		=> 0,
			'account_type'	=> 'KES',
			'last_update'	=> $date
		];
		$balance_usd = [
			'member_id' 	=> $member_id,
			'balance'		=> 0,
			'account_type'	=> 'USD',
			'last_update'	=> $date
		];
		$set_charges = [
			'member_id' 	=> $member_id,
			'updated_by'	=> 'DEFAULT',
			'last_update'	=> $date
		];
		if(!($this->db->insert("wallet_balance",$balance_ugx))){
			$bool = false;
		}
		if(!($this->db->insert("wallet_balance",$balance_kes))){
			$bool = false;
		}
		if(!($this->db->insert("wallet_balance",$balance_usd))){
			$bool = false;
		}
		if(!($this->db->insert("payment_charges",$set_charges))){
			$bool = false;
		}
		return $bool;
	}
	
	public function getKYCinfo() {
		if(isset($_POST['member_id'])){
			$mid = $_POST['member_id'];
			$this->db->where("approval", "request");
			$this->db->where("member_id", $mid);
			$info = $this->db->getOne('kyc');
			echo json_encode($info);
		}else{
			echo [];
		}
	}
	
	//Delete all below after testing is Done
	function TestCallback(){
		$myfile = fopen(DATA.'test_callback.txt', "a") or die("Unable to open file!");
		fwrite($myfile, file_get_contents("php://input"));
		fclose($myfile);
	}
	
	function Testhook(){
		$myfile = fopen(DATA.'webhook_call.logs', "r") or die("Unable to open file!");
		echo fread($myfile,filesize(DATA.'webhook_call.logs'));
		fclose($myfile);
		echo '<br/><br/><br/>';
		$myfile = fopen(DATA.'test_callback.txt', "r") or die("Unable to open file!");
		echo fread($myfile,filesize(DATA.'test_callback.txt'));
		fclose($myfile);
	}
	
}
?>