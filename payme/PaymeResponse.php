<?php
/**
 * Klass dlya otpravki otvetov
 *
 * Class PaymeResponse
 * @package app\components\payme
 */
class PaymeResponse
{
    /**
     * @var PaymeRequest $request
     */
    protected $request;


    /**
     * Системная (внутренняя ошибка).
     */
    const SYSTEM_ERROR = -32400;


    /**
     * Auth error
     */
    const AUTH_ERROR = -32504;


    /**
     * Неверная сумма.
     */
    const WRONG_AMOUNT = -31001;


    /**
     * Ошибки связанные с неверным пользовательским вводом "account".
     * Например: введенный логин не найден
     */
    const USER_NOT_FOUND = -31050;


    /**
     * Передан неправильный JSON-RPC объект.
     */
    const JSON_RPC_ERROR = -32600;


    /**
     * Транзакция не найдена.
     */
    const TRANS_NOT_FOUND = -31003;


    /**
     * Запрашиваемый метод не найден.
     * Поле data содержит запрашиваемый метод.
     */
    const METHOD_NOT_FOUND = -32601;


    /**
     * Ошибка Парсинга JSON.
     * Запрос является не валидным JSON объектом
     */
    const JSON_PARSING_ERROR = -32700;


    /**
     * Невозможно выполнить данную операцию.
     */
    const CANT_PERFORM_TRANS = -31008;


    /**
     * Невозможно отменить транзакцию.
     * Товар или услуга предоставлена Потребителю в полном объеме.
     */
    const CANT_CANCEL_TRANSACTION = -31007;


    /**
     * В ожидании оплаты
     */
    const PENDING_PAYMENT = -31099;


    /**
     * PaymeResponse constructor.
     * @param PaymeRequest $request
     */
    public function __construct(PaymeRequest $request)
    {
        $this->request = $request;
    }



    /**
     * Create transaction uchun success otvet
     *
     * @param $createTime
     * @param $transaction
     * @param $state
     * @return array
     */
    public function successCreateTransaction($createTime, $transaction, $state)
    {
        return $this->success([
            'create_time' => $createTime,
            'transaction' => strval($transaction),
            'state' => $state,
        ]);
    }


    /**
     * Check perform transaction uchun success otvet
     *
     * @return array
     */
    public function successCheckPerformTransaction()
    {
        return $this->success([
            "allow" => true
        ]);
    }


    /**
     * Perform transaction uchun success otvet
     * Transaksiya qabul qilingandan keyin chaqiriladi
     *
     * @param $state
     * @param $performTime
     * @param $transaction
     * @return array
     */
    public function successPerformTransaction($state, $performTime, $transaction)
    {
        return $this->success([
            "state" => $state,
            "perform_time" => $performTime,
            "transaction" => strval($transaction),
        ]);
    }


    /**
     * Check transaction
     *
     * @param $createTime
     * @param $performTime
     * @param $cancelTime
     * @param $transaction
     * @param $state
     * @param $reason
     * @return array
     */
    public function successCheckTransaction($createTime, $performTime, $cancelTime, $transaction, $state, $reason)
    {
        return $this->success([
            "create_time" => $createTime,
            "perform_time" => $performTime,
            "cancel_time" => $cancelTime,
            "transaction" => strval($transaction),
            "state" => $state,
            "reason" => $reason
        ]);
    }


    /**
     * Transaksiya otmena bolganda shu method chaqiriladi
     *
     * @param $state
     * @param $cancelTime
     * @param $transaction
     * @return array
     */
    public function successCancelTransaction($state, $cancelTime, $transaction)
    {
        return $this->success([
            "state" => $state,
            "cancel_time" => $cancelTime,
            "transaction" => strval($transaction)
        ]);
    }


    /**
     * Umumiy JSON-RPC uchun success method
     * Vazifasi id va boshqa parametrlarni olib quyadi requestdan
     *
     * @param array $result
     * @return array
     */
    public function success (array $result) {
        return [
            "error" => null,
            "result" => $result,
            "id" => $this->request->id
        ];
    }


    /**
     * Oshibka bulganda shu method ishga tushadi
     * Klientga oshibkani qaytarish uchun
     *
     * @param $code
     * @param array $message
     * @param null $data
     * @return array
     */
    public function error ($code, $message = [], $data = null){

        if (empty($message)) {
            $message = $this->getErrorMessage($code);
        }

        return [
            'error' => [
                "code" => $code,
                "message" => $message,
                "data" => $data
            ],
            'result' => null,
            'id' => $this->request->id
        ];
    }


    /**
     * Default opisaniyalar oshibkalar uchun
     *
     * @param $code
     * @return array|mixed
     */
    private function getErrorMessage($code)
    {
        $messages = [
            self::SYSTEM_ERROR => [
                "uz" => "Ichki sestema hatoligi",
                "ru" => "Внутренняя ошибка сервера",
                "en" => "Internal server error"
            ],

            self::WRONG_AMOUNT => [
                "uz" => "Notug'ri summa.",
                "ru" => "Неверная сумма.",
                "en" => "Wrong amount.",
            ],

            self::USER_NOT_FOUND => [
                "uz" => "Foydalanuvchi topilmadi",
                "ru" => "Пользователь не найден",
                "en" => "User not found",
            ],

            self::JSON_RPC_ERROR => [
                "uz" => "Notog`ri JSON-RPC obyekt yuborilgan.",
                "ru" => "Передан неправильный JSON-RPC объект.",
                "en" => "Handed the wrong JSON-RPC object."
            ],

            self::TRANS_NOT_FOUND => [
                "uz" => "Transaction not found",
                "ru" => "Трансакция не найдена",
                "en" => "Transaksiya topilmadi"
            ],

            self::METHOD_NOT_FOUND => [
                "uz" => "Metod topilmadi",
                "ru" => "Запрашиваемый метод не найден.",
                "en" => "Method not found"
            ],

            self::JSON_PARSING_ERROR => [
                "uz" => "Json pars qilganda hatolik yuz berdi",
                "ru" => "Ошибка при парсинге JSON",
                "en" => "Error while parsing json"
            ],

            self::CANT_PERFORM_TRANS => [
                "uz" => "Bu operatsiyani bajarish mumkin emas",
                "ru" => "Невозможно выполнить данную операцию.",
                "en" => "Can't perform transaction",
            ],

            self::CANT_CANCEL_TRANSACTION => [
                "uz" => "Transaksiyani qayyarib bolmaydi",
                "ru" => "Невозможно отменить транзакцию",
                "en" => "You can not cancel the transaction"
            ],

            self::PENDING_PAYMENT => [
                "uz" => "To'lov kutilmoqda",
                "ru" => "В ожидании оплаты",
                "en" => "Pending payment"
            ],

            self::AUTH_ERROR => [
                "uz" => "Avtorizatsiyadan otishda xatolik",
                "ru" => "Auth error",
                "en" => "Auth error"
            ]
        ];

        return isset($messages[$code]) ? $messages[$code] : [];
    }

}