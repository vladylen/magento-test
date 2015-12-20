<?php

class PhoneManager
{
    /**
     * @param $phone
     *
     * @return string
     * @throws InvalidPhoneNumberException
     */
    public function getValidPhoneNumber($phone)
    {
        $phone = preg_replace('/[^\d]/', '', $phone); //$str = "/^(?:\+|0{2})([0-9]){8,14}$/";

        if (strlen($phone) === 14 && substr($phone, 0, 5) === '00380') {
            $phone = substr($phone, 4);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '380') {
            $phone = substr($phone, 2);
        } elseif (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }

        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $isValid = true;
        } else {
            $isValid = false;
        }

        if ($isValid === false) {
            throw new InvalidPhoneNumberException('Incorrect phone number!');
        }

        return $phone;
    }

    /**
     * @param string $phoneNumber
     *
     * @return bool
     * @throws ATSException
     */
    public function organizeCall($phoneNumber)
    {
        try {
            require_once __DIR__ . "/../../../../../../lib/asterisk-php-manager/AsteriskManager.php";
            $config = require_once __DIR__ . "/../config/ATSConfig.php";

            $params = ['server' => $config['server'], 'port' => $config['port']];
            $ast    = new Net_AsteriskManager($params);

            try {
                $ast->connect();
            } catch (\Exception $e) {
                throw new ATSException('Unable to connect!');
            }

            try {
                $ast->login($config['login'], $config['password']);
            } catch (\Exception $e) {
                throw new ATSException('Unable to login!');
            }

            try {
                $result = $ast->originateCall(
                    $phoneNumber,
                    'SIP/voximplant',
                    'from-internal',
                    '111->' . $phoneNumber,
                    1,
                    30000,
                    null,
                    uniqid('call', true)
                );

                if (strpos($result, 'Success') === false) {
                    throw new ATSException('Unable to call!');
                }
            } catch (\Exception $e) {
                throw new ATSException('Unable to call!');
            }
        } catch (\Exception $e) {
            // some logging
            throw new ATSException('Call was not organized!');
        }

        return true;
    }
}

class InvalidPhoneNumberException extends \Exception
{

}

class ATSException extends \Exception
{

}
