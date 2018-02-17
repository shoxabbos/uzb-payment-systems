<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('BILLING_MODULE', 1);

include './payme/AbstractPayme.php';
include './payme/DbTransactionProvider.php';


class Payme extends AbstractPayme
{
    /**
     * List fields
     *
     * @var array $accounts
     */
    protected $accounts = ["order"];


    /**
     * Table of transactions
     *
     * @var string $tableName
     */
    protected $tableName = "payme_uz";


    /**
     * Min summ
     *
     * @var int $minSum
     */
    protected $minSum = 1000;


    /**
     * Max summ
     *
     * @var int $maxSum
     */
    protected $maxSum = 100000;


    /**
     * Transaction timeout
     *
     * @var int $timeout
     */
    protected $timeout = 6000 * 1000;


    /**
     * @var bool $canCancelSuccessTransaction
     */
    protected $canCancelSuccessTransaction = false;

    /**
     * User primaryKey
     *
     * @var string $userKey
     */
    protected $userKey = "order";


    private $config = [
        'merchant' => 'merchant-id',
        'login' => 'Paycom',
        'key' => 'merchant-key',
        'test_key' => 'merchant-key',
    ];


    /**
     * Pdo object
     * @var PDO
     */
    private $pdo;

    /**
     * Wallet constructor.
     * @param string $request JSON request
     */
    public function __construct($request)
    {
        file_put_contents('./log.txt', $request);

        $host = 'db-host';
        $db   = 'db-name';
        $user = 'db-user';
        $pass = 'db-password';
        $charset = 'utf8';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $opt);

        parent::__construct($request, new DbTransactionProvider($this->tableName, $pdo));

