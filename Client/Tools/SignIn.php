<?php

namespace Sanf\Tools;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Sanf\Crypto\Crypto;
use Sanf\Enums\{
    Application,
    Platform,
};

class SignIn
{
    private int $phone;
    private string $auth;
    private string $code;
    private string $tmp_session;
    private int $phone_code_hash;
    private string $session;
    private string $server;
    private string $publicKey;
    private string $privateKey;
    private bool $set = false;
    private Platform $platform;
    private Application $application;

    public function __construct(string|null $session, Application $application = Application::Rubika)
    {
        $application->value == "Rubika" ? $servers = [
            "https://messengerg2c38.iranlms.ir",
            "https://messengerg2c152.iranlms.ir",
            "https://messengerg2c158.iranlms.ir"
        ] : $servers = [
            "https://shadmessenger25.iranlms.ir",
            "https://shadmessenger93.iranlms.ir",
            "https://shadmessenger130.iranlms.ir"
        ];
        while (true) {
            echo "enter your phone number : ";
            $phone_number = fgets(STDIN);
            $phone_number = self::checkPhoneNumber($phone_number);
            if ($phone_number === false) {
                echo "phone number invalid.\n";
                continue;
            } else {
                $this->phone = $phone_number;
                $key = Crypto::getKey();
                $this->session = $session;
                $this->publicKey = $key[0];
                $this->privateKey = $key[1];
                $this->application = $application;
                $this->platform = Platform::Android;
                $this->tmp_session = Crypto::generateRandomAuth();
                new Crypto($this->tmp_session, null, $this->platform);
                $this->server = $servers[array_rand($servers)];
                break;
            }
        }
        $sms_hash = $this->sendSMS($this->phone);
        if (
            !isset($sms_hash["phone_code_hash"])
        ) exit("error send sms code!");
        echo "enter code : ";

        $sms_code = trim(fgets(STDIN));
        $new_auth = $this->signIn($this->phone, $sms_hash["phone_code_hash"], $sms_code);
        if (!isset($new_auth["auth"])) exit("error get auth");
        $auth = $this->decryptAUTH($new_auth["auth"]);
        $this->registerDevice();
        file_put_contents("$session.sr", self::set_config(json_encode([
            "auth" => $auth[0],
            "key" => str_replace(
                ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\n"],
                "",
                $this->privateKey
            ),
            "app" => $this->application->value,
            "platform" => $this->platform->value,
            "phone" => $this->phone,
            "session_name" => $session,
            "time" => time() * 1000,
            "login_info" => php_uname()

        ]), "sanf/rush-client"));
    }

    private function sendSMS($phone = null, string $pass_key = null)
    {
        is_null($phone) ? $phone_number = $this->phone : $phone_number = self::checkPhoneNumber($phone);
        if ($phone_number === false) return "phone number invalid.";
        $json = [
            "phone_number" => $phone_number,
            "send_type" => "SMS"
        ];
        is_null($pass_key) ? null : $json["pass_key"] = $pass_key;
        return $this->setJsonTMP("sendCode", $json);
    }

    private function signIn($phone = null, $phone_code_hash = null, $code = null)
    {
        is_null($phone) ? $phone_number = $this->phone : $phone_number = self::checkPhoneNumber($phone);
        if ($phone_number === false) return "phone number invalid.";
        $json = [
            "phone_number" => $phone_number,
            "phone_code_hash" => is_null($phone_code_hash) ? $this->phone_code_hash : $phone_code_hash,
            "phone_code" => is_null($code) ? $this->code : $code,
            "public_key" => $this->publicKey,

        ];
        return $this->setJsonTMP("signIn", $json);
    }
    private function registerDevice()
    {
        $plt = $this->platform->value;
        $deviceInfo = Crypto::getRandomDeviceInfo();
        $json = [
            "token_type" => $plt == "Web" ? "Web" : "Firebase",
            "token" => "",
            "app_version" => $plt == "Web" ? "WB_4.4.17" : "MA_3.3.2",
            "lang_code" => "fa",
            "system_version" => $plt == "Web" ? "Linux" : $deviceInfo["sdkVersion"],
            "device_model" => $plt == "Web" ? "Chrome 131" : $deviceInfo["model"],
            "device_hash" => Crypto::getStringSalt()

        ];
        $plt !== "Web" ? $json["is_multi_account"] = false : null;
        $this->set = true;
        return $this->setJsonRe("registerDevice", $json);
    }

