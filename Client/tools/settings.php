<?php

namespace Sanf\Tools;

define('COLOR_RESET', "\033[0m");
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_MAGENTA', "\033[35m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_WHITE', "\033[37m");


if (!file_exists("config.json")) createFile();

function createFile()
{
    $setting = [
        "config" => [
            "auth" => false,
            "privateKey" => false,
            "self_name" => "sanf",
        ],
        "main_settings" => [
            "request_count" => 100,
            "run" => file_exists("run.bat"),
            "self_dev" => ["rubika" => "coder95", "telegram" => "coder95"],
            "message" => COLOR_RED . "Any misuse is the responsibility of the user, and we will not assume any liability for it." . COLOR_WHITE . "\nWe advise users not to run this bot on personal or important accounts; it is recommended to install it only on a non-essential account",
        ]
    ];
    file_put_contents(
        "config.json",
        json_encode(
            $setting,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );
}
