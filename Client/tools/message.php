<?php

namespace Sanf\Tools;

use Sanf\Enums\{
    deleteMessage,
    Filter,
    setReaction
};

class Message
{
    private $data;
    private $api;

    public function __construct($data, $api)
    {
        $this->data = $data;
        $this->api = $api;
    }

    public function object_guid()
    {
        return $this->data["chat_updates"][0]["object_guid"] ?? false;
    }

    public function message_dict()
    {
        return $this->data['message_updates'][0] ?? false;
    }

    public function chat_type()
    {
        return $this->data["chat_updates"][0]["type"] ?? false;
    }

    public function count_unseen(): int
    {
        return intval($this->data["chat_updates"][0]["chat"]["count_unseen"] ?? 0);
    }

    public function status()
    {
        return $this->data["chat_updates"][0]["chat"]["status"] ?? false;
    }

    public function last_message_id()
    {
        return $this->data["chat_updates"][0]["chat"]["last_message_id"] ?? false;
    }

    public function action()
    {
        return $this->data["message_updates"][0]["action"] ?? false;
    }

    public function message_id()
    {
        return $this->data["message_updates"][0]["message_id"] ?? false;
    }

    public function reply_message_id()
    {
        $meg = isset($this->data["message_updates"][0]["message"]["reply_to_message_id"]) ? $this->data["message_updates"][0]["message"]["reply_to_message_id"] : '';
        return !isset($meg) || empty($meg) ? false : $meg;
    }

    public function text(bool $lower = true)
    {
        $text = isset($this->data["message_updates"][0]["message"]["text"]) ? strval($this->data["message_updates"][0]["message"]["text"]) : $this->data['chat_updates'][0]['chat']['last_message']['text'];
        return empty($text) || !isset($text) ? false : ($lower ? strtolower($text) : $text);
    }

    public function is_edited(): bool
    {
        return isset($this->data["message_updates"][0]["message"]["is_edited"]) ? $this->data["message_updates"][0]["message"]["is_edited"] : false;
    }

    public function message_type()
    {
        if ($this->file_inline()) {
            return $this->file_inline()["type"] ?? false;
        }
        return $this->data["message_updates"][0]["message"]["type"] ?? false;
    }

    public function author_type()
    {
        return $this->data["message_updates"][0]["message"]["author_type"] ?? false;
    }

    public function author_guid()
    {
        return $this->data["message_updates"][0]["message"]["author_object_guid"] ?? false;
    }

    public function prev_message_id(): string|bool|null
    {
        return $this->data["message_updates"][0]["prev_message_id"] ?? false;
    }

    public function title(): string|bool|null
    {
        return $this->data['show_notifications'][0]["title"] ?? false;
    }

    public function author_title()
    {
        return $this->data['chat_updates'][0]['chat']['last_message']["author_title"] ?? $this->title();
    }

    public function is_private(): bool
    {
        return $this->chat_type() === "User";
    }

    public function is_group(): bool
    {
        return $this->chat_type() === "Group";
    }

    public function is_forward(): bool
    {
        return isset($moreImfo['message']['forwarded_from']) || isset($moreImfo['message']['forwarded_no_link']) ? true : false;;
    }

    public function forward_from(): string|bool|null
    {
        $msg = $this->data["message_updates"][0]["message"]["forwarded_from"]["type_from"] ?? '';
        return $this->is_forward() ? (empty($msg) || !isset($msg) ? false : $msg) : false;
    }

    public function forward_object_guid(): string|bool|null
    {
        $msg = $this->data["message_updates"][0]["message"]["forwarded_from"]["object_guid"] ?? '';
        return $this->is_forward() ? (!isset($msg) || empty($msg) ? false : $msg) : false;
    }

    public function forward_message_id(): string|bool|null
    {
        $msg = $this->data["message_updates"][0]["message"]["forwarded_from"]["message_id"] ?? '';
        return $this->is_forward() ? (empty($msg) || !isset($msg) ? false : $msg) : false;
    }

    public function is_event(): string|bool|null
    {
        $msg = $this->data["message_updates"][0]["message"]["event_data"] ?? '';
        return !isset($msg) || empty($msg) ? false : $msg;
    }

    public function event_type(): string|bool|null
    {
        $msg = $this->data["message_updates"][0]["message"]["event_data"]["type"] ?? '';
        return $this->is_event() ? (empty($msg) || !isset($msg) ? false : $msg) : false;
    }

    public function event_object_guid(): array|string|null
    {
        $msg = $this->data["message_updates"][0]["message"]["event_data"]["performer_object"]["object_guid"] ?? '';
        return $this->is_event() ? (empty($msg) || !isset($msg) ? false : $msg) : false;
    }

    public function file_inline()
    {
        return $this->data["message_updates"][0]["message"]["file_inline"] ?? false;
    }

    public function reply($text): array|string
    {
        return $this->api->sendMessage($this->object_guid(), $text, $this->message_id());
    }

    public function editMessage($text, array|string|int $message_id): string|array|bool
    {
        if (is_array($message_id) && isset($message_id["message_update"]["message_id"]))
            return $this->api->editMessage($this->object_guid(), $text, $message_id["message_update"]["message_id"]);
        elseif (is_string($message_id) || is_numeric($message_id))
            return $this->api->editMessage($this->object_guid(), $text, $message_id);
        else
            return false;
    }

    public function has_link(): bool
    {

        $links = ["http:/", "https:/", "www.", ".ir", ".com", ".net", "@"];
        foreach ($links as $link) {
            if (stripos($this->text(), $link) !== false) {
                return true;
            }
        }
        return false;
    }
    public function seen(): array
    {
        return $this->api->seenChats([$this->object_guid() => $this->message_id()]);
    }

    public function deleteMessage(array|string|int $message_id, deleteMessage $type = deleteMessage::Local, $reply = false): string|array|bool
    {
        if ($reply) {
            if ($this->reply_message_id())
                return $this->api->deleteMessages($this->object_guid(), [$this->reply_message_id()], $type->value);
            else return false;
        } else {
            if (is_array($message_id) || isset($message_id["message_update"]["message_id"]))
                return $this->api->deleteMessages(
                    $this->object_guid(),
                    [$message_id["message_update"]["message_id"]],
                    $type
                );
            elseif (is_string($message_id) || is_numeric($message_id))
                return $this->api->deleteMessages(
                    $this->object_guid(),
                    [$message_id],
                    $type
                );
            else
                return false;
        }
    }

    public static function groupAccess(): array
    {
        return [
            "ChangeInfo",
            "PinMessages",
            "DeleteGlobalAllMessages",
            "BanMember",
            "SetAdmin",
            "SetJoinLink",
            "SetMemberAccess",
            "ViewMembers",
            "ViewAdmins",
            "SendMessages",
            "AddMember",
            "AcceptOwner",
            "ViewInfo",
            "ViewMessages",
            "DeleteLocalMessages",
            "EditMyMessages",
            "DeleteGlobalMyMessages"
        ];
    }
    public function getData(): array|string
    {
        return $this->data;
    }
    public function setReaction(int $reaction_id, setReaction $action = setReaction::Add)
    {
        return $this->api->actionOnMessageReaction($this->object_guid(), $this->message_id(), $action, $reaction_id);
    }
    public function filter(Filter $filterType): bool
    {
        if ($filterType->value == $this->message_type()) return true;
        else return false;
    }
}
