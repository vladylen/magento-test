<?php

class PhoneManager
{
    const NONAME = 'noname';

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
     * @param string $name
     *
     * @return bool
     * @throws ATSException
     */
    public function organizeCall($phoneNumber, $name)
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
                    $name . $phoneNumber,
                    1,
                    30000,
                    null,
                    uniqid('call', true)
                );

                $ast->logout();
                $ast->close();

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

    /**
     * @param string $name
     *
     * @return string
     */
    public function getValidName($name)
    {
        $validName = $this->translit($name);
        $validName = empty($validName) ? self::NONAME : $validName;

        return $validName;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    function translit($string)
    {
        $replace=[
            "'"=>"",
            "`"=>"",
            " "=>"",
            "а"=>"a","А"=>"A",
            "б"=>"b","Б"=>"B",
            "в"=>"v","В"=>"V",
            "г"=>"g","Г"=>"G",
            "д"=>"d","Д"=>"D",
            "е"=>"e","Е"=>"E",
            "ё"=>"e","Ё"=>"E",
            "ж"=>"zh","Ж"=>"ZH",
            "з"=>"z","З"=>"Z",
            "и"=>"i","И"=>"I",
            "й"=>"y","Й"=>"Y",
            "к"=>"k","К"=>"K",
            "л"=>"l","Л"=>"L",
            "м"=>"m","М"=>"M",
            "н"=>"n","Н"=>"N",
            "о"=>"o","О"=>"O",
            "п"=>"p","П"=>"P",
            "р"=>"r","Р"=>"R",
            "с"=>"s","С"=>"S",
            "т"=>"t","Т"=>"T",
            "у"=>"u","У"=>"U",
            "ф"=>"f","Ф"=>"F",
            "х"=>"h","Х"=>"H",
            "ц"=>"c","Ц"=>"C",
            "ч"=>"ch","Ч"=>"CH",
            "ш"=>"sh","Ш"=>"SH",
            "щ"=>"sch","Щ"=>"SCH",
            "ъ"=>"","Ъ"=>"",
            "ы"=>"y","Ы"=>"Y",
            "ь"=>"","Ь"=>"",
            "э"=>"e","Э"=>"E",
            "ю"=>"yu","Ю"=>"UI",
            "я"=>"ya","Я"=>"YA",
            "і"=>"i","І"=>"I",
            "ї"=>"yi","Ї"=>"YI",
            "є"=>"e","Є"=>"E"
        ];

        return iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
    }
}

class InvalidPhoneNumberException extends \Exception
{

}

class ATSException extends \Exception
{

}
