<?php

include __DIR__.'/TransactionProvider.php';

/**
 * Class DbTransactionProvider
 */
class DbTransactionProvider implements TransactionProvider
{

    public $db;

    /**
     * Bazadagi payme transaksiyalari turadigan tablitsa
     *
     * @var string $tableName
     */
    public $tableName;


    /**
     * DbTransactionProvider constructor.
     * @param $tableName
     * @param \PDO $pdo
     */
    public function __construct($tableName, \PDO $pdo)
    {
        $this->tableName = $tableName;
        $this->db = $pdo;
    }


    /**
     * Bazada tranzasiya topadi $transId buyicha
     *
     * @return array|bool
     * @param $transId
     */
    public function getByTransId($transId)
    {
        $trans = $this->db->query("SELECT * FROM {$this->tableName} WHERE transaction = '{$transId}' ")
            ->fetch();

        if ($trans) {
            $trans['create_time'] = intval($trans['create_time']);
            $trans['cancel_time'] = intval($trans['cancel_time']);
            $trans['perform_time'] = intval($trans['perform_time']);
            $trans['payme_time'] = intval($trans['payme_time']);
            $trans['state'] = is_null($trans['state']) ? null : intval($trans['state']);
            $trans['amount'] = is_null($trans['amount']) ? null : intval($trans['amount']);
            $trans['reason'] = is_null($trans['reason']) ? null : intval($trans['reason']);
        }

        return $trans;
    }


    /**
     * Tranzaksiyasi ownerId buyicha izlash
     * @param $ownerId
     * @return mixed
     */
    public function getByOwnerId($ownerId)
    {
        $trans = $this->db->query("SELECT * FROM {$this->tableName} WHERE owner_id = '{$ownerId}' ")
            ->fetch();

        if ($trans) {
            $trans['create_time'] = intval($trans['create_time']);
            $trans['cancel_time'] = intval($trans['cancel_time']);
            $trans['perform_time'] = intval($trans['perform_time']);
            $trans['payme_time'] = intval($trans['payme_time']);
            $trans['state'] = is_null($trans['state']) ? null : intval($trans['state']);
            $trans['amount'] = is_null($trans['amount']) ? null : intval($trans['amount']);
            $trans['reason'] = is_null($trans['reason']) ? null : intval($trans['reason']);
        }

        return $trans;
    }

    /**
     * Update transaction
     *
     * @param $transaction
     * @param array $fields
     * @return mixed|PDOStatement
     */
    public function update($transaction, array $fields)
    {
        $params = ''; $i = 0;
        foreach ($fields as $key => $field) {
            $params .= $i == 0 ? "`{$key}` = '{$field}'" : ", `{$key}` = '{$field}'";
            $i++;
        }

        $sql = "UPDATE `{$this->tableName}` SET {$params} WHERE transaction = '{$transaction}';";

        return $this->db->query($sql);
    }


    /**
     * Yangi transaksiya qushadi
     *
     * @param array $fields
     * @return int
     */
    public function insert(array $fields)
    {
        $sql = "INSERT INTO `{$this->tableName}` (transaction, payme_time, amount, state, create_time, owner_id) 
            VALUES ('{$fields['transaction']}', {$fields['payme_time']}, {$fields['amount']}, {$fields['state']}, {$fields['create_time']}, {$fields['owner_id']})";
        return $this->db->query($sql);
    }


}