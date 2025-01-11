<?php

namespace Sanf;

ob_implicit_flush();

use getid3;
use Exception;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;
use Sanf\Crypto\Crypto;
use Sanf\Config\Config;
use Sanf\Tools\{
    Message
};
use Sanf\Enums\{
    Application,
    Block,
    chatActivity,
    ChatTypes,
    deleteMessage,
    Device,
    joinGroup,
    Pin,
    Platform,
    setChannelType,
    setHistory,
    setReaction,
    setSetting,
    Sort
};

class Client
{
    private Config $config;
    private $account_auth;
    public function __construct(string $auth, string $privateKey, Platform $platform = Platform::Web, Application $application = Application::Rubika)
    {
        $this->config = new Config($auth, $privateKey, $platform, $application);
        $this->account_auth = $auth;
    }

    public function getServiceInfo(string $service_guid)
    {
        return $this->config->setJson("getServiceInfo", ["service_guid" => $service_guid]);
    }

    public function getMyStickerSets()
    {
        return $this->config->setJson("getMyStickerSets", []);
    }

    public function getFolders()
    {
        return $this->config->setJson("getFolders", []);
    }

    public function getChatsUpdates(int $state = 0)
    {
        $state === 0 ? $state = time() - 150 : $state;
        return $this->config->setJson("getChatsUpdates", ["state" => $state]);
    }
    public function getChatAds()
    {
        return $this->config->setJson("getChatAds", ["state" => time()]);
    }

    public function getUserInfo(string $user_guid = null)
    {
        $json = is_null($user_guid)  ? [] : ["user_guid" => $user_guid];
        return $this->config->setJson("getUserInfo", $json);
    }

    public function getMessagesInterval(string $object_guid, int $message_id)
    {
        return $this->config->setJson("getMessagesInterval", ["object_guid" => $object_guid, "middle_message_id" => $message_id]);
    }

    /**
     * Summary of getMessagesByID
     * @param string $object_guid
     * @param array $message_ids ["message_id-1","message_id-2",...,"message_id-3"]
     * @return mixed
     */
    public function getMessagesByID(string $object_guid, array $message_ids)
    {
        return $this->config->setJson("getMessagesByID", ["object_guid" => $object_guid, "message_ids" => $message_ids]);
    }

    public function getMessagesUpdates(string $object_guid, int $state = 0)
    {
        $state === 0 ? $state = time() - 150 : $state;
        return $this->config->setJson("getMessagesUpdates", ["object_guid" => $object_guid, "state" => $state]);
    }

    public function getAvatars(string $object_guid)
    {
        return $this->config->setJson("getAvatars", ["object_guid" => $object_guid]);
    }

    public function 
    (string $object_guid, chatActivity $action)
    {
        return $this->config->setJson("sendChatActivity", ["object_guid" => $object_guid, "activity" => $action->value]);
    }

    public function sendMessage(string $object_guid, string $text, int|string $reply = 0)
    {
        $pattern = self::Metadata($text);
        $json = [
            "object_guid" => $object_guid,
            "rnd" => random_int(12312, 998899),
            "text" => isset($pattern["metadata"]) ? $pattern["text"] : $text,
        ];
        $json["metadata"] = isset($pattern["metadata"]) ? $pattern["metadata"] : null;
        !$reply ? null : $json['reply_to_message_id'] = $reply;
        // return $json;
        return $this->config->setJson("sendMessage", $json);
    }

    public function sendRubinoStory(string $object_guid, string $story_id, string $profile_id, bool  $is_mute = false)
    {
        $json = [
            "is_mute" => $is_mute,
            "object_guid" => $object_guid,
            "rnd" => random_int(-998899, -12312),
            "story_id" => $story_id,
            "story_profile_id" => $profile_id,
            "type" => "Direct"
        ];
        return $this->config->setJson("sendRubinoStory", $json);
    }
    public function sendRubinoPost(string $object_guid, string $post_id, string $profile_id, bool  $is_mute = false)
    {
        $json = [
            "is_mute" => $is_mute,
            "object_guid" => $object_guid,
            "rnd" => random_int(-998899, -12312),
            "post_id" => $post_id,
            "post_profile_id" => $profile_id
        ];
        return $this->config->setJson("sendRubinoPost", $json);
    }

    public function getMyGifSet()
    {
        return $this->config->setJson("getMyGifSet", []);
    }

    public function getAvailableReactions()
    {
        return $this->config->setJson("getAvailableReactions", []);
    }

    /**
     * Summary of seenChats
     * @param array $listSeen ["Guid" => "message_id"]
     * @return mixed
     */
    public function seenChats(array $listSeen)
    {
        return $this->config->setJson("seenChats", ["seen_list" => $listSeen]);
    }

    public function addToMyGifSet(string $object_guid, int $message_id)
    {
        return $this->config->setJson("addToMyGifSet", ["message_id" => $message_id, "object_guid" => $object_guid]);
    }

    public function actionOnMessageReaction(string $object_guid, int|string $message_id, setReaction $action, int $reaction_id = 1)
    {
        $json = [
            "action" => $action->value,
            "message_id" => $message_id,
            "object_guid" => $object_guid
        ];
        if ($action->value == "Add")
            $json['reaction_id'] = $reaction_id;

        return $this->config->setJson("actionOnMessageReaction", $json);
    }

    public function getTrendStickerSets(string $object_guid, int $message_id)
    {
        return $this->config->setJson("getTrendStickerSets", []);
    }

    public function actionOnStickerSet(string $sticker_set_id, setReaction $action)
    {
        return $this->config->setJson("actionOnStickerSet", ["sticker_set_id" => $sticker_set_id, "action" => $action->value]);
    }

    /**
     * Summary of deleteMessages
     * @param string $object_guid
     * @param array $message_ids ["msg_1","msg_2","msg_3","msg_3","msg_4"]
     * @param deleteMessage $type deleteMessage:: Local -> [for me] or Global -> [for all]
     * @return mixed
     */
    public function deleteMessages(string $object_guid, array $message_ids, deleteMessage $type)
    {
        return $this->config->setJson("deleteMessages", ["object_guid" => $object_guid, "message_ids" => $message_ids, "type" => $type->value]);
    }

    public function getChats()
    {
        return $this->config->setJson("getChats", []);
    }

    public function getMySessions()
    {
        return $this->config->setJson("getMySessions", []);
    }

