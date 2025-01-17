<?php

require_once "vendor/autoload.php";

use Sanf\Client;
use Sanf\Tools\Message;
use GuzzleHttp\Client as request;
use Sanf\Enums\deleteMessage;

// Using automatic login
$self = new Client('rush');

// Creating an anonymous function and adding commands
$action = function (Message $update) use ($self) {
    $text = $update->text(false);
    echo "$text\n";
    if (substr($text, 0, 1) == "/") {
        $del = $update->reply("چند لحضه صبر کنید تا پردازش تمام شود.");
        $update->reply(GPT(str_replace("/", "", $text)));
        $update->seen();
        $update->deleteMessage($del, deleteMessage::Global);
    }
    if ($update->has_link()) {
        echo json_encode($update->reply("Sending **links** is prohibited - [{$update->author_title()}]({$update->author_guid()})"));
        // $update->deleteMessage($update->message_id(), deleteMessage::Global);
    }
};

// Connecting to the socket
$self->on_message($action);

// Sending a request to the AI and receiving a response
function GPT(string $message)
{
    $client = new request();
    try {
        $response = $client->get("https://sanf.oping.xyz/api/gpt-2.php", [
            "query" => [
                "ask" => $message
            ]
        ]);
        $result = json_decode($response->getBody(), true);
        if (isset($result["resutl"]) && !is_array($result["resutl"])) return $result["resutl"];
        else return "خطا در دریافت اصلاعات لطفا مجدد تلاش کنید و از ارسال متن های بزرگ برای ربات خود داری میند.";
    } catch (Exception $e) {
        return "req error - message : {$e->getMessage()}";
    }
}
