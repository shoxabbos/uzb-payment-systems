<?php
/**
 * Bu klass requestlarni obrabotka va validatsiya qilib
 * beradi
 */
class PaymeRequest
{
    /**
     * Id zaprosa
     *
     * @var int $id
     */
    public $id;


    /**
     * @var string $method
     */
    public $method;


    /**
     * @var array $params
     */
    public $params;


    /**
     * @var int $error
     */
    public $error;


    /**
     * @var array $errorMessage
     */
    public $errorMessage = [];


    /**
     * @var string $errorData
     */
    public $errorData;


    /**
     * Prinimaet json i parsit yevo
     * zapolnyaet klass
     *
     * PaymeRequest constructor.
     * @param $request
     */
    public function __construct($request)
    {
        try {
            $data = json_decode($request, true);

            if ($this->validateData($data)) {
                $this->id = $data['id'];
                $this->method = $data['method'];
                $this->params = $data['params'];
            }else {
                $this->error = PaymeResponse::JSON_RPC_ERROR;
            }
        } catch (\Exception $e) {
            $this->error = PaymeResponse::JSON_PARSING_ERROR;
        }
    }


    /**
     * Check param
     *
     * @param $param
     * @return bool
     */
    public function hasParam($param)
    {
        if (is_array($param)) {
            foreach ($param as $item) {
                if(!$this->hasParam($item)) return false;
            }
            return true;
        } else {
            return isset($this->params[$param]) && !empty($this->params[$param]);
        }
    }


    /**
     * Method dlya polucheniya parametri zaprosa
     *
     * @param $param
     * @param null $default
     * @return mixed|null
     */
    public function getParam($param, $default = null)
    {
        if($this->hasParam($param)) {
            return $this->params[$param];
        } elseif($default) {
            return $default;
        }

        return null;
    }


    /**
     * Check account fields
     *
     * @param array $accounts
     * @return bool
     */
    public function hasAccounts(array $accounts)
    {
        if (!$this->hasParam('account')) {
            return false;
        }

        foreach ($accounts as $account){
            if (!isset($this->params['account'][$account])) {
                return false;
            }
        }

        return true;
    }


    /**
     * Validate data
     *
     * @param array $data
     * @return bool
     */
    protected function validateData(array $data){
        if (!isset($data['params']) || empty($data['params'])) {
            return false;
        }

        if (!isset($data['id']) || empty($data['id'])) {
            return false;
        }

        if (!isset($data['method']) || empty($data['method'])) {
            return false;
        }

        return true;
    }


    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->error ? false : true;
    }


}