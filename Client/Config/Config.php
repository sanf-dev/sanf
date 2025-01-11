<?php

namespace Sanf\Config;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use Sanf\Crypto\Crypto;
use Sanf\Enums\{
    Application,
    Platform
};

if (!file_exists("config.json")) require_once "./Client/tools/settings.php";
if (file_exists("config.json")) $setting = json_decode(file_get_contents("config.json"), true);

typing_text($setting["main_settings"]["message"]);
function typing_text($text, $delay = 10)
{
    for ($i = 0; $i < strlen($text); $i++) {
        echo $text[$i];
        usleep($delay * 1000);
    }
    echo PHP_EOL;
}

class Config
{
    private string $auth;
    private string $server;
    private array $setting;
    private int $request_count = 0;
    private Crypto $crypto;
    private Platform $platform;
    private Application $application;

    public function __construct($auth, $private_key, Platform $platform, Application $application)
    {
        $this->auth = $auth;
        $this->platform = $platform;
        $this->application = $application;
        $this->crypto = new Crypto($auth, $private_key, $platform);
        $this->setting = json_decode(file_get_contents("config.json"), true);

        if (
            empty($this->server)
        ) {
            $this->server = self::getDCs()["messenger"];
            $this->request_count = 0;
        }
    }

    public function setJson(string $method, array $input)
    {
        $setAuth_enc = $this->platform->value == "Web" || $this->platform->value == "PWA" ? true : false;
        $changeJson = array(
            "method" => $method,
            "input" => $input,
            "client" => $this->platform->getClientPlatfrom($this->application)["client"]
        );
        !$setAuth_enc ? $changeJson["is_background"] = false : null;
        $changeJson = json_encode($changeJson);
        $json = array(
            "api_version" => "6",
            "auth" => $setAuth_enc ? $this->auth : $this->crypto::setAuth($this->auth),
            "data_enc" => $this->crypto::encrypt($changeJson, $setAuth_enc),
            "sign" => $this->crypto::sign($this->crypto::encrypt($changeJson, $setAuth_enc))
        );
        $result = $this->Post($json);
        $result = isset($result['data']) ? $result['data'] : $result;
        return is_null($result) ? ["status" => "ERROR_ACTION", "status_det" => "NOT_REGISTERED_RNULL"] : $result;
        // return $json;
        // return $changeJson;
    }

    private static function cmd_run($run)
    {
        exec($run);
    }
    private function Post($data)
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(function ($retries, $request, $response, $reason) {
            return $retries < 3 && ($response && $response->getStatusCode() >= 500);
        }));
        $client = new GuzzleClient([
            'timeout' => 15,
            'verify' => false,
            'handler' => $stack
        ]);


        try {
            // $dc = $this->server["messenger"];
            $dc = self::getDCs()["messenger"];

            $response = $client->post($dc, [
                'json' => $data,
                'headers' => $this->platform->getClientPlatfrom($this->application)['header']
            ]);
            if (
                $this->request_count >= $this->setting["main_settings"]["request_count"]
            ) {
                $this->server = self::getDCs()["messenger"];
                $this->request_count = 0;
            }
            $this->request_count = $this->request_count + 1;
            $Cipher = json_decode($response->getBody()->getContents(), true);
            $Cipher = isset($Cipher['data_enc']) ? $Cipher['data_enc'] : false;
            $setAuth_enc = $this->platform->value  == "Web" || $this->platform->value == "PWA" ? true : false;
            $Cipher = $Cipher ? json_decode(Crypto::decrypt($Cipher, $setAuth_enc), true) : null;
            return isset($Cipher["data"]) ? $Cipher["data"] : $Cipher;
        } catch (RequestException $e) {
            if (file_exists("run.bat")) self::cmd_run("run.bat");
            else echo "The 'run.bat' file can be executed to restart after guzzel errors.\nNote: The 'run.bat' file will be executed directly on the terminal, so please be careful when writing the file.\nTo do this, create the 'run.bat' file in the bot's file path and upload the command inside the file\n";
            return null;
        }
    }

    public function getDCs()
    {
        $json = [
            "api_version" => "4",
            "method" => "getDCs",
            "client" => [
                "app_name" => "Main",
                "app_version" => "4.4.17",
                "platform" => "Web",
                "package" => "web.rubika.ir",
                "lang_code" => "fa"
            ]
        ];
        // $ressult =  json_decode(file_get_contents($this->platform->getClientPlatfrom($this->application)['url']), true);
        $ressult = $this->DCS($json);
        $messanger =  $ressult['data']['API'] ?? $ressult['data']['default_api_urls'] ?? false;
        $socket = $ressult['data']['socket'] ?? $ressult['data']['default_sockets'] ?? false;
        $storage = $ressult['data']['storage'] ?? false;

        if ($storage) $R_storage = $storage[array_rand($storage)];
        else $R_storage = $storage[array_rand([
            "https://messenger1050.iranlms.ir",
            "https://messenger1040.iranlms.ir",
            "https://messenger1035.iranlms.ir",
            "https://messenger1036.iranlms.ir",
            "https://messenger1037.iranlms.ir",
            "https://messenger1038.iranlms.ir",
            "https://messenger1039.iranlms.ir"
        ])];

        if ($messanger) $R_messanger = $messanger[array_rand($messanger)];
        else $R_messanger = $messanger[array_rand(["https://messengerg2c38.iranlms.ir", "https://messengerg2c152.iranlms.ir", "https://messengerg2c158.iranlms.ir"])];

        if ($socket) $R_socket = $socket[array_rand($socket)];
        else $R_socket = $socket[array_rand(["wss://nsocket12.iranlms.ir:80"])];

        return [
            "messenger" => $R_messanger,
            "socket" => $R_socket,
            "storage" => $R_storage
        ];
    }

    private function DCS($data)
    {
        $dc = $this->platform->getClientPlatfrom($this->application)['url'];
        $ch = curl_init($dc);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $headers = $this->platform->getClientPlatfrom($this->application)['header'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            if (file_exists("run.bat")) self::cmd_run("run.bat");
            else echo "The 'run.bat' file can be executed to restart after guzzel errors.\nNote: The 'run.bat' file will be executed directly on the terminal, so please be careful when writing the file.\nTo do this, create the 'run.bat' file in the bot's file path and upload the command inside the file\n";
            return null;
        }
        curl_close($ch);
        return json_decode($response, true);
    }
}