        $this->pdo = $pdo;

    }

    /**
     * @return bool
     */
    public function auth()
    {
        $headers = getallheaders();
        if (!$headers ||
            !isset($headers['Authorization']) ||
            !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) ||
            base64_decode($matches[1]) != $this->config['login'] . ":" . $this->config['key']
        ) {
            return false;
        }

        return true;
    }

    /**
     * Transaksiya otkazib bolish imkoniyatini tekshiradi
     *
     * @return array
     */
    protected function checkPerformTransaction()
    {
        // check auth
        if (!$this->auth()) {
            return $this->response->error(PaymeResponse::AUTH_ERROR);
        }

        // Check account fields
        if (!$this->request->hasAccounts($this->accounts) || !$this->request->hasParam(["amount"])) {
            return $this->response->error(PaymeResponse::JSON_RPC_ERROR);
        }

        // Get vars
        $accounts = $this->request->getParam('account');
        $amount = $this->getAmount($this->request->getParam("amount"));
        

        // check order
        // Ushbu proverka sestemaga bogliq zakaz yoki polzvatel pul qabul qila oladimi yoki yuq ?
        $invoice = $this->getInvoice($accounts['order']);
        if (!$invoice) {
            return $this->response->error(PaymeResponse::USER_NOT_FOUND);
        }


        // Check amount
        if (!$this->isValidAmount($invoice['invoice_pay'], $amount)) {
            return $this->response->error(PaymeResponse::WRONG_AMOUNT);
        }

        // Success
        return $this->response->successCheckPerformTransaction();
    }


    /**
     * Transaksiya yaratadi
     *
     * @return array
     */
    protected function createTransaction()
    {
        // check auth
        if (!$this->auth()) {
            return $this->response->error(PaymeResponse::AUTH_ERROR);
        }

        // Check account fields
        if (!$this->request->hasAccounts($this->accounts) || !$this->request->hasParam(["amount", "time", "id"])) {
            return $this->response->error(PaymeResponse::JSON_RPC_ERROR);
        }

        $accounts = $this->request->getParam('account');
        $amount = $this->getAmount($this->request->getParam("amount"));
        $transId = $this->request->getParam("id");
        $time = $this->request->getParam("time");


        // get transaction
        $trans = $this->provider->getByTransId($transId);

        if ($trans) {
            // check state
            if ($trans['state'] != 1) {
                return $this->response->error(PaymeResponse::CANT_PERFORM_TRANS);
            }

            // check timeout
            if (!$this->checkTimeout($trans['create_time'])) {
                $this->provider->update($transId, [
                    "state" => -1,
                    "reason" => 4
                ]);

                return $this->response->error(PaymeResponse::CANT_PERFORM_TRANS, [
                    "uz" => "Vaqt tugashi o'tdi",
                    "ru" => "Тайм-аут прошел",
                    "en" => "Timeout passed"
                ]);
            }

            return $this->response->successCreateTransaction(
                $trans['create_time'],
                $trans['id'],
                $trans['state']
            );
        }


        // check perform transaction
        // check order
        $invoice = $this->getInvoice($accounts[$this->userKey]);
        if (!$invoice) {
            return $this->response->error(PaymeResponse::USER_NOT_FOUND);
        }

        // Check amount
        if (!$this->isValidAmount($invoice['invoice_pay'], $amount)) {
            return $this->response->error(PaymeResponse::WRONG_AMOUNT);
        }

        // check order status
        $trans = $this->provider->getByOwnerId($accounts[$this->userKey]);
        if ($trans && $trans['state'] == 1) {
            return $this->response->error(PaymeResponse::PENDING_PAYMENT);
        }

        // Add new transaction
        try {
            $this->provider->insert([
                'transaction' => $transId,
                'payme_time' => $time,
                'amount' => $amount,
                'state' => 1,
                'create_time' => $this->microtime(),
                'owner_id' => $accounts[$this->userKey],
            ]);

            $trans = $this->provider->getByTransId($transId);

            return $this->response->successCreateTransaction($trans['create_time'], $trans['id'], $trans['state']);

        } catch (\Exception $e) {
            return $this->response->error(PaymeResponse::SYSTEM_ERROR);
        }
    }


    /**
     * Transaksiyani utqazish va foydalanuvchi hisobiga pul otqazish
     *
     * @return array
     */
    protected function performTransaction()
    {
        // check auth
        if (!$this->auth()) {
            return $this->response->error(PaymeResponse::AUTH_ERROR);
        }

        // Check fields
        if (!$this->request->hasParam(["id"])) {
            return $this->response->error(PaymeResponse::JSON_RPC_ERROR);
        }

        // Search by id
        $transId = $this->request->getParam('id');
        $trans = $this->provider->getByTransId($transId);

        if (!$trans) {
            return $this->response->error(PaymeResponse::TRANS_NOT_FOUND);
        }

        if ($trans['state'] != 1) {
            if ($trans['state'] == 2) {
                return $this->response->successPerformTransaction($trans['state'], $trans['perform_time'], $trans['id']);
            } else {
                return $this->response->error(PaymeResponse::CANT_PERFORM_TRANS);
            }
        }

        // Check timeout
        if (!$this->checkTimeout($trans['create_time'])) {
            $this->provider->update($transId, [
                "state" => -1,
                "reason" => 4
            ]);

            return $this->response->error(PaymeResponse::CANT_PERFORM_TRANS, [
                "uz" => "Vaqt tugashi o'tdi",
                "ru" => "Тайм-аут прошел",
                "en" => "Timeout passed"
            ]);
        }

        try {
            $this->fillUpBalance($trans['owner_id'], $trans['amount']);

            $performTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => 2,
                "perform_time" => $performTime
            ]);

            return $this->response->successPerformTransaction(2, $performTime, $trans['id']);
        } catch (\Exception $e) {
            return $this->response->error(PaymeResponse::CANT_PERFORM_TRANS);
        }
    }


    /**
     * Transaksiyani statusini tekshiradi
     *
     * @return array
     */
    protected function checkTransaction()
    {
        // check auth
        if (!$this->auth()) {
            return $this->response->error(PaymeResponse::AUTH_ERROR);
        }

        // Check fields
        if (!$this->request->hasParam(["id"])) {
            return $this->response->error(PaymeResponse::JSON_RPC_ERROR);
        }

        $transId = $this->request->getParam("id");
        $trans = $this->provider->getByTransId($transId);

        if ($trans) {
            return $this->response->successCheckTransaction(
                $trans['create_time'],
                $trans['perform_time'],
                $trans['cancel_time'],
                $trans['id'],
                $trans['state'],
                $trans['reason']
            );
        } else {
            return $this->response->error(PaymeResponse::TRANS_NOT_FOUND);
        }
    }


    /**
     * Transaksiyani qaytarish va foydalanuvchi hisobidan yechib olish
     *
     * @return array
     */
    protected function cancelTransaction()
    {
        // check auth
        if (!$this->auth()) {
            return $this->response->error(PaymeResponse::AUTH_ERROR);
        }

        // Check fields
        if (!$this->request->hasParam(["id", "reason"])) {
            return $this->response->error(PaymeResponse::JSON_RPC_ERROR);
        }

        $transId = $this->request->getParam("id");
        $reason = $this->request->getParam("reason");
        $trans = $this->provider->getByTransId($transId);

        if (!$trans) {
            $this->response->error(PaymeResponse::TRANS_NOT_FOUND);
        }

        if ($trans['state'] == 1) {
            $cancelTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => -1,
                "cancel_time" => $cancelTime,
                "reason" => $reason
            ]);

            return $this->response->successCancelTransaction(-1, $cancelTime, $trans['id']);
        }


        if ($trans['state'] != 2) {
            return $this->response->successCancelTransaction($trans['state'], $trans['cancel_time'], $trans['id']);
        }

        try {
            $this->withdrawBalance($trans['owner_id'], $trans['amount']);

            $cancelTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => -2,
                "cancel_time" => $cancelTime,
                "reason" => $reason
            ]);

            return $this->response->successCancelTransaction(-2, $cancelTime, $trans['id']);
        } catch (\Exception $e) {
            return $this->response->error(PaymeResponse::CANT_CANCEL_TRANSACTION);
        }
    }


    /**
     * Hozircha bu metod hech narsa qilmaydi, lekin keyin albatta qilaman
     */
    public function getStatement()
    {
        // TODO: Implement GetStatement() method.
    }

    /**
     * Bu metod parolni uzgartirish uchun kk
     */
    protected function changePassword()
    {
        // TODO: Implement ChangePassword() method.
    }



    // helpers

    /**
     * Foydalanuvchi hisobiga pul otqazish
     *
     * @param $owner_id
     * @param $amount
     * @throws \Exception
     */
    private function fillUpBalance($owner_id, $amount)
    {
        // buyurtmani olamiz
        $invoice = $this->getInvoice($owner_id);
        if (!$invoice) {
            throw new \Exception("Can't find order");
        }

        // buyurtmani tolandi db belgilash
        $this->pdo->query("UPDATE `dle_billing_invoice` SET `invoice_date_pay` = '".time()."' WHERE invoice_id = {$owner_id}");
        $this->pdo->query("UPDATE `dle_users` SET `user_balance` = (user_balance + '{$amount}') WHERE name = '{$invoice['invoice_user_name']}'");
    }

    /**
     * Foydalanuvchi hisobidan pul yechish
     *
     * @param $owner_id
     * @param $amount
     * @throws \Exception
     */
    private function withdrawBalance($owner_id, $amount)
    {
        // buyurtmani olamiz
        $invoice = $this->getInvoice($owner_id);
        if (!$invoice) {
            throw new \Exception("Can't find order");
        }

        // buyurtmani tolandi db belgilash
        $this->pdo->query("UPDATE `dle_billing_invoice` SET `invoice_date_pay` = '0' WHERE invoice_id = {$owner_id}");
        $this->pdo->query("UPDATE `dle_users` SET `user_balance` = (user_balance - '{$amount}') WHERE name = '{$invoice['invoice_user_name']}'");
    }


    /**
     * Transaksiyani tekshiradi timeoutga qarab
     *
     * @param $created_time
     * @return bool
     */
    private function checkTimeout($created_time)
    {
        return $this->microtime() <= ($created_time + $this->timeout);
    }


    /**
     * Oddiy helper prosta akkount yoki zakazni olish uchun
     *
     * @param $id
     * @return null|array
     */
    private function getInvoice($id) {
        return $this->pdo->query("SELECT * FROM dle_billing_invoice WHERE invoice_id = '{$id}' ")
            ->fetch();
    }

    /**
     * @param $clientAmount
     * @param $paymeAmount
     * @return bool
     */
    private function isValidAmount($clientAmount, $paymeAmount) {
        return $clientAmount == $paymeAmount;
    }

    /**
     * @param $amount
     * @return float|int
     */
    private function getAmount($amount) {
        return $amount / 100;
    }
}



$data = file_get_contents("php://input");

if ($data) {
    $response = (new Payme($data))->response();

    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response);
}
