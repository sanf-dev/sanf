<?php

namespace Sanf\Enums;

enum Platform: string
{
    case Android = "Android";
    case Web = "Web";
    case PWA = "PWA";

    public function getClientPlatfrom(Application $app = Application::Rubika): array
    {
        return match ($this) {
            self::Android => [
                "client" => [
                    "app_name" => "Main",
                    "app_version" => "3.8.1",
                    "store" => "Direct",
                    "lang_code" => "fa",
                    "package" => $app->value == "Rubika" ? "app.rbmain.a" : "ir.resaneh1.iptv",
                    "temp_code" => "30",
                    "platform" => "Android"
                ],
                "url" => $app->value == "Rubika" ? "https://getdcmess.iranlms.ir/" : "https://shgetdcmess.iranlms.ir/",
                "header" => $app->value == "Rubika" ? [
                    "content-type: application/json; charset=UTF-8",
                    "Referer: https://web.rubika.ir",
                    "Origin: https://web.rubika.ir"
                ] : [
                    "content-type: application/json; charset=UTF-8",
                    "Referer: https://web.shad.ir",
                    "Origin: https://web.shad.ir"
                ]
            ],
            self::Web => [
                "client" =>
                [
                    "app_name" => "Main",
                    "app_version" => "4.4.18",
                    "platform" => "Web",
                    "package" => $app->value == "Rubika" ? "web.rubika.ir" : "web.shad.ir",
                    "lang_code" => "fa"
                ],
                "url" => $app->value == "Rubika" ? "https://getdcmess.iranlms.ir/" : "https://shgetdcmess.iranlms.ir/",
                "header" => $app->value == "Rubika" ? [
                    "Accept" => "application/json, text/plain, */*",
                    "Content-Type" => "text/plain",
                    "Origin" => "https://web.rubika.ir",
                    "Referer" => "https://web.rubika.ir/",
                    "Sec-Ch-Ua-Platform" => "Windows",
                    "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
                ] : [
                    "Accept" => "application/json, text/plain, */*",
                    "Content-Type" => "text/plain",
                    "Origin" => "https://web.shad.ir",
                    "Referer" => "https://web.shad.ir/",
                    "Sec-Ch-Ua-Platform" => "Windows",
                    "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
                ]
            ],
            self::PWA => [
                "client" =>
                [
                    "app_name" => "Main",
                    "app_version" => "2.4.0",
                    "platform" => "PWA",
                    "package" => "m.rubika.ir",
                    "lang_code" => "fa"
                ],
                "url" => "https://getdcmess.iranlms.ir/",
                "header" => [
                    "Accept" => "application/json, text/plain, */*",
                    "Content-Type" => "text/plain",
                    "Origin" => "https://m.rubika.ir",
                    "Referer" => "https://m.rubika.ir",
                    "Sec-Ch-Ua-Platform" => "Windows",
                    "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
                ]
            ]
        };
    }
}
