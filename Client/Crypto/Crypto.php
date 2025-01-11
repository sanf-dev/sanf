<?php

namespace Sanf\Crypto;

use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use Sanf\Enums\{
    Application,
    Platform
};

class Crypto
{
    private static $auth;
    private static $key;
    private static $iv;

    public function __construct(string|null $auth, string|null $privateKey, Platform $platform)
    {
        if (!is_null($auth) && !is_null($privateKey)) {
            if (strlen($auth) != 32) {
                exit(json_encode(["status" => "ERROR_ACTION", "status_det" => "the length of auth must be 32 digits"]));
            }
            $plt = $platform->value;
            if ($plt == "Android")
                self::$key = "-----BEGIN RSA PRIVATE KEY-----\n$privateKey\n-----END RSA PRIVATE KEY-----";
            elseif ($plt == "Web" || $plt == "PWA")
                self::$key = json_decode(base64_decode($privateKey), true)['d'];
        }
        self::$auth = $auth;
        self::$iv = str_repeat("\x00", 16);
        if ($privateKey) (new RSA())->loadKey($privateKey);
    }

    public static function setAuth(string $auth): string
    {
        return preg_replace_callback('/[a-zA-Z0-9]/', function ($matches) {
            $char = $matches[0];
            if (ctype_lower($char)) {
                return chr(((32 - (ord($char) - 97)) % 26) + 97);
            } elseif (ctype_upper($char)) {
                return chr(((29 - (ord($char) - 65)) % 26) + 65);
            } elseif (ctype_digit($char)) {
                return chr(((13 - (ord($char) - 48)) % 10) + 48);
            }
            return $char;
        }, $auth);
    }

    private static function secret(string $auth): string
    {
        $t = substr($auth, 0, 8);
        $i = substr($auth, 8, 8);
        $n = substr($auth, 16, 8) . $t . substr($auth, 24, 8) . $i;

        for ($s = 0, $length = strlen($n); $s < $length; $s++) {
            $char = $n[$s];
            $n[$s] = ctype_digit($char)
                ? chr((ord($char) - ord('0') + 5) % 10 + ord('0'))
                : chr((ord($char) - ord('a') + 9) % 26 + ord('a'));
        }

        return $n;
    }

    public static function encrypt(string $data, bool $setAuth = false)
    {
        $auth = $setAuth ? self::secret(self::setAuth(self::$auth)) : self::secret(self::$auth);
        $cipher = "aes-256-cbc";
        $encrypted = openssl_encrypt($data, $cipher, $auth, OPENSSL_RAW_DATA, self::$iv);
        return base64_encode($encrypted);
    }

    public static function decrypt(string $data, bool $setAuth = false): string
    {
        $auth = $setAuth ? self::secret(self::setAuth(self::$auth)) : self::secret(self::$auth);
        $aes = new AES(AES::MODE_CBC);
        $aes->setKey($auth);
        $aes->setIV(self::$iv);
        $decodedData = base64_decode($data);

        return $decodedData !== false ? $aes->decrypt($decodedData) : null;
    }

    public static function sign(string $data): string
    {
        $key = openssl_pkey_get_private(self::$key);
        if ($key === false) {
            return null;
        }

        $signature = '';
        if (openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return base64_encode($signature);
        }
        return null;
    }
    public static function getKey(): array
    {
        $keyGenerator = openssl_pkey_new(array(
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        openssl_pkey_export($keyGenerator, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($keyGenerator);
        $publicKey = $publicKeyDetails['key'];
        $publicKey = Crypto::setAuth(base64_encode($publicKey));
        $publicKey = chunk_split($publicKey, 64, "\n");
        return array($publicKey, $privateKey);
    }

    public static function generateRandomAuth($length = 32): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function getStringSalt(): string
    {
        $str = "250118664537361040511210153736";
        $length = strlen($str);
        $randomString = '';
        while (strlen($randomString) < 30) {
            $randomIndex = rand(0, $length - 1);
            $randomString .= $str[$randomIndex];
        }
        return $randomString;
    }

    public static function getRandomDeviceInfo()
    {
        $devices = [
            'Samsung' => [
                'Galaxy S21' => 'Android 11',
                'Galaxy S20' => 'Android 10',
                'Galaxy Note 20' => 'Android 10',
                'Galaxy A52' => 'Android 11',
                'Galaxy A72' => 'Android 11',
                'Galaxy S10' => 'Android 10',
                'Galaxy Z Flip' => 'Android 14',
                'Galaxy Z Fold 2' => 'Android 114',
                'Galaxy M31' => 'Android 11'
            ],
            'Google' => [
                'Pixel 6' => 'Android 15',
                'Pixel 5' => 'Android 15',
                'Pixel 4a' => 'Android 14',
                'Pixel 4' => 'Android 14',
                'Pixel 3a' => 'Android 13',
                'Pixel 3' => 'Android 12',
                'Pixel 2' => 'Android 11'
            ],
            'OnePlus' => [
                'OnePlus 9' => 'Android 11',
                'OnePlus 8' => 'Android 10',
                'OnePlus Nord' => 'Android 10',
                'OnePlus 7T' => 'Android 10',
                'OnePlus 7' => 'Android 10',
                'OnePlus 6T' => 'Android 9',
                'OnePlus 5' => 'Android 8'
            ],
            'Xiaomi' => [
                'Mi 11' => 'Android 14',
                'Redmi Note 10' => 'Android 13',
                'Poco X3' => 'Android 10',
                'Mi 10' => 'Android 10',
                'Redmi Note 9' => 'Android 10',
                'Poco F3' => 'Android 11',
                'Mi Note 10' => 'Android 10'
            ],
            'Sony' => [
                'Xperia 1 III' => 'Android 11',
                'Xperia 5 II' => 'Android 11',
                'Xperia 10' => 'Android 10',
                'Xperia L4' => 'Android 9',
                'Xperia 1 II' => 'Android 10'
            ],
            'Nokia' => [
                'Nokia 8.3' => 'Android 11',
                'Nokia 7.2' => 'Android 10',
                'Nokia 5.4' => 'Android 11',
                'Nokia 3.4' => 'Android 10',
                'Nokia 2.4' => 'Android 10'
            ],
            'Huawei' => [
                'P40 Pro' => 'Android 10',
                'Mate 40 Pro' => 'Android 10',
                'P30' => 'Android 10',
                'Mate 30' => 'Android 10',
                'Y9s' => 'Android 10'
            ],
            'Oppo' => [
                'Find X3 Pro' => 'Android 12',
                'Reno 5' => 'Android 11',
                'A94' => 'Android 11',
                'A74' => 'Android 12',
                'F19 Pro' => 'Android 11'
            ],
            'Vivo' => [
                'X60 Pro' => 'Android 11',
                'V21' => 'Android 11',
                'Y20' => 'Android 11',
                'Y31' => 'Android 11',
                'V15 Pro' => 'Android 10'
            ]
        ];

        $manufacturer = array_rand($devices);
        $model = array_rand($devices[$manufacturer]);
        $sdkVersion = $devices[$manufacturer][$model];

        return [
            'manufacturer' => $manufacturer,
            'model' => $model,
            'sdkVersion' => $sdkVersion,
        ];
    }
}
