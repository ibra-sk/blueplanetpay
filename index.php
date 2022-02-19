<?php
const DS = DIRECTORY_SEPARATOR;
define('DOMAIN', 'https://blueplanetpay.com/');
define('TITLE', "BluePlanet Pay ");
define('APP', dirname(__DIR__) . DS."blueplanet".DS . "App" . DS);
define('DATA', dirname(__DIR__) . DS ."blueplanet".DS. "storage" . DS);

define('DB_TYPE', 'mysql');
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('ENCRYPT_KEY', '');
define('SECRET_KEY', '');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'route.php';

//Libraries
//include APP . 'lib/Model.php';
include APP . 'lib/Database.php';

//Hanlders
include APP . 'src/ErrorHandle.php';

//Controllers
include APP . 'src/API.php';
include APP . 'src/Home.php';
include APP . 'src/Dashboard.php';
include APP . 'src/Portal.php';

$route = new Route();
$route->add('/', 'Home', '');
$route->add('/home', 'Home', '');
$route->add('/login', 'Home', 'login');
$route->add('/signup', 'Home', 'signUp');
$route->add('/logout', 'Home', 'logout');
$route->add('/forgot-password', 'Home', 'forgotPassword');
$route->add('/reviewinfo', 'Home', 'getKYCinfo');


//Dashboard Routes
$route->add('/dashboard', 'Dashboard', 'index');
$route->add('/dashboard/home', 'Dashboard', 'index');
$route->add('/dashboard/customers', 'Dashboard', 'listCustomers');
$route->add('/dashboard/customers/view', 'Dashboard', 'viewCustomer');
$route->add('/dashboard/customers/delete', 'Dashboard', 'removeCustomer');
$route->add('/dashboard/customers/active', 'Dashboard', 'activeCustomer');
$route->add('/dashboard/customers/deactive', 'Dashboard', 'deactiveCustomer');
$route->add('/dashboard/customers/paycharge', 'Dashboard', 'updatePayCharge');
$route->add('/dashboard/customers/setwallet', 'Dashboard', 'setWalletAmount');
$route->add('/dashboard/kyc', 'Dashboard', 'listKYC');
$route->add('/dashboard/kyc/update', 'Dashboard', 'updateKYC');
$route->add('/dashboard/transactions', 'Dashboard', 'listTransactions');
$route->add('/dashboard/settings', 'Dashboard', 'listSetting');
$route->add('/dashboard/settings/saveinfo', 'Dashboard', 'saveInfoConfg');
$route->add('/dashboard/settings/savepwd', 'Dashboard', 'savePasswordConfg');


//Portal Routes
$route->add('/portal', 'Portal', 'viewWallets');
$route->add('/portal/home', 'Portal', 'viewWallets');
$route->add('/portal/customers', 'Portal', 'listCustomers');
$route->add('/portal/customers/view/', 'Portal', 'viewCustomer');
$route->add('/portal/transactions', 'Portal', 'listTransactions');
//$route->add('/portal/wallets', 'Portal', 'viewWallets');
$route->add('/portal/account', 'Portal', 'viewAccount');
$route->add('/portal/account/verification', 'Portal', 'verifyAccount');
$route->add('/portal/history/transfer', 'Portal', 'historyTransfer');
$route->add('/portal/history/withdraw', 'Portal', 'historyWithdraw');
$route->add('/portal/action/withdraw', 'Portal', 'sendWithdraw');
$route->add('/portal/action/deposit', 'Portal', 'sendDeposit');


//API Routes
$route->add('/api/bill/nwsc/area', 'API', 'nwsc_Area');
$route->add('/api/bill/nwsc/details', 'API', 'nwsc_Details');
$route->add('/api/bill/yaka/details', 'API', 'nwsc_Details');
$route->add('/api/bill/ura/details', 'API', 'ura_Details');
$route->add('/api/bill/paytv/providers', 'API', 'tv_providers');
$route->add('/api/bill/paytv/tariff', 'API', 'tv_tariff');
$route->add('/api/bill/paytv/details', 'API', 'paytv_Details');

$route->add('/api/history/transfer', 'API', 'transferHistory');
$route->add('/api/history/withdraw', 'API', 'withdrawHistory');
$route->add('/api/history/utility', 'API', 'utilityHistory');

$route->add('/api/payment/momo', 'API', 'Payment_MoMo');
$route->add('/api/payment/tranfer_status', 'API', 'cron_transfer_daily');

$route->add('/api/payment/transaction_status', 'API', 'cron_daily');
$route->add('/api/payment/card', 'API', 'Payment_Card');
$route->add('/api/payment/withdraw', 'API', 'Payment_Withdraw');
$route->add('/api/payment/deposit', 'API', 'Payment_Deposit');
$route->add('/api/webhook', 'API', 'IPN_Webhook');


$route->add('/check_notification', 'API', 'check_notifications');

//Test Phase links
$route->add('/test/callback', 'Home', 'TestCallback');
$route->add('/test/gethook', 'Home', 'Testhook');
$route->submit();

?>