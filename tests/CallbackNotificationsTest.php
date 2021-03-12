<?php

declare(strict_types=1);

namespace Vlsv\SberBankApi\Tests;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Vlsv\SberBankApi\CallbackNotification;

class CallbackNotificationsTest extends TestCase
{
    private string $mdOrder = '72318777-5zfg-782c-bk02-xxxxxxxx8dx5';
    private string $orderNumber = 'ZX-987654321';

    private array $approved_successfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'approved', 'status' => '1'];
    private array $approved_unsuccessfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'approved', 'status' => '0'];
    private array $declinedByTimeout_successfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'declinedByTimeout', 'status' => '1'];
    private array $declinedByTimeout_unsuccessfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'declinedByTimeout', 'status' => '0'];
    private array $deposited_successfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'deposited', 'status' => '1'];
    private array $deposited_unsuccessfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'deposited', 'status' => '0'];
    private array $reversed_successfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'reversed', 'status' => '1'];
    private array $reversed_unsuccessfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'reversed', 'status' => '0'];
    private array $refunded_successfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'refunded', 'status' => '1'];
    private array $refunded_unsuccessfully = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'refunded', 'status' => '0'];
    private array $declinedByTimeout = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'declinedByTimeout', 'status' => '0'];
    private array $approved = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'approved', 'status' => '0'];
    private array $reversed = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'reversed', 'status' => '0'];
    private array $refunded = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'refunded', 'status' => '0'];

    private array $unknown_operation = ['orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'unknown', 'status' => '0'];

    private array $deposited_successfully_custom_field = ['customField' => 'some-value', 'orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'deposited', 'status' => '1'];

    private array $deposited_successfully_with_checksum = ['checksum' => '38f189e2b24b34984adef6e8bf2f81b475b0a9348afe5de082e2b5232b2d5e73', 'orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'deposited', 'status' => '1'];
    private string $check_string = 'mdOrder;72318777-5zfg-782c-bk02-xxxxxxxx8dx5;operation;deposited;orderNumber;ZX-987654321;status;1;';
    private array $deposited_successfully_with_checksum_custom_field = ['amount' => '5', 'checksum' => '38f189e2b24b34984adef6e8bf2f81b475b0a9348afe5de082e2b5232b2d5e73', 'orderNumber' => 'ZX-987654321', 'mdOrder' => '72318777-5zfg-782c-bk02-xxxxxxxx8dx5', 'operation' => 'deposited', 'status' => '1'];
    private string $check_string_custom_field = 'amount;5;mdOrder;72318777-5zfg-782c-bk02-xxxxxxxx8dx5;operation;deposited;orderNumber;ZX-987654321;status;1;';

    private string $test_symmetric_private_key = 'B07BAA3F9C809098ACBB462618A93275';

    private array $asymmetric_key_test_payload_correct = ['amount' => '35000099', 'mdOrder' => '12b59da8-f68f-7c8d-12b5-9da8000826ea', 'operation' => 'deposited', 'status' => '1', 'checksum' => '9524FD765FB1BABFB1F42E4BC6EF5A4B07BAA3F9C809098ACBB462618A9327539F975FEDB4CF6EC1556FF88BA74774342AF4F5B51BA63903BE9647C670EBD962467282955BD1D57B16935C956864526810870CD32967845EBABE1C6565C03F94FF66907CEDB54669A1C74AC1AD6E39B67FA7EF6D305A007A474F03B80FD6C965656BEAA74E09BB1189F4B32E622C903DC52843C454B7ACF76D6F76324C27767DE2FF6E7217716C19C530CA7551DB58268CC815638C30F3BCA3270E1FD44F63C14974B108E65C20638ECE2F2D752F32742FFC5077415102706FA5235D310D4948A780B08D1B75C8983F22F211DFCBF14435F262ADDA6A97BFEB6D332C3D51010B'];
    private array $asymmetric_key_test_payload_wrong = ['amount' => '935000099', 'mdOrder' => '12b59da8-f68f-7c8d-12b5-9da8000826ea', 'operation' => 'deposited', 'status' => '1', 'checksum' => '9524FD765FB1BABFB1F42E4BC6EF5A4B07BAA3F9C809098ACBB462618A9327539F975FEDB4CF6EC1556FF88BA74774342AF4F5B51BA63903BE9647C670EBD962467282955BD1D57B16935C956864526810870CD32967845EBABE1C6565C03F94FF66907CEDB54669A1C74AC1AD6E39B67FA7EF6D305A007A474F03B80FD6C965656BEAA74E09BB1189F4B32E622C903DC52843C454B7ACF76D6F76324C27767DE2FF6E7217716C19C530CA7551DB58268CC815638C30F3BCA3270E1FD44F63C14974B108E65C20638ECE2F2D752F32742FFC5077415102706FA5235D310D4948A780B08D1B75C8983F22F211DFCBF14435F262ADDA6A97BFEB6D332C3D51010B'];
    private string $asymmetric_public_key = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwtuGKbQ4WmfdV1gjWWys
5jyHKTWXnxX3zVa5/Cx5aKwJpOsjrXnHh6l8bOPQ6Sgj3iSeKJ9plZ3i7rPjkfmw
qUOJ1eLU5NvGkVjOgyi11aUKgEKwS5Iq5HZvXmPLzu+U22EUCTQwjBqnE/Wf0hnI
wYABDgc0fJeJJAHYHMBcJXTuxF8DmDf4DpbLrQ2bpGaCPKcX+04POS4zVLVCHF6N
6gYtM7U2QXYcTMTGsAvmIqSj1vddGwvNGeeUVoPbo6enMBbvZgjN5p6j3ItTziMb
Vba3m/u7bU1dOG2/79UpGAGR10qEFHiOqS6WpO7CuIR2tL9EznXRc7D9JZKwGfoY
/QIDAQAB
-----END PUBLIC KEY-----
EOD;

    public function dataProviderIncorrectStructure(): array
    {
        return [
            [['orderNumber' => '', 'mdOrder' => $this->mdOrder, 'operation' => 'unknown', 'status' => '0']],
            [['orderNumber' => '', 'mdOrder' => $this->mdOrder, 'operation' => 'deposited', 'status' => '0']],
            [['order' => '', 'mdOrder' => $this->mdOrder, 'operation' => 'unknown', 'status' => '0']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => '', 'operation' => 'deposited', 'status' => '1']],
            [['orderNumber' => $this->orderNumber, 'md' => $this->mdOrder, 'operation' => 'deposited', 'status' => '1']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'operation' => '', 'status' => '1']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'wrong' => 'deposited', 'status' => '1']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'operation' => 'deposited', 'stts' => '1']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'operation' => 'deposited', 'status' => '']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'operation' => 'deposited', 'status' => 'wrong_value']],
            [['orderNumber' => $this->orderNumber, 'mdOrder' => $this->mdOrder, 'operation' => 'deposited', 'status' => '2']],
        ];
    }

    /**
     * @dataProvider dataProviderIncorrectStructure
     * @param $payload
     */
    public function testIncorrectStructureException($payload)
    {
        $exception = 'unset';

        try {
            new CallbackNotification($payload);
        } catch (UnexpectedValueException $e) {
            $exception = $e->getMessage();
        }

        self::assertEquals('incorrect structure', $exception);
    }

    public function testUnknownOperationException()
    {
        $exception = 'unset';

        try {
            new CallbackNotification($this->unknown_operation);
        } catch (UnexpectedValueException $e) {
            $exception = $e->getMessage();
        }

        self::assertEquals('unknown operation', $exception);
    }

    public function testGetMdOrder()
    {
        $notification = new CallbackNotification($this->deposited_successfully);

        self::assertEquals($this->mdOrder, $notification->getMdOrder());
    }

    public function testGetOrderNumber()
    {
        $notification = new CallbackNotification($this->deposited_successfully);

        self::assertEquals($this->orderNumber, $notification->getOrderNumber());
    }

    public function testGetOperation()
    {
        $notification = new CallbackNotification($this->deposited_successfully);

        self::assertEquals('deposited', $notification->getOperation());

        $notification = new CallbackNotification($this->declinedByTimeout);

        self::assertEquals('declinedByTimeout', $notification->getOperation());

        $notification = new CallbackNotification($this->approved);

        self::assertEquals('approved', $notification->getOperation());

        $notification = new CallbackNotification($this->reversed);

        self::assertEquals('reversed', $notification->getOperation());

        $notification = new CallbackNotification($this->refunded);

        self::assertEquals('refunded', $notification->getOperation());
    }

    public function testGetStatusTrueFalse()
    {
        $notification = new CallbackNotification($this->deposited_successfully);

        self::assertTrue($notification->getStatus());

        $notification = new CallbackNotification($this->deposited_unsuccessfully);

        self::assertFalse($notification->getStatus());
    }

    public function testGetValueOrException()
    {
        $notification = new CallbackNotification($this->deposited_successfully_custom_field);
        $exception_message = 'unset';

        self::assertEquals('some-value', $notification->getValueOrException('customField'));

        try {
            $notification->getValueOrException('anotherField');
        } catch (UnexpectedValueException $e) {
            $exception_message = $e->getMessage();
        }

        self::assertEquals('index does not exist', $exception_message);
    }

    public function testGetCheckString()
    {
        $notification = new CallbackNotification($this->deposited_successfully_with_checksum);

        self::assertEquals($this->check_string, $notification->getCheckString());

        $notification = new CallbackNotification($this->deposited_successfully_with_checksum_custom_field);

        self::assertEquals($this->check_string_custom_field, $notification->getCheckString());
    }

    public function testIsSymmetricKeyValidationSuccessfulEmptyKeyException()
    {
        $symmetric_private_key = '';
        $exception_message = 'unset';

        $notification = new CallbackNotification($this->deposited_successfully_with_checksum_custom_field);

        try {
            $notification->isSymmetricKeyValidationSuccessful($symmetric_private_key);
        } catch (UnexpectedValueException $e) {
            $exception_message = $e->getMessage();
        }

        self::assertEquals('empty symmetric_private_key', $exception_message);
    }

    public function testIsSymmetricKeyValidationSuccessful()
    {
        $hmac = hash_hmac('sha256', $this->check_string_custom_field, $this->test_symmetric_private_key);

        $this->deposited_successfully_with_checksum_custom_field['checksum'] = $hmac;

        $notification = new CallbackNotification($this->deposited_successfully_with_checksum_custom_field);

        self::assertTrue($notification->isSymmetricKeyValidationSuccessful($this->test_symmetric_private_key));
    }

    public function testIsAsymmetricKeyValidationSuccessfulEmptyKeyException()
    {
        $asymmetric_public_key = '';
        $exception_message = 'unset';

        $notification = new CallbackNotification($this->deposited_successfully, false);

        try {
            $notification->isAsymmetricKeyValidationSuccessful($asymmetric_public_key);
        } catch (UnexpectedValueException $e) {
            $exception_message = $e->getMessage();
        }

        self::assertEquals('empty asymmetric_public_key', $exception_message);
    }

    public function testIsAsymmetricKeyValidationSuccessful()
    {
        $notification = new CallbackNotification($this->asymmetric_key_test_payload_correct, false);

        self::assertTrue($notification->isAsymmetricKeyValidationSuccessful($this->asymmetric_public_key));

        $notification = new CallbackNotification($this->asymmetric_key_test_payload_wrong, false);

        self::assertFalse($notification->isAsymmetricKeyValidationSuccessful($this->asymmetric_public_key));
    }

    public function testIsApproved()
    {
        $notification = new CallbackNotification($this->approved_successfully);

        self::assertTrue($notification->isApproved());

        $notification = new CallbackNotification($this->approved_unsuccessfully);

        self::assertFalse($notification->isApproved());
    }

    public function testIsDeclinedByTimeout()
    {
        $notification = new CallbackNotification($this->declinedByTimeout_successfully);

        self::assertTrue($notification->isDeclinedByTimeout());

        $notification = new CallbackNotification($this->declinedByTimeout_unsuccessfully);

        self::assertTrue($notification->isDeclinedByTimeout());
    }

    public function testIsDeposited()
    {
        $notification = new CallbackNotification($this->deposited_successfully);

        self::assertTrue($notification->isDeposited());

        $notification = new CallbackNotification($this->deposited_unsuccessfully);

        self::assertFalse($notification->isDeposited());
    }

    public function testIsReversed()
    {
        $notification = new CallbackNotification($this->reversed_successfully);

        self::assertTrue($notification->isReversed());

        $notification = new CallbackNotification($this->reversed_unsuccessfully);

        self::assertFalse($notification->isReversed());
    }

    public function testIsRefunded()
    {
        $notification = new CallbackNotification($this->refunded_successfully);

        self::assertTrue($notification->isRefunded());

        $notification = new CallbackNotification($this->refunded_unsuccessfully);

        self::assertFalse($notification->isRefunded());
    }
}