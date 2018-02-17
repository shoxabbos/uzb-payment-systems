<?php
/**
 * Bu kontract klass, agar shu package boshqa frameworkda ishlatilsa
 * Unda mana shu interferda bulishi kk
 *
 * Interface TransactionProvider
 * @package app\components\payme\contracts
 */
interface TransactionProvider
{

    /**
     * Find by transaction id from transactions table
     *
     * @param $transId
     * @return mixed
     */
    public function getByTransId($transId);


    /**
     * Find by owner_id id from transactions table
     *
     * @param $ownerId
     * @return mixed
     */
    public function getByOwnerId($ownerId);


    /**
     * Update transaction
     *
     * @param $transId
     * @param array $fields
     * @return mixed
     */
    public function update($transId, array $fields);


    /**
     * Add new transaction
     *
     * @param array $fields
     * @return mixed
     */
    public function insert(array $fields);

}