    public function getInfoByUsername(string $username)
    {
        return $this->config->setJson("getObjectByUsername", ['username' => $username]);
    }


    public function getMessages(string $object_guid, Sort $sort = Sort::Min, int $min_id = null)
    {
        $json = [
            'object_guid' => $object_guid,
            'sort' => $sort->value
        ];
        is_null($min_id) ? null : $json["min_id"] = $min_id;
        return $this->config->setJson('getMessages', $json);
    }

    public function getContacts()
    {
        return $this->config->setJson("getContacts", []);
    }

    public function getContactsUpdates()
    {
        return $this->config->setJson("getContactsUpdates", ["state" => time() - 150]);
    }

    public function updateProfile(string $bio = null, string $first_name = null, string $last_name = null, string $date = null)
    {
        $json = [

            "updated_parameters" => []
        ];
        if (is_null($bio) && is_null($first_name) && is_null($last_name) && is_null($date)) return ["ERROR" => "one parameter is required"];

        if (!is_null($bio)) {
            $json["bio"] = $bio;
            $json['updated_parameters'][] = "bio";
        }
        if (!is_null($first_name)) {

            $json['first_name'] = $first_name;
            $json['updated_parameters'][] = "first_name";
        }
        if (!is_null($last_name)) {

            $json['last_name'] = $last_name;
            $json['updated_parameters'][] = "last_name";
        }
        if (!is_null($date)) {

            $json['birth_date'] = $date;
            $json['updated_parameters'][] = "birth_date";
        }

        return $this->config->setJson("updateProfile", $json);
    }

    public function terminateSession(string $key)
    {
        return $this->config->setJson("terminateSession", ["session_key" => $key]);
    }

    public function getBlockedUsers()
    {
        return $this->config->setJson("getBlockedUsers", []);
    }

    public function requestDeleteAccount()
    {
        return $this->config->setJson("requestDeleteAccount", []);
    }

    public function getPrivacySetting()
    {
        return $this->config->setJson("getPrivacySetting", []);
    }

    public function getGroupInfo(string $group_guid)
    {
        return $this->config->setJson("getGroupInfo", ["group_guid" => $group_guid]);
    }

    public function getGroupOnlineCount(string $group_guid)
    {
        return $this->config->setJson("getGroupOnlineCount", ["group_guid" => $group_guid]);
    }

    /**
     * Summary of getAbsObjects
     * @param array $objects_guids ["userGuid-1","g2",...,"g-100"]
     * @return mixed
     */
    public function getAbsObjects(array $objects_guids)
    {
        return $this->config->setJson("getAbsObjects", ["objects_guids" => $objects_guids]);
    }

    /**
     * Summary of getListMessagesByID
     * @param array $object_guid ["msgId-1","m2",...,"m-100"]
     * @param array $message_ids
     * @return mixed
     */
    public function getListMessagesByID(array $object_guid, array $message_ids)
    {
        return $this->config->setJson("getMessagesByID", ["object_guid" => $object_guid, "message_ids" => $message_ids]);
    }


