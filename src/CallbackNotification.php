<?php

declare(strict_types=1);

namespace Vlsv\SberBankApi;

use UnexpectedValueException;

/**
 * Уведомления обратного вызова
 *
 * @link https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start
 * @package Vlsv\SberBankApi
 */
class CallbackNotification
{
    private $payload;

    const OPERATION = [
        'approved',
        'declinedByTimeout',
        'deposited',
        'reversed',
        'refunded'
    ];

    public function __construct(
        string $payload,
        bool $check_structure = true
    )
    {
        $payload = json_decode($payload);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new UnexpectedValueException('incorrect json');
        }

        if ($check_structure && self::isStructureIncorrect($payload)) {
            throw new UnexpectedValueException('incorrect structure');
        }

        if (!in_array($payload->operation, self::OPERATION)) {
            throw new UnexpectedValueException('unknown operation');
        }

        $this->payload = $payload;
    }

    /**
     * Уникальный номер заказа в системе платёжного шлюза
     *
     * @return mixed
     */
    public function getMdOrder(): string
    {
        return $this->payload->mdOrder;
    }

    /**
     * Уникальный номер (идентификатор) заказа в системе продавца
     *
     * @return string
     */
    public function getOrderNumber(): string
    {
        return $this->payload->orderNumber;
    }

    /**
     * Аутентификационный код, или контрольная сумма, полученная из набора параметров
     *
     * @return string
     */
    public function getChecksum(): string
    {
        return $this->payload->checksum ?? '';
    }

    /**
     * Тип операции, о которой пришло уведомление:
     * - approved - операция удержания (холдирования) суммы;
     * - declinedByTimeout - операция отклонения заказа по истечении его времени жизни;
     * - deposited - операция завершения;
     * - reversed - операция отмены;
     * - refunded - операция возврата.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->payload->operation;
    }

    /**
     * Индикатор успешности операции, указанной в параметре operation:
     * - 1 - операция прошла успешно;
     * - 0 - операция завершилась ошибкой.
     *
     */
    public function getStatus(): bool
    {
        return (bool)$this->payload->status;
    }

    /**
     * Переменная определенная настройками аккаунта Сбербанк
     *
     * @param $index
     * @return false
     */
    public function getValueOrException($index)
    {
        if (!isset($this->payload->$index) || !$this->payload->$index) {
            throw new UnexpectedValueException('index does not exist');
        }

        return $this->payload->$index ?? false;
    }

    public function getCheckString(): string
    {
        $check_string = '';
        $payload = (array)$this->payload;
        unset($payload['checksum']);
        ksort($payload);

        foreach ($payload as $key => $value) {
            $check_string .= $key . ';' . $value . ';';
        }

        return $check_string;
    }

    /**
     * Валидация запроса симметричным ключом
     *
     * @param string $symmetric_private_key
     * @return bool
     */
    public function isSymmetricKeyValidationSuccessful(string $symmetric_private_key): bool
    {
        if (empty($symmetric_private_key)) {
            throw new UnexpectedValueException('empty symmetric_private_key');
        }

        $hmac = hash_hmac('sha256', $this->getCheckString(), $symmetric_private_key);

        return $hmac === $this->getChecksum();
    }

    /**
     * Валидация запроса асимметричным ключом
     *
     * @param string $asymmetric_public_key
     * @return bool
     */
    public function isAsymmetricKeyValidationSuccessful(string $asymmetric_public_key): bool
    {
        if (empty($asymmetric_public_key)) {
            throw new UnexpectedValueException('empty asymmetric_public_key');
        }

        $binary_signature = hex2bin(strtolower($this->getChecksum()));
        $is_verify = openssl_verify(
            $this->getCheckString(),
            $binary_signature,
            $asymmetric_public_key,
            OPENSSL_ALGO_SHA512
        );

        return (bool)$is_verify;
    }

    /**
     * Проверка корректности структуры переданного json
     *
     * @param \stdClass $payload
     * @return bool
     */
    private function isStructureIncorrect(\stdClass $payload): bool
    {
        return !isset($payload->mdOrder) ||
            !$payload->mdOrder ||
            !isset($payload->orderNumber) ||
            !$payload->orderNumber ||
            !isset($payload->operation) ||
            !$payload->operation ||
            !isset($payload->status) ||
            ($payload->status != '0' && $payload->status != '1');
    }

    /**
     * Операция удержания (холдирования) суммы
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->getOperation() === 'approved' && $this->getStatus();
    }

    /**
     * Операция отклонения заказа по истечении его времени жизни
     *
     * @note Для данной операции статус не имеет значения
     * @return bool
     */
    public function isDeclinedByTimeout(): bool
    {
        return $this->getOperation() === 'declinedByTimeout';
    }

    /**
     * Операция завершения
     *
     * @return bool
     */
    public function isDeposited(): bool
    {
        return $this->getOperation() === 'deposited' && $this->getStatus();
    }

    /**
     * Операция отмены
     *
     * @return bool
     */
    public function isReversed(): bool
    {
        return $this->getOperation() === 'reversed' && $this->getStatus();
    }

    /**
     * Операция возврата
     *
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->getOperation() === 'refunded' && $this->getStatus();
    }
}