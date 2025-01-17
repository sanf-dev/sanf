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

class Config
{
    private string $auth;
    private string $server;
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
            "client" => $this->platform->getClientPlatform($this->application)["client"]
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
                'headers' => $this->platform->getClientPlatform($this->application)['header']
            ]);
            if ($this->request_count >= 70) {
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
        $support_API = [
            "storages" => [
                "https://messenger1050.iranlms.ir",
                "https://messenger1040.iranlms.ir",
                "https://messenger1035.iranlms.ir",
                "https://messenger1036.iranlms.ir",
                "https://messenger1037.iranlms.ir",
                "https://messenger1038.iranlms.ir",
                "https://messenger1039.iranlms.ir"
            ],
            "API" => [
                "https://messengerg2c38.iranlms.ir",
                "https://messengerg2c152.iranlms.ir",
                "https://messengerg2c158.iranlms.ir"
            ],
            "socket" => [
                "wss://jsocket4.iranlms.ir:80",
                "wss://nsocket11.iranlms.ir:80",
                "wss://jsocket2.iranlms.ir:80",
                "wss://nsocket12.iranlms.ir:80"
            ]
        ];

        // $ressult =  json_decode(file_get_contents($this->platform->getClientPlatform($this->application)['url']), true);
        $ressult = $this->DCS($json);
        $messanger =  $ressult['data']['API'] ?? $ressult['data']['default_api_urls'] ?? $support_API["API"] ?? false;
        $socket = $ressult['data']['socket'] ?? $ressult['data']['default_sockets'] ?? $support_API["socket"] ?? false;
        $storage = $ressult['data']['storage'] ?? $ressult['data']['storages'] ?? $support_API["storages"] ?? false;

        $S_messanger = $messanger ? $messanger[array_rand($messanger)] : exit("urls not found - messager");
        $S_socket = $socket ? $socket[array_rand($socket)] : exit("urls not found - socket");
        $S_storage = $storage ? $storage[array_rand($storage)] : exit("urls not found - storage");

        return [
            "messenger" => $S_messanger,
            "socket" => $S_socket,
            "storage" => $S_storage
        ];
    }

    private function DCS($data)
    {
        $dc = $this->platform->getClientPlatform($this->application)['url'];
        $ch = curl_init($dc);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $headers = $this->platform->getClientPlatform($this->application)['header'];
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