    public function DownloadFile(string $object_guid, string|int $message_id, string|null $file_name = null, callable $progress = null)
    {
        $file_name = is_null($file_name) ? "sanf-downloader-" . random_int(0, 50) : $file_name;
        $message_info = self::getMessagesByID($object_guid, [$message_id]);
        if (!isset($message_info["messages"][0])) return "message not found | please check param [object_guid | message_id]";
        else $message_info = $message_info["messages"][0];
        if (!isset($message_info["file_inline"])) return "file not found | please check param [object_guid | message_id]";
        else $message_info = $message_info["file_inline"];
        if (!isset(
            $message_info["file_id"],
            $message_info["mime"],
            $message_info["dc_id"],
            $message_info["access_hash_rec"],
            $message_info["size"]
        )) return "file (info) not found";

        list(
            $file_id,
            $dc_id,
            $access_hash_rec,
            $mime,
            $fileSize
        ) = [
            $message_info["file_id"],
            $message_info["dc_id"],
            $message_info["access_hash_rec"],
            $message_info["mime"],
            $message_info["size"]
        ];
        $MB_fileSize = $fileSize / (1024 * 1024);


        $partSize = 128 * (1024 * 2);
        $totalParts = ceil($fileSize / $partSize);

        for ($part = 0; $part < $totalParts; $part++) {

            if (is_callable($progress)) $progress("file Size (byte : $fileSize) , (MB : $MB_fileSize) | total Part : $totalParts | upload part $part");
            $start_index = $part * $partSize;
            $last_index = min(($start_index + $partSize - 1), $fileSize - 1);
            $range = "bytes=$start_index-$last_index";

            $data = self::requestDownloadFile(
                $access_hash_rec,
                $file_id,
                $dc_id,
                $last_index,
                $start_index,
                $range

            );
            file_put_contents("$file_name.$mime", $data, FILE_APPEND);
        }
        return "$file_name.$mime";
    }
    private function requestDownloadFile(
        string $hash,
        string $file_id,
        string $dc_id,
        string $last_index,
        string $start_index,
        string $range
    ) {
        $url = "https://messenger$dc_id.iranlms.ir/GetFile.ashx";
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'headers' => [
                "Access-Hash-Rec" => (string) $hash,
                "Auth" => Crypto::setAuth($this->account_auth),
                "Client-App-Name" => "Main",
                "Client-App-Version" => "3.8.1",
                "Client-Package" => "app.rbmain.a",
                "Client-Platform" => "Android",
                "Connection" => "Keep-Alive",
                "Content-Length" => 0,
                "Content-Type" => "application/json",
                "dc-id" => (string) $dc_id,
                "file-id" => (string) $file_id,
                "Host" => parse_url($url, PHP_URL_HOST),
                "last-index" => (string) $last_index,
                "range" => $range,
                "start-index" => (string) $start_index,
                "User-Agent" => "okhttp/3.12.12"
            ]
        ]);
        $data = $response->getBody();
        return $data;
    }

    public function requestSendFile(string $file_name, string $mime, int $size)
    {
        return $this->config->setJson("requestSendFile", ["file_name" => $file_name, "size" => $size, "mime" => $mime]);
    }

    public function UploadedFile(string $file, callable $progress = null)
    {
        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ]);

        $chunk_size = 128 * 1024;
        $file_name = pathinfo($file, PATHINFO_BASENAME);

        $file_content = @file_get_contents($file, false, $context);
        if ($file_content === FALSE)
            return array(
                "status" => false,
                "message" => "Failed to retrieve contents.\n",
                'dc_id' => null,
                'file_id' => null,
                'file_name' => null,
                'file_size' => null,
                'mime' => null,
                'hash_code' => null
            );

        $total_parts = (int) ceil(strlen($file_content) / $chunk_size);
        $file_size = strval(strlen($file_content));
        $pr = self::requestSendFile($file_name, pathinfo($file, PATHINFO_EXTENSION), $file_size);

        for ($part_number = 1; $part_number <= $total_parts; $part_number++) {
            $start = ($part_number - 1) * $chunk_size;
            $end = min($part_number * $chunk_size, strlen($file_content));
            $data = substr($file_content, $start, $end - $start);
            $response = $this->UploadFileToServer(
                $pr["upload_url"],
                $data,
                strval(strlen($data)),
                $pr["id"],
                strval($part_number),
                $pr["access_hash_send"],
                strval($total_parts)
            );
            if (is_callable($progress))
                $progress("file size (byte) $file_size | total part : $total_parts | upload part $part_number", json_encode($response));
            if ($response['data'] != null) {
                return array(
                    "status" => true,
                    "message" => true,
                    'dc_id' => $pr['dc_id'],
                    'file_id' => $pr['id'],
                    'file_name' => $file_name,
                    'file_size' => $file_size,
                    'mime' => pathinfo($file, PATHINFO_EXTENSION),
                    'hash_code' => $response['data']['access_hash_rec']
                );
            } elseif ($response["status"] == "ERROR_TRY_AGAIN" || $response["status"] == "ERROR_GENERIC") {
                return array(
                    "status" => false,
                    "message" => "upload error : (ERROR_TRY_AGAIN)",
                    'dc_id' => null,
                    'file_id' => null,
                    'file_name' => null,
                    'file_size' => null,
                    'mime' => null,
                    'hash_code' => null
                );
            }
        }
    }

    private function UploadFileToServer(string $url, string $data, int $Chunk_Size, $file_id, $part_number, $hash, $total_parts)
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false,
        ]);
        $response = $client->post($url, [
            'body' => $data,
            'headers' => array(
                "Accept" => "application/json, text/plain, */*",
                "Access-Hash-Send" => $hash,
                'Host' => parse_url($url, PHP_URL_HOST),
                "Auth" => Crypto::setAuth($this->account_auth),
                "Chunk-Size" => $Chunk_Size,
                "File-Id" => $file_id,
                "Part-Number" => $part_number,
                "Total-Part" => $total_parts,
                "Content-Type" => "text/plain",
                "Origin" => "https://web.rubika.ir",
                "Referer" => "https://web.rubika.ir/",
                "Sec-Ch-Ua-Platform" => "Windows",
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
            )

        ]);
        $Cipher = $response->getBody()->getContents();
        return json_decode($Cipher, true);
    }

    public function sendFile($file_path, $object_guid, $reply = null, $captipn = '', callable|null $progress = null)
    {
        $up = $this->UploadedFile($file_path, $progress);
        if (!isset($up['status']) || !$up['status']) {
            echo isset($up["message"]) ? $up["message"] : "upload error";
            return false;
        }
        $json = [
            "object_guid" => $object_guid,
            "rnd" => (string) mt_rand(100000, 999999),
            "file_inline" => [
                "dc_id" => $up['dc_id'],
                "file_id" => $up['file_id'],
                "type" => "File",
                "file_name" => "sanfBot." . $up["mime"], //$up['file_name'],
                "size" => $up['file_size'],
                "mime" => $up['mime'],
                "access_hash_rec" => $up['hash_code']
            ],
        ];
        if (!empty($captipn)) {
            $pattern = self::Metadata($captipn);
            isset($pattern["metadata"]) ? $json['metadata'] = $pattern["metadata"] : null;
            $json['text'] = $pattern["text"];
        }
        !$reply ? null : $json['reply_to_message_id'] = $reply;
        return $this->config->setJson("sendMessage", $json);
    }
    public function sendMultyFile($object_guid, array $file_inline, $caption = '')
    {
        $json = [
            "object_guid" => $object_guid,
            "rnd" => (string) mt_rand(100000, 999999),
            "file_inline" => $file_inline,
        ];
        if (!empty($captipn)) {
            $pattern = self::Metadata($captipn);
            isset($pattern["metadata"]) ? $json['metadata'] = $pattern["metadata"] : null;
            $json['text'] = $pattern["text"];
        }
        return $this->config->setJson("sendMessage", $json);
    }

    public function sendLive(string $object_guid, string $title, Device $device_type = Device::Software, array $comments_list = ["hello", "ok"])
    {
        return $this->config->setJson("sendLive", [
            "device_type" => $device_type,
            "object_guid" => $object_guid,
            "rnd" => random_int(1, 99),
            "suggestion_comments" => $comments_list,
            "title" => $title,
            "thumb_inline" => "\/9j\/4AAQSkZJRgABAQAAAQABAAD\/4gIoSUNDX1BST0ZJTEUAAQEAAAIYAAAAAAIQAABtbnRyUkdC\nIFhZWiAAAAAAAAAAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAA\nAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlk\nZXNjAAAA8AAAAHRyWFlaAAABZAAAABRnWFlaAAABeAAAABRiWFlaAAABjAAAABRyVFJDAAABoAAA\nAChnVFJDAAABoAAAAChiVFJDAAABoAAAACh3dHB0AAAByAAAABRjcHJ0AAAB3AAAADxtbHVjAAAA\nAAAAAAEAAAAMZW5VUwAAAFgAAAAcAHMAUgBHAEIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\nAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFhZWiAA\nAAAAAABvogAAOPUAAAOQWFlaIAAAAAAAAGKZAAC3hQAAGNpYWVogAAAAAAAAJKAAAA+EAAC2z3Bh\ncmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABYWVogAAAAAAAA9tYAAQAAAADT\nLW1sdWMAAAAAAAAAAQAAAAxlblVTAAAAIAAAABwARwBvAG8AZwBsAGUAIABJAG4AYwAuACAAMgAw\nADEANv\/bAEMADQkKCwoIDQsKCw4ODQ8TIBUTEhITJxweFyAuKTEwLiktLDM6Sj4zNkY3LC1AV0FG\nTE5SU1IyPlphWlBgSlFST\/\/bAEMBDg4OExETJhUVJk81LTVPT09PT09PT09PT09PT09PT09PT09P\nT09PT09PT09PT09PT09PT09PT09PT09PT09PT\/\/AABEIADIAJQMBIgACEQEDEQH\/xAAWAAEBAQAA\nAAAAAAAAAAAAAAAAAQf\/xAAUEAEAAAAAAAAAAAAAAAAAAAAA\/8QAFQEBAQAAAAAAAAAAAAAAAAAA\nAAH\/xAAUEQEAAAAAAAAAAAAAAAAAAAAA\/9oADAMBAAIRAxEAPwDMRABUAAAAAAAAABUAAAFARQAA\nFAAf\/9k=\n"
        ]);
    }

    public function sendMuzic($file_path, $object_guid, $reply = null, $captipn = '', callable|null $progress = null)
    {
        $getMusicInfo = new getID3();
        $getTime = $getMusicInfo->analyze($file_path);
        $getTime = isset($getTime['error']) ? 179.4992 : $getTime["playtime_seconds"];
        $up = $this->UploadedFile($file_path, $progress);
        if (!isset($up['status']) || !$up['status']) {
            echo isset($up["message"]) ? $up["message"] : "upload error";
            return false;
        }
        $json = [
            "object_guid" => $object_guid,
            "rnd" => (string) mt_rand(100000, 999999),
            "file_inline" => [
                "dc_id" => $up['dc_id'],
                "file_id" => $up['file_id'],
                "type" => "Music",
                "file_name" => "sanfMusic." . $up["mime"], //$up['file_name'],
                "size" => $up['file_size'],
                "mime" => $up['mime'] ?? "mp3",
                "time" => $getTime,
                "music_performer" => "sanfai music",
                "access_hash_rec" => $up['hash_code']
            ],
        ];
        if (!empty($captipn)) {
            $pattern = self::Metadata($captipn);
            isset($pattern["metadata"]) ? $json['metadata'] = $pattern["metadata"] : null;
            $json['text'] = $pattern["text"];
        }
        !$reply ? null : $json['reply_to_message_id'] = $reply;
        return $this->config->setJson("sendMessage", $json);
    }

    public function sendImage(string $object_guid, string $file_path, $reply, string $caption = "")
    {
        $up = $this->UploadedFile($file_path);
        if (!$up['status']) {
            echo $up["message"];
            return false;
        }

        $imageInfo = getimagesize($file_path);
        if ($imageInfo !== false) {
            list($width, $height) = $imageInfo;
        }
        $json =  [
            "object_guid" => $object_guid,
            "rnd" => (string) mt_rand(100000, 999999),
            "file_inline" => [
                "dc_id" => (string)$up["dc_id"],
                "file_id" => (string)$up["file_id"],
                "type" => "Image",
                "file_name" => $up["file_name"] . $up["mime"],
                "size" => $up["file_size"],
                "mime" => $up["mime"],
                "thumb_inline" => self::getImageThumbInline($file_path),
                "width" => $width,
                "height" => $height,
                "access_hash_rec" => $up["hash_code"]
            ],
        ];
        if (!empty($caption)) {
            $pattern = self::Metadata($caption);
            isset($pattern["metadata"]) ? $json['metadata'] = $pattern["metadata"] : null;
            $json['text'] = $pattern["text"];
        }
        !$reply ? null : $json['reply_to_message_id'] = $reply;
        return $this->config->setJson("sendMessage", $json);
    }

    private static function getImageThumbInline($file_path)
    {
        $fileByte = file_get_contents($file_path);
        $image = imagecreatefromstring($fileByte);
        if ($image === false) {
            return "\/9j\/4AAQSkZJRgABAQAAAQABAAD\/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb\/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD\/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD\/wAARCAAoACgDASIAAhEBAxEB\/8QAGwAAAgIDAQAAAAAAAAAAAAAAAAgFBwQGCQL\/xAAuEAABAwMBBgQHAQEAAAAAAAABAgMEAAURBgcIEiExQRNRcZEUM1JhgaHBIjL\/xAAYAQADAQEAAAAAAAAAAAAAAAADBAUBAv\/EACkRAAECBQMBCQEAAAAAAAAAAAIAAQMEBhEhBRIx0RMiI0FCcYGhseH\/2gAMAwEAAhEDEQA\/AHvbtcjjbDbaC3k+ISSCPLHao7VGi7rqG1TrNG1IqEma1woSlhKuDHU4PM\/c1LWzTTDTofuWo7ncXEKCm1PvJSEEdCEoCU559cVsL0eDJaDTrilAfS4U59eHGakFr8Jnw6IOjkPK557WNmM\/Q12NtdvrE2akcb3CccJPMDOevmO1VRdX7mw5wy1KBHb7V0g1VsU2a3lmSgWdTU+chYRKHG6Glnn4hBOPfrVAzNzu\/TJMj4vVUBthIUWVJSVKUewIxhPuaqy1USu3xDt7\/wASsXQIpP3BulHk3laEcCRjzPnRV6ah3W7pZJSUzrpbloKgFK8ZzhSD0JUEY9qKqBrkvEFiA8JR9Eji9nBWvF3hbgMeKxHUcYyFkGs9G8RKCPksA9j4hxSJo2iSXUBxuSFpPMKSrIP5rw5tCuZ+W9gfcmop0rInnb9v1VcKjmmwT3+G6J6Xt4acQQlTA\/f9qIkbwF3SVKTMCgfqAIFI+\/ry9rSUic4M+S8VDytTXR4EGU4rI55dUc\/usGlZIfSy7epJjyTrXnbhIuSC3cI8aS35OJyB6EcxRSNOagu6UFtM5xKT1SlZx7UU0NPysNrA2EIqhmne9\/xU1atR3O0f5hyVBpXPw1c0H8H+VsEXXrxJ+OiBYzkFlXCf3RRR4cY24dJlDEuWWazryG6eB1h9oZ5KJCgPXvRI1YkJJhKKsH\/pRGB6DrRRTIxSflAKGLPhYZ1lKSVKeZZKe2MjFFFFd7yWdmK\/\/9k=";
        }
        $width = imagesx($image);
        $height = imagesy($image);
        if ($height > $width) {
            $newHeight = 40;
            $newWidth = round($newHeight * $width / $height);
        } else {
            $newWidth = 40;
            $newHeight = round($newWidth * $height / $width);
        }
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        ob_start();
        imagepng($thumb);
        $changedImage = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        imagedestroy($thumb);
        return base64_encode($changedImage);
    }

    public function getLinkFromAppUrl(string $link)
    {
        return $this->config->setJson("getLinkFromAppUrl", ["app_url" => $link]);
    }

    public function getChannelInfo(string $channel_guid)
    {
        return $this->config->setJson("getChannelInfo", ["channel_guid" => $channel_guid]);
    }

    public function createGroupVoiceChat(string $chat_guid)
    {
        return $this->config->setJson("createGroupVoiceChat", ["chat_guid" => $chat_guid]);
    }

    public function getGroupVoiceChatParticipants(string $chat_guid, string $voice_chat_id)
    {
        return $this->config->setJson("getGroupVoiceChatParticipants", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id]);
    }

    public function setGroupVoiceChatSetting(string $chat_guid, string $voice_chat_id, string $title)
    {
        return $this->config->setJson("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "title" => $title, "updated_parameters" => ["title"]]);
    }

    public function setGroupVoiceChatSettingMute(string $chat_guid, string $voice_chat_id, bool $mute = false)
    {
        return $this->config->setJson("setGroupVoiceChatSetting", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id, "join_muted" => $mute, "updated_parameters" => ["join_muted"]]);
    }

    public function discardGroupVoiceChat(string $chat_guid, string $voice_chat_id,)
    {
        return $this->config->setJson("discardGroupVoiceChat", ["chat_guid" => $chat_guid, "voice_chat_id" => $voice_chat_id]);
    }

    public function getGroupAllMembers(string $group_guid)
    {
        return $this->config->setJson("getGroupAllMembers", ["group_guid" => $group_guid]);
    }

    public function getGroupDefaultAccess(string $group_guid)
    {
        return $this->config->setJson("getGroupDefaultAccess", ["group_guid" => $group_guid]);
    }

    public function getGroupAdminMembers(string $group_guid)
    {
        return $this->config->setJson("getGroupAdminMembers", ["group_guid" => $group_guid]);
    }

    public function getGroupLink(string $group_guid)
    {
        return $this->config->setJson("getGroupLink", ["group_guid" => $group_guid]);
    }

    public function editGroupInfo_title(string $object_guid, string $title)
    {
        return $this->config->setJson("editGroupInfo", [
            "group_guid" => $object_guid,
            "title" => $title,
            "updated_parameters" => [
                "title"
            ]
        ]);
    }

    public function editGroupInfo_historyMembers(string $group_guid, setHistory $action)
    {

        return $this->config->setJson("editGroupInfo", ["group_guid" => $group_guid, "chat_history_for_new_members" => $action->value, "updated_parameters" => ["chat_history_for_new_members"]]);
    }

    public function editGroupInfo_eventMessages(string $group_guid, bool $showEventMessages = true)
    {
        return $this->config->setJson("editGroupInfo", ["group_guid" => $group_guid, "event_messages" => $showEventMessages, "updated_parameters" => ["event_messages"]]);
    }

    public function setGroupLink(string $group_guid)
    {
        return $this->config->setJson("setGroupLink", ["group_guid" => $group_guid]);
    }

    public function setAllReaction(string $group_guid)
    {
        return $this->config->setJson("editGroupInfo", ["group_guid" => $group_guid, "chat_reaction_setting" => ["reaction_type" => "All"], "updated_parameters" => ["chat_reaction_setting"]]);
    }

    public function getBannedGroupMembers(string $group_guid)
    {
        return $this->config->setJson("getBannedGroupMembers", ["group_guid" => $group_guid]);
    }

    public function leaveGroup(string $group_guid)
    {
        return $this->config->setJson("leaveGroup", ["group_guid" => $group_guid]);
    }

    public function joinGroupByLink(string $link)
    {
        return $this->config->setJson("joinGroup", ["hash_link" => $link]);
    }

    public function searchGlobalObjects(string $text)
    {
        return $this->config->setJson("searchGlobalObjects", ["search_text" => $text]);
    }

    public function addAddressBook(string $phone, string $first_name, string $last_name = "")
    {
        if (substr($phone, 0, 1) == 0) {
            $phone = "+98" . substr($phone, 1);
        } else if (substr($phone, 0, 2) == "98") {
            $phono = "+" . $phone;
        } else {
            return "error please enter phone , example (string) +989136667575 or 09136667575";
        }

        return $this->config->setJson("addAddressBook", ["phone" => $phone, "first_name" => $first_name, "last_name" => $last_name]);
    }

    /**
     * @param array $users ["userGuid-1","userGuid-2",...,"userGuid-3"]
     */
    public function getContactsLastOnline(array $users)
    {
        return $this->config->setJson("getContactsLastOnline", ["user_guids" => $users]);
    }

    /**
     * Summary of addGroup
     * @param array $member_guids ["userGuid-1","userGuid-2",...,"userGuid-3"]
     * @return mixed
     */
    public function addGroup(array $member_guids)
    {
        return $this->config->setJson("addGroup", ["member_guids" => $member_guids]);
    }

    /**
     * @param array $member_guids ["userGuid-1","userGuid-2",...,"userGuid-3"]
     * @return mixed
     */
    public function addGroupMembers(string $group_guid, array $member_guids)
    {
        return $this->config->setJson("addGroupMembers", ["group_guid" => $group_guid, "member_guids" => $member_guids]);
    }

    public function getMember_and_joinNewGroup(string $get_member_group_guid, string $add_member_group_guid, bool $left = true)
    {
        $getGroupMembers = $this->getGroupAllMembers($get_member_group_guid)["data"]["in_chat_members"];
        if (empty($getGroupMembers)) {
            return "error get member";
        }
        $MGuids = [];
        $NameUsers = [];
        $name = "sanfai message :\n\n";
        foreach ($getGroupMembers as $guid) {
            $MGuids[] = $guid["member_guid"];
            $NameUsers[] = $guid["first_name"];
            $name .= $guid["first_name"] . "\n";
        }
        $name .= "\nadd to group :)";
        $this->leaveGroup($get_member_group_guid);
        $this->sendMessage($add_member_group_guid, $name);
        return  ["guids" => $MGuids, "names" => $NameUsers, "server_message" => $this->addGroupMembers($add_member_group_guid, $MGuids)];
    }

    public function logout()
    {
        return $this->config->setJson("logout", []);
    }

    public function setGroupAdmin(string $group_guid, string $member_guid, array $access_list = [])
    {
        if ($access_list == []) {
            $access_list = [
                "ChangeInfo",
                "PinMessages",
                "DeleteGlobalAllMessages",
                "BanMember",
                "SetAdmin",
                "SetMemberAccess",
                "SetJoinLink"
            ];
        }
        return $this->config->setJson("setGroupAdmin", ["group_guid" => $group_guid, "member_guid" => $member_guid, "action" => "SetAdmin", "access_list" => $access_list]);
    }

    public function setGroupUnAdmin(string $group_guid, string $member_guid)
    {
        return $this->config->setJson("setGroupAdmin", ["group_guid" => $group_guid, "member_guid" => $member_guid, "action" => "UnsetAdmin"]);
    }

    public function banGroupMember(string $group_guid, string $member_guid)
    {
        return $this->config->setJson("banGroupMember", ["group_guid" => $group_guid, "member_guid" => $member_guid, "action" => "Set"]);
    }

    public function unBanGroupMember(string $group_guid, string $member_guid)
    {
        return $this->config->setJson("banGroupMember", ["group_guid" => $group_guid, "member_guid" => $member_guid, "action" => "Unset"]);
    }

    public function searchMemberGroup(string $group_guid, $username)
    {
        return $this->config->setJson("getGroupAllMembers", ["group_guid" => $group_guid, "search_text" => $username]);
    }

    /**
     * get group message Read Participants : namaishe Bazdid konandegan Payam bray Group ;)
     */
    public function getGroupMessageReadParticipants(string $group_guid, int $message_id)
    {
        return $this->config->setJson("getGroupMessageReadParticipants", ["group_guid" => $group_guid, "message_id" => $message_id]);
    }

    public function editMessage(string $object_guid, string $text, string $message_id)
    {
        $pattern = self::Metadata($text);
        $json = [
            "object_guid" => $object_guid,
            "text" => $pattern["text"],
            "message_id" => $message_id
        ];
        if (isset($pattern["metadata"])) {
            $json['metadata'] = $pattern["metadata"];
        }
        return $this->config->setJson("editMessage", $json);
    }

    /**
     * @param string $from az koja forward Beshe (guid)
     * @param string $to be koja forward Beshe (guid)
     * @param array $message_ids ["message_id_1","m_2",...]
     * @return mixed
     */
    public function forwardMessages(string $from, string $to, array $message_ids)
    {
        return $this->config->setJson("forwardMessages", ["from_object_guid" => $from, "to_object_guid" => $to, "message_ids" => $message_ids, "rnd" => (string) random_int(12332, 987889)]);
    }

    public function sendGifByInfo(string $object_guid, string $file_id, int $dc_id, string $hash, string $caption = "", int $size = 0, int $time = 0, int $height = 0, int $width = 0, string $file_name = "SanfBot", string $thumb = '')
    {
        $thumb = !empty($thumb) ? $thumb : "/9j/4AAQSkZJRgABAQAAAQABAAD/4gJASUNDX1BST0ZJTEUAAQEAAAIwAAAAAAIQAABtbnRyUkdC\nIFhZWiAAAAAAAAAAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAA\nAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlk\nZXNjAAAA8AAAAHRyWFlaAAABZAAAABRnWFlaAAABeAAAABRiWFlaAAABjAAAABRyVFJDAAABoAAA\nAChnVFJDAAABoAAAAChiVFJDAAABoAAAACh3dHB0AAAByAAAABRjcHJ0AAAB3AAAAFRtbHVjAAAA\nAAAAAAEAAAAMZW5VUwAAAFgAAAAcAHMAUgBHAEIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\nAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFhZWiAA\nAAAAAABvogAAOPUAAAOQWFlaIAAAAAAAAGKZAAC3hQAAGNpYWVogAAAAAAAAJKAAAA+EAAC2z3Bh\ncmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABYWVogAAAAAAAA9tYAAQAAAADT\nLW1sdWMAAAAAAAAAAQAAAAxlblVTAAAAOAAAABwARwBvAG8AZwBsAGUAIABJAG4AYwAuACAAMgAw\nADEANgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP/bAEMAHhQWGhYTHhoYGiEfHiMsSjAsKSksW0FE\nNkprXnFvaV5oZnaFqpB2fqGAZmiUypahsLW/wL9zjtHgz7neqru/t//bAEMBHyEhLCcsVzAwV7d6\naHq3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t7e3t//AABEI\nABkALQMBIgACEQEDEQH/xAAZAAADAQEBAAAAAAAAAAAAAAABAgMEAAX/xAAqEAABAwMCBAUFAAAA\nAAAAAAABAAIDBBESITETM0FhMjRxgbEFIkJRwf/EABYBAQEBAAAAAAAAAAAAAAAAAAIBA//EABYR\nAQEBAAAAAAAAAAAAAAAAAAABEf/aAAwDAQACEQMRAD8ARmLtTv0T00ZlqsRrYdEkj6YtvGyUW11I\nK6lla0OwuHOJvfsFKsUbIRI+RmVidPRMa3NjRJfEmxtfdSjabZNAN910URdMXHTHcftA9kDhD72n\n8Sle7EgAbIvkHEflle5+UOJTWGbJSezgP4tGbqenZGS6okLmDoNEagRyeUGo0sCtEfMClFyR6FS0\npGZ0lRE7F0RsQMi3X4Wim+pNhuySKRrSfFbqq0Xl2+/yVV3IYjKWBI6nnyLbO0vcFefJTgvNpQR2\nCrF4pvdZxuUtZ1//2Q==\n";
        $json = [
            'object_guid' => $object_guid,
            'rnd' => (string) random_int(12321, 998877),
            'file_inline' => [
                'file_id' => $file_id,
                'mime' => 'mp4',
                'dc_id' => $dc_id,
                'access_hash_rec' => $hash,
                'file_name' => "$file_name.mp4",
                'thumb_inline' => $thumb,
                'width' => $width ? $width : 480,
                'height' => $height ? $height : 272,
                'time' => $time ? $time : 8000,
                'size' => $size ? $size : 883078,
                'type' => 'Gif'
            ]
        ];
        if (!empty($caption)) {
            $pattern = self::Metadata($caption);
            $json['text'] = $pattern["text"];
            isset($pattern["metadata"]) ? $json["metadata"] = $pattern["metadata"] : null;
        }
        return $this->config->setJson("sendMessage", $json);
    }

    /**
     * Summary of getStickersBySetIDs
     * @param array $sticker_ids ["stickrId-1","stickrId-2",...]
     * @return mixed
     */
    public function getStickersBySetIDs(array $sticker_ids)
    {
        return $this->config->setJson("getStickersBySetIDs", ["sticker_set_ids" => $sticker_ids]);
    }

    public function uploadAvatar($thumbnail_id, $main_id)
    {
        return $this->config->setJson("uploadAvatar", ["thumbnail_file_id" => $thumbnail_id, "main_file_id" => $main_id]);
    }


    public function on_message(callable $callback)
    {
        $server = $this->config->getDCs()['socket'];
        $loop = Loop::get();
        $connector = new Connector($loop);
        echo "set server connecting : [$server]";
        $this->connect($connector, $server, $callback, $loop, 0);
        $loop->run();
    }

    private function connect(Connector $connector, string $server, callable $callback, $loop, int $attempt)
    {
        self::getChats();
        $connector($server, [], [])
            ->then(function (WebSocket $conn) use ($callback, $loop, $server, $attempt) {
                echo "-> connected \n";
                $connect_data = json_encode([
                    "api_version" => "5",
                    "auth" => Crypto::setAuth($this->account_auth),
                    "data" => "",
                    "method" => "handShake"
                ]);

                $conn->send($connect_data);
                $timer = $loop->addPeriodicTimer(30, function () use ($conn) {
                    $conn->send(json_encode([]));
                    self::getChatsUpdates();
                });

                $conn->on('message', function ($msg) use ($conn, $callback) {
                    $data = json_decode($msg, true);
                    if (isset($data['type'])) {
                        $get_msg = json_decode(Crypto::decrypt($data['data_enc'], true), true);
                        if (isset($get_msg['message_updates']) && isset($get_msg['message_updates'][0]['action']) && $get_msg['message_updates'][0]['action'] == "New") {
                            $callback(new Message($get_msg, $this));
                        }
                    }
                });

                $conn->on('close', function ($code, $reason) use ($timer, $callback, $server, $attempt, $loop) {
                    $connector = new Connector($loop);
                    $loop->cancelTimer($timer);
                    echo "\n-> Connection closed, attempting to reconnect... \n";

                    $delay = min(5, $attempt + 1);
                    $loop->addTimer($delay, function () use ($connector, $server, $callback, $loop, $attempt) {
                        $this->connect($connector, $server, $callback, $loop, $attempt + 1);
                    });
                });
            }, function (Exception $e) use ($callback) {
                exit("\n-> Error Connect Socket\n error message:\n{$e->getMessage()}\n");
            });
    }

    public function addContact($first_name, $last_name, $phone)
    {
        return $this->config->setJson("addAddressBook", [
            "first_name" => $first_name,
            "last_name" => $last_name,
            "phone" => $phone,
        ]);
    }
    public function addChannel(string $title, setChannelType $action, $users_chat_id = null)
    {
        return $this->config->setJson("addChannel", [
            "channel_type" => $action->value,
            "title" => $title,
            "member_guids" => $users_chat_id,
        ]);
    }
    public function setBlockUser(string $member_guid, Block $Action)
    {
        return $this->config->setJson("setBlockUser", [
            "action" => $Action->value,
            "user_guid" => $member_guid,
        ]);
    }
    public function PinMessage(string $object_guid, int $message_id, Pin $action)
    {
        return $this->config->setJson("setPinMessage", ["object_guid" => $object_guid, "message_id" => $message_id, "action" => $action->value]);
    }

    public function deleteUserChat(string $object_guid, int $last_message_id = 0)
    {
        return $this->config->setJson("deleteUserChat", ["user_guid" => $object_guid, "last_deleted_message_id" => $last_message_id]);
    }

    public function getPendingObjectOwner(string $object_guid)
    {
        return $this->config->setJson("getPendingObjectOwner", ["object_guid" => $object_guid]);
    }

    public function actionOnJoinRequest(string $group_guid, string $user_guid, joinGroup $action = joinGroup::Accept)
    {
        return $this->config->setJson("actionOnJoinRequest", [
            "object_guid" => $group_guid,
            "object_type" => "Group",
            "action" => $action,
            "user_guid" => $user_guid
        ]);
    }

    public function createJoinLink(string $group_guid, string $title = "sanfBOT", bool $request_needed = true, int $usage_limit = 0, int $time = 0)
    {
        $json = [
            "object_guid" => $group_guid,
            "title" => $title,
            "request_needed" => $request_needed,
            "usage_limit" => $usage_limit
        ];
        $time === 0 ? null : $json["expire_time"] = $time;
        return $this->config->setJson("createJoinLink", $json);
    }

    public function getJoinLinks(string $object_guid)
    {
        return $this->config->setJson("getJoinLinks", ["object_guid" => $object_guid]);
    }

    public function checkUserUsername(string $username)
    {
        return $this->config->setJson("checkUserUsername", ["username" => $username]);
    }

    public function updateUsername(string $username)
    {
        return $this->config->setJson("updateUsername", ["username" => $username]);
    }

    public function getJoinRequests(string $object_guid)
    {
        return $this->config->setJson("getJoinRequests", ["object_guid" => $object_guid]);
    }

    public function setProfileSetting(setSetting|null $phone = null, setSetting|null $online = null, setSetting|null $photo = null, setSetting|null $date = null, setSetting|null $forward = null, setSetting|null $join = null)
    {
        $json = [
            "settings" => [],
            "update_parameters" => []
        ];
        if (is_null($phone) && is_null($online) && is_null($photo) && is_null($date) && is_null($forward) && is_null($join))
            return ["ERROR" => "one parameter is required"];
        if (!is_null($date)) {
            $json['settings']["show_my_birth_date"] = $date;
            $json["update_parameters"][] = "show_my_birth_date";
        }
        if (!is_null($online)) {
            $json['settings']["show_my_last_online"] = $online;
            $json["update_parameters"][] = "show_my_last_online";
        }
        if (!is_null($photo)) {
            $json['settings']["show_my_profile_photo"] = $photo;
            $json["update_parameters"][] = "show_my_profile_photo";
        }
        if (!is_null($forward)) {
            $json['settings']["link_forward_message"] = $forward;
            $json["update_parameters"][] = "link_forward_message";
        }
        if (!is_null($join)) {
            $json['settings']["can_join_chat_by"] = $join;
            $json["update_parameters"][] = "can_join_chat_by";
        }
        if (!is_null($phone)) {
            $json['settings']["show_my_phone_number"] = $phone;
            $json["update_parameters"][] = "show_my_phone_number";
        }
        // return $json;
        return $this->config->setJson("setSetting", $json);
    }

    /**
     * Summary of channelPreviewByJoinLink
     * @param string $link example link : BHBIEDGF0MLPBTOLUOZVDXSDUKKHYGZT
     * @return mixed
     */
    public function channelPreviewByJoinLink(string $link)
    {
        return $this->config->setJson("channelPreviewByJoinLink", ["hash_link" => $link]);
    }

    /**
     * Summary of groupPreviewByJoinLink
     * @param string $link example link : BHBIEDGF0MLPBTOLUOZVDXSDUKKHYGZT
     * @return mixed
     */
    public function groupPreviewByJoinLink(string $link)
    {
        return $this->config->setJson("groupPreviewByJoinLink", ["hash_link" => $link]);
    }

    public function getCommonGroups(string $user_guid)
    {
        return $this->config->setJson("getCommonGroups", ["user_guid" => $user_guid]);
    }

    public function getTranscription(string $object_guid, int|string $message_id,  string $transcription_id = null)
    {
        if (is_null($transcription_id)) {
            $TRPI = self::transcribeVoice($object_guid, $message_id);
            if (isset($TRPI["transcription_id"])) $TRPI = $TRPI["transcription_id"];
            elseif (isset($TRPI["status"]) || $TRPI["status"] == "NotAllowed" || $TRPI["status"] == "NotReady") return false;
            else return ["ERROR" => "INPUT_INVALID", "RESULT" => $TRPI];
        }
        $json = [
            "transcription_id" => is_null($transcription_id) ? $TRPI : $transcription_id,
            "message_id" => $message_id
        ];
        return $this->config->setJson("getTranscription", $json);
    }

    public function transcribeVoice(string $object_guid, int|string $message_id)
    {
        $json = [
            "object_guid" => $object_guid,
            "message_id" => $message_id
        ];
        return $this->config->setJson("transcribeVoice", $json);
    }

    private function downloadFile_url($url, $fileName, $mime = null)
    {
        $fileMime = preg_match('/\.([^.]+)(\?.*)?$/', $url, $FMime) ? $FMime[0] : ".zip";
        $fileMime = is_null($mime) ? $fileMime : $mime;
        $fileName = "$fileName$fileMime";
        $fp = fopen($fileName, 'w+');
        if ($fp === false) {
            return ["status" => false, "name" => $fileName];
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return ["status" => true, "name" => $fileName];
    }

    public function Metadata($markdown)
    {
        $meta_data_parts = [];
        $markdown_re = '/```(.*?)```|\*\*(.*?)\*\*|`(.*?)`|__(.*?)__|--(.*?)--|~~(.*?)~~|\|\|(.*?)\|\||\[(.*?)\]\(\s*(https?:\/\/\S+|g0|u0|c0|[^\s]+)\s*\)/us';
        while (preg_match($markdown_re, $markdown, $matches)) {
            $group = $matches[0];
            $startIndex = mb_strpos($markdown, $group);
            $length = mb_strlen($group);
            $markdown = preg_replace($markdown_re, '$1$2$3$4$5$6$7$8', $markdown, 1);

            if (mb_strpos($group, '```') === 0) {
                $lang = explode("\n", trim($group, '`'))[0];
                $meta_data_parts[] = ['type' => 'Pre', 'language' => $lang, 'from_index' => $startIndex, 'length' => $length - 6];
            } elseif (mb_strpos($group, '**') === 0) {
                $meta_data_parts[] = ['type' => 'Bold', 'from_index' => $startIndex, 'length' => $length - 4];
            } elseif (mb_strpos($group, '`') === 0) {
                $meta_data_parts[] = ['type' => 'Mono', 'from_index' => $startIndex, 'length' => $length - 2];
            } elseif (mb_strpos($group, '__') === 0) {
                $meta_data_parts[] = ['type' => 'Italic', 'from_index' => $startIndex, 'length' => $length - 4];
            } elseif (mb_strpos($group, '--') === 0) {
                $meta_data_parts[] = ['type' => 'Underline', 'from_index' => $startIndex, 'length' => $length - 4];
            } elseif (mb_strpos($group, '~~') === 0) {
                $meta_data_parts[] = ['type' => 'Strike', 'from_index' => $startIndex, 'length' => $length - 4];
            } elseif (mb_strpos($group, '||') === 0) {
                $meta_data_parts[] = ['type' => 'Spoiler', 'from_index' => $startIndex, 'length' => $length - 4];
            } else {
                $url = str_replace(")", "", $matches[9]) ?? "https://sanfai.ir/";
                $length = mb_strlen($matches[8]);
                $mention_text_object_type = 'hyperlink';

                if (mb_strpos($url, 'u') === 0) {
                    $mention_text_object_type = 'User';
                } elseif (mb_strpos($url, 'g') === 0) {
                    $mention_text_object_type = 'Group';
                } elseif (mb_strpos($url, 'c') === 0) {
                    $mention_text_object_type = 'Channel';
                }

                $meta_data_part = [
                    'type' => $mention_text_object_type == 'hyperlink' ? 'Link' : 'MentionText',
                    'from_index' => $startIndex,
                    'length' => $length
                ];

                if ($mention_text_object_type == 'hyperlink') {
                    $meta_data_part['link'] = ['type' => $mention_text_object_type, 'hyperlink_data' => ['url' => $url]];
                } else {
                    $meta_data_part['mention_text_object_guid'] = $url;
                    $meta_data_part['mention_text_object_type'] = $mention_text_object_type;
                }
                $meta_data_parts[] = $meta_data_part;
            }
        }

        $result = ['text' => trim($markdown)];
        if (count($meta_data_parts) > 0) {
            $result['metadata'] = ['meta_data_parts' => $meta_data_parts];
        }

        return array_reverse($result);
    }
    private function setLogError($data)
    {
        file_put_contents("error-bot.log", $data, FILE_APPEND);
    }
}