    private static function checkPhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) == 11 && $phone[0] == '0') {
            return '98' . substr($phone, 1);
        } elseif (strlen($phone) == 12 && strpos($phone, '98') === 0) {
            return substr($phone, 0, 2) . substr($phone, 2);
        } elseif (strlen($phone) == 13 && $phone[0] == '+') {
            return $phone;
        } else {
            return false;
        }
    }
    private function Post($data)
    {
        // exit(json_encode($data, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_UNICODE));
        $client = new GuzzleClient();
        $url =   $this->server;
        try {
            $response = $client->request('POST', $url, [
                'headers' => $this->platform->getClientPlatform($this->application)["header"],
                'body' => json_encode($data)
            ]);
            $Cipher = json_decode($response->getBody()->getContents(), true);
            $Cipher = isset($Cipher['data_enc']) ? $Cipher['data_enc'] : false;
            $Cipher = $Cipher ? json_decode(Crypto::decrypt($Cipher, $this->set), true) : null;
            return isset($Cipher["data"]) ? $Cipher["data"] : $Cipher;
        } catch (RequestException $e) {
            return null;
        }
    }
    private function setJsonTMP(string $method, array $input)
    {
        $changeJson = json_encode(array(
            "method" => $method,
            "input" => $input,
            "client" => $this->platform->getClientPlatform($this->application)["client"]
        ));
        $json = array(
            "tmp_session" => $this->tmp_session,
            "api_version" => "6",
            "data_enc" => Crypto::encrypt($changeJson, false),
        );
        $result = $this->Post($json);
        return is_null($result) ? ["status" => "ERROR_ACTION", "status_det" => "NOT_REGISTERED_RESULT_NULL"] : $result;
    }

    public function setJsonRe(string $method, array $input)
    {
        $auth = $this->auth;
        $key = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\n"], "", $this->privateKey);
        $key = $this->platform->value == "Web" ? base64_encode(json_encode(["d" => "-----BEGIN RSA PRIVATE KEY-----\n$key\n-----END RSA PRIVATE KEY-----"])) : $key;
        new Crypto($auth, $key, $this->platform);
        $changeJson = json_encode(array(
            "method" => $method,
            "input" => $input,
            "client" => $this->platform->getClientPlatform($this->application)["client"]
        ));
        $setAuth_enc = $this->platform->value == "Web" ? true : false;
        $json = array(
            "api_version" => "6",
            "auth" => $setAuth_enc ? $this->auth : Crypto::setAuth($this->auth),
            "data_enc" => Crypto::encrypt($changeJson, false),
            "sign" => Crypto::sign(Crypto::encrypt($changeJson, false))
        );
        $result = $this->Post($json);
        return is_null($result) ? ["status" => "ERROR_ACTION", "status_det" => "NOT_REGISTERED_RESULT_NULL", "resutl" => $result] : $result;
    }
    private function set_config($data, $key)
    {
        $key = hash('sha256', Crypto::setAuth($key), true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $text = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $text);
    }

    public function decryptAUTH($auth)
    {
        $privateKey = openssl_pkey_get_private($this->privateKey);
        if (!$privateKey) {
            return "Invalid private key.";
        }
        $dataBytes = base64_decode($auth);
        if ($dataBytes === false) {
            return "Invalid base64 encoded data.";
        }
        $decryptedBytes = '';
        if (!openssl_private_decrypt($dataBytes, $decryptedBytes, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            return "Decryption failed.";
        }
        $this->auth = $decryptedBytes;
        return [
            $decryptedBytes,
            $this->privateKey,
        ];
    }
}
