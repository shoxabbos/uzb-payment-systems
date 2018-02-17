<?php
include __DIR__.'/PaymeRequest.php';
include __DIR__.'/PaymeResponse.php';


abstract class AbstractPayme
{
    /**
     * @var $request PaymeRequest
     */
    protected $request;


    /**
     * @var $response PaymeResponse
     */
    protected $response;


    /**
     * @var TransactionProvider
     */
    protected $provider;


    /**
     * @var array $accounts
     */
    protected $accounts = [];


    /**
     * @var array $error
     */
    protected $error;


    /**
     * AbstractPayme constructor.
     * @param $request
     * @param TransactionProvider $provider
     */
    public function __construct($request, TransactionProvider $provider)
    {
        $this->provider = $provider;
        $this->request = new PaymeRequest($request);
        $this->response = new PaymeResponse($this->request);
    }


    /**
     * Transaksiya otkazib bolish imkoniyatini tekshiradi
     *
     * @return array
     */
    abstract protected function checkPerformTransaction();


    /**
     * Transaksiya yaratadi
     *
     * @return array
     */
    abstract protected function createTransaction();


    /**
     * Transaksiyani utqazish va foydalanuvchi hisobiga pul otqazish
     *
     * @return array
     */
    abstract protected function performTransaction();


    /**
     * Transaksiyani qaytarish va foydalanuvchi hisobidan yechib olish
     *
     * @return array
     */
    abstract protected function cancelTransaction();


    /**
     * Transaksiyani statusini tekshiradi
     *
     * @return array
     */
    abstract protected function checkTransaction();


    /**
     * Hozircha bu metod hech narsa qilmaydi, lekin keyin albatta qilaman
     */
    abstract public function getStatement();


    /**
     * Bu metod parolni uzgartirish uchun kk
     */
    abstract protected function changePassword();


    /**
     * Mikrotaym olish uchun
     *
     * @return int
     */
    protected function microtime()
    {
        return (time() * 1000);
    }


    /**
     * Methodlarni chaqirib kerakli javobni serverga qaytaradi
     *
     * @return array
     */
    public function response()
    {
        $this->validate();

        if ($this->error) {
            $result = $this->response->error($this->error, $this->request->errorMessage, $this->request->errorData);
        } else {
            $result = $this->{$this->request->method}();
        }

        return $result;
    }


    /**
     * Validatsioya uchun metod
     */
    private function validate(){
        if (!$this->request->isValid()) {
            $this->error = $this->request->error;
        } elseif (!method_exists($this, $this->request->method)) {
            $this->error = PaymeResponse::METHOD_NOT_FOUND;
        }
    }


}