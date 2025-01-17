<?php

namespace Sanf;

use getID3;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sanf\Tools\SignIn;
use Sanf\Crypto\Crypto;
use Sanf\Enums\{
    Application,
    Sort,
    uploadType,
    Model,
};

class Rubino
{

    private string $auth;
    public string $profile_id;
    private $client;

    /**
     * set self settings
     * @param string|null $session | session name
     * @param array $option setting :  auth  | custom profile_id
     */
    public function __construct(string|null $session = null,array $option = [])
    {
        $auth = isset($option["auth"]) && !empty($option["auth"]) ? $option["auth"] : null;
        $custom_profileId = isset($option["profile_id"]) && !empty($option["profile_id"]) ? $option["profile_id"] : null;
        if (is_null($session)) is_null($auth) || strlen(string: $auth) != 32 ? exit("auth invalid.\n") : $this->auth = $auth;
        elseif (!is_null($session)) {
            if (file_exists("$session.sr")) self::set_run($session);
            else {
                new SignIn($session, Application::Rubika);
                exit("Please run the program again.\n");
            }
        }
        self::select_account($custom_profileId);
    }

    private function select_account($custom_profileId)
    {
        $this->client = new Client();
        if (php_sapi_name() == "cli") {
            if (is_null($custom_profileId)) {
                $getProfile = $this->getPIDS();
                if (isset($getProfile["profiles"])) {
                    if (count($getProfile["profiles"]) === 1) {
                        $this->profile_id = $getProfile["profiles"][0]["id"];
                        echo "profile : " . $getProfile["profiles"][0]["id"] . "\n";
                    } else {
                        $list_ids = "";
                        $number_pages = 0;
                        $default_page = 0;
                        foreach ($getProfile["profiles"] as $prof_id) {
                            $number_pages = $number_pages + 1;
                            $list_ids .= "page number :$number_pages | profile id :" . $prof_id["id"] . " - default page : " . json_encode($prof_id["is_default"]) . "\n";
                            $prof_id["is_default"] ? $default_page = $number_pages : null;
                        }
                        echo "$list_ids\n";
                        echo "select Page number (default = $default_page): ";
                        $page_id = trim(fgets(STDIN));
                        empty($page_id) ? $page_id = $default_page : $page_id;
                        if (is_numeric($page_id)) {
                            if (isset($getProfile["profiles"][$page_id - 1]["id"])) {
                                $this->profile_id = $getProfile["profiles"][$page_id - 1]["id"];
                                echo "profile : " . $getProfile["profiles"][$page_id - 1]["id"] . "\n";
                            } else exit("page not found ...\n");
                        } else  exit("inpute invalid ...\n");
                    }
                } else exit(json_encode($getProfile));
            } else $this->profile_id = $custom_profileId;
        } else $this->profile_id = is_null($custom_profileId) ? "" : $custom_profileId;
    }

    private function run($method, $input)
    {
        $client = array(
            "app_name" => "Main",
            "app_version" => "3.8.1",
            "lang_code" => "fa",
            "package" => "app.rbmain.a",
            "platform" => "Android",
            "store" => "Direct",
            "temp_code" => "30"
        );

        $json_data = array(
            "api_version" => "0",
            "auth" => $this->auth,
            "client" => $client,
            "data" => $input,
            "method" => $method
        );

        return $this->post($json_data);
    }

    private function post($data)
    {
        $url = 'https://rubino15.iranlms.ir/';
        $headers = [
            "Accept-Encoding" => "gzip",
            "Connection" => "Keep-Alive",
            "Content-Length" => strlen(trim(json_encode($data))),
            "Content-Type" => "application/json; charset=UTF-8",
            "Host" => parse_url($url, PHP_URL_HOST),
            "User-Agent" => "okhttp/3.12.12",
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $data
            ]);
            $result = json_decode($response->getBody(), true);
            return (isset($result["data"]) && isset($result["status"]) && $result["status"]) ? $result["data"] : $result;
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    private function getPIDS()
    {
        return $this->run(
            "getProfileList",
            [
                "equal" => false,
                "limit" => 10,
                "sort" => "FromMax"
            ]
        );
    }

    private function requestUploadFile(string $path, int $fileSize, bool $is_data, string|bool $fileName = false)
    {
        if (!$fileName) {
            if (!$is_data) {
                $fileMime = preg_match('/\.([^.]+)(\?.*)?$/', $path, $FMime) ? strtolower($FMime[0]) : ".zip";
                if (in_array($fileMime, [".mp4", ".jpg", ".jpeg", ".png"])) $fileName = "1" . ($fileMime == ".mp4" ? $fileMime : ".jpg");
                else return false;
            } else list($fileName, $fileMime) = ["1.jpg", ".jpg"];
        } else {
            $fileMime = self::getFileMime($fileName);
        }
        $json = [
            "file_name" => $fileName,
            "file_size" => $fileSize,
            "file_type" => $fileMime == ".mp4" ? uploadType::Video : uploadType::Picture,
            "profile_id" => $this->profile_id
        ];
        return $this->run("requestUploadFile", $json);
    }

    private function UploadFile(string $path, string|bool $fileName = false, callable $progress = null, bool $is_data = false)
    {
        $chunk_size = 128 * 1024;
        $file_name = pathinfo($path, PATHINFO_BASENAME);
        $file_mime = pathinfo($path, PATHINFO_EXTENSION);
        $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);

        $file_content = $is_data ? $path : @file_get_contents($path, false, $context);
        if ($file_content === FALSE)
            return [
                "error" => "upload file...",
                "file_id" => false,
                "hash_file_receive" => false
            ];

        $total_parts = (int) ceil(strlen($file_content) / $chunk_size);
        $file_size = strval(strlen($file_content));

        $file_info = $this->requestUploadFile($path, $file_size, $is_data, $fileName);
        if ($file_info === false) {
            return [
                "error" => "Unsupported file extension... [MP4 | JPG | PNG | JPEG] | inpute :" . self::getFileMime($path),
                "file_id" => false,
                "hash_file_receive" => false
            ];
        }
        if (isset($file_info["file_id"])) {
            $file_id = $file_info["file_id"];
            $hash_file_request = $file_info["hash_file_request"];
            $server_url = $file_info["server_url"];
        } else return [
            "error" => "server error | upload file...",
            "file_id" => false,
            "hash_file_receive" => false
        ];

        for ($part_number = 1; $part_number <= $total_parts; $part_number++) {

            $start = ($part_number - 1) * $chunk_size;
            $end = min($part_number * $chunk_size, strlen($file_content));
            $data = substr($file_content, $start, $end - $start);
            $response = $this->UploadFileToServer(
                $server_url,
                $data,
                $file_id,
                strval($part_number),
                $hash_file_request,
                strval($total_parts)
            );
            if (is_callable($progress)) $progress("file size (byte) : {$file_size} | part count : $part_number|$total_parts", json_encode($response));

            if ($response['data'] != null) {
                return [
                    "file_id" => $file_id,
                    "hash_file_receive" => $response["data"]["hash_file_receive"]
                ];
            } elseif ($response["status"] == "ERROR_TRY_AGAIN" || $response["status"] == "ERROR_GENERIC") {
                return [
                    "error" => "upload file...",
                    "file_id" => false,
                    "hash_file_receive" => false
                ];
            }
        }
    }
    private function UploadFileToServer(string $url, string $data, $file_id, $part_number, $hash, $total_parts)
    {
        $response = $this->client->post($url, [
            'body' => $data,
            'headers' => array(
                "Accept-Encoding" => "gzip",
                "auth" => $this->auth,
                "Connection" => "Keep-Alive",
                "Content-Length" => strlen($data),
                "Content-Type" => "application/octet-stream",
                "file-id" => $file_id,
                "hash-file-request" => $hash,
                "Host" => parse_url($url, PHP_URL_HOST),
                "part-number" => $part_number,
                "total-part" => $total_parts,
                "User-Agent" => "okhttp/3.12.12"
            )
        ]);
        $Cipher = $response->getBody()->getContents();
        return json_decode($Cipher, true);
    }

    public function addPost(string $path, $caption = null, callable $progress = null)
    {
        $fileMime = preg_match('/\.([^.]+)(\?.*)?$/', $path, $FMime) ? strtolower($FMime[0]) : ".zip";
        if (in_array($fileMime, [".mp4", ".png", ".jpg", ".jpeg"])) $fileName = "1$fileMime";
        else return "Unsupported file ... [MP4 | PNG | JPG | JPEG] inpute : $fileMime";

        if (substr($path, 0, 7) == "http://" || substr($path, 0, 8) == "https://") {
            $download_info = self::downloadFile($path, "rubino");
            if (!$download_info["status"]) return "error download file...";
            else $path = $download_info["name"];
        }


        if ($fileMime == ".mp4") {
            list($width, $height, $duration) = self::getVideoDimensions($path);
            if (!$duration) {
                echo "The file was not recognized. Enter the corrections manually\n";
                echo "video time (s) : ";
                $duration = trim(fgets(STDIN));
                echo "video width : ";
                $width = trim(fgets(STDIN));
                echo "video height : ";
                $height = trim(fgets(STDIN));
                echo "\n";
                // if (!empty($width) && is_numeric($width)) $width = 1000;
                // if (!empty($height) && is_numeric($height)) $height = 1000;
                // if (!empty($duration) && is_numeric($duration)) $duration = 60;
            }
            $GIT = "https://uploadkon.ir/uploads/2c9302_2556277282799634.jpg";
            $thumbnail = self::UploadFile($GIT, false, $progress);
        } else {
            $GIT = self::getImageThumbInline($path);
            $imageInfo = getimagesize($path);
            if ($imageInfo !== false) list($width, $height) = $imageInfo;
            $thumbnail = self::UploadFile($GIT, false, $progress, true);
        }
        $main = self::UploadFile($path, false, $progress);

        if (isset($main["error"])) return $main["error"];
        if (isset($thumbnail["error"])) return $thumbnail["error"];

        if ($fileMime == ".mp4") {
            $json = [
                "caption" => (string) is_null($caption) ? "Upload By Sanf Lib (@coder95)" : $caption,
                "duration" => !empty($duration) ? $duration : 60,
                "file_id" => $main["file_id"],
                "hash_file_receive" => $main["hash_file_receive"],
                "height" => !empty($height) ? $width : 800,
                "is_multi_file" => false,
                "post_type" => uploadType::Video,
                "rnd" => random_int(123231213, 985769748),
                "snapshot_file_id" => $thumbnail["file_id"],
                "snapshot_hash_file_receive" => $thumbnail["hash_file_receive"],
                "tagged_profiles" => [],
                "thumbnail_file_id" => $thumbnail["file_id"],
                "thumbnail_hash_file_receive" => $thumbnail["hash_file_receive"],
                "width" => !empty($width) ? $width : 800,
                "profile_id" => $this->profile_id
            ];
        } else {
            $json = [
                "caption" => (string) is_null($caption) ? "Upload By Sanf Lib (@coder95)" : $caption,
                "file_id" => $main["file_id"],
                "hash_file_receive" => $main["hash_file_receive"],
                "height" => isset($height) ? round($height > 1000 ? 1000 : $height) : 200,
                "is_multi_file" => false,
                "post_type" => uploadType::Picture,
                "rnd" => random_int(123231213, 985769748),
                "tagged_profiles" => [],
                "thumbnail_file_id" => $thumbnail["file_id"],
                "thumbnail_hash_file_receive" => $thumbnail["hash_file_receive"],
                "width" => isset($width) ? round($width > 1000 ? 800 : $width) : 200,
                "profile_id" => $this->profile_id
            ];
        }
        return $this->run(
            "addPost",
            $json
        );
    }

    public function addStory(string $path, callable $progres = null)
    {
        $fileMime = preg_match('/\.([^.]+)(\?.*)?$/', $path, $FMime) ? strtolower($FMime[0]) : ".zip";
        if (in_array($fileMime, [".mp4", ".png", ".jpg", ".jpeg"])) $fileName = random_int(123, 95765) . ".jpg";
        else return "Unsupported file ... [MP4 | PNG | JPG | JPEG] inpute : $fileMime";

        if ($fileMime == ".mp4") {
            if (substr($path, 0, 7) == "http://" || substr($path, 0, 8) == "https://") {
                $download_info = self::downloadFile($path, "rubino-story");
                if (!$download_info["status"]) return "error download file...";
                else $path = $download_info["name"];
            }
            $main = self::UploadFile($path, "output_124973.mp4", $progres);
            if (isset($main["error"])) return $main["error"];

            $thumbnail = self::UploadFile("https://uploadkon.ir/uploads/2c9302_2556277282799634.jpg", "Compressed_Image_1735926357460135764830.jpg", $progres);
            if (isset($thumbnail["error"])) return $thumbnail["error"];

            $snapshot = self::UploadFile("https://uploadkon.ir/uploads/2c9302_2556277282799634.jpg", "Compressed_Image_1735926361280135764831.jpg", $progres);
            if (isset($thumbnail["error"])) return $snapshot["error"];

            $file_type = uploadType::Video;
            list($width, $height, $duration) = self::getVideoDimensions($path);
        } else {
            $thumbnail = self::UploadFile(self::getImageThumbInline($path), $fileName, $progres, true);
            if (isset($thumbnail["error"])) return $thumbnail["error"];
            $main = self::UploadFile($path, random_int(123, 9654) . ".jpg", $progres);
            if (isset($main["error"])) return $main["error"];
            $imageInfo = getimagesize($path);
            if ($imageInfo !== false) list($width, $height) = $imageInfo;
            $file_type = uploadType::Picture;
            $duration = 0;
        }

        $json = [
            "duration" => $duration === 0 ? 0 : $duration * 1000,
            "file_id" => $main["file_id"],
            "hash_file_receive" => $main["hash_file_receive"],
            "height" => isset($height) || !empty($height) ? $height : 1280,
            "profile_id" => $this->profile_id,
            "rnd" => random_int(1222426, 9988759749),
            "story_type" => $file_type->value,
            "thumbnail_file_id" => $thumbnail["file_id"],
            "thumbnail_hash_file_receive" => $thumbnail["hash_file_receive"],
            "width" => isset($width) || !empty($width) ? $width : 800
        ];
        if ($fileMime == ".mp4") {
            $json["snapshot_file_id"] = $snapshot["file_id"];
            $json["snapshot_hash_file_receive"] = $snapshot["hash_file_receive"];
        }
        return $this->run(
            "addStory",
            $json
        );
    }

    public function updateProfilePhoto(string $main_filePath, string $thumbnail_filePath, callable $progres = null)
    {
        if (
            !in_array(self::getFileMime($main_filePath), [".png", ".jpg", ".jpeg"]) &&
            !in_array(self::getFileMime($thumbnail_filePath), [".png", ".jpg", ".jpeg"])
        ) return "Unsupported file extension... [PNG|JPG|JPEG]";

        $th_info = self::UploadFile($thumbnail_filePath, "avatarThumb.jpg", $progres);
        $main_info = self::UploadFile($main_filePath, "avatarMain.jpg", $progres);
        if (isset($main_info["error"])) return $main_info["error"];
        if (isset($th_info["error"])) return $th_info["error"];

        $json = [
            "file_id" => $main_info["file_id"],
            "hash_file_receive" => $main_info["hash_file_receive"],
            "thumbnail_file_id" => $th_info["file_id"],
            "thumbnail_hash_file_receive" => $th_info["hash_file_receive"],
            "profile_id" => $this->profile_id
        ];
        return self::run("updateProfilePhoto", $json);
    }

    public function getProfileList(bool $equal = false, int $limit = 10, Sort $sort = Sort::Max)
    {
        return self::run("getProfileList", [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value
        ]);
    }
    public function getMyProfileInfo(string|null $profile_id = null)
    {
        return self::run("getMyProfileInfo", ["profile_id" => is_null($profile_id) ? $this->profile_id : $profile_id]);
    }
    public function getProfilesStories(string|null $profile_id = null, int $limit = 100)
    {
        return self::run("getProfilesStories", [
            "limit" => $limit,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ]);
    }
    public function getRecentFollowingPosts(
        string|null $profile_id = null,
        string|null $max_id = null,
        int $limit = 30,
        bool $equal = false,
        Sort $sort = Sort::Max
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ];
        is_null($max_id) ?: $json["max_id"] = $max_id;
        return self::run("getRecentFollowingPosts", $json);
    }
    public function getNewEvents(
        string|null $profile_id = null,
        int $limit = 50,
        Sort $sort = Sort::Max,
        bool $equal = false

    ) {
        return self::run("getNewEvents", [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ]);
    }
    /**
     * Summary of viewRecentFollowingPosts
     * @param array $post_ids => input : array("post_id-1","post_id-2",...,"post_id-100");
     * @param string $profile_id 
     */
    public function viewRecentFollowingPosts(
        array $post_ids,
        string|null $profile_id = null
    ) {
        return self::run("viewRecentFollowingPosts", [
            "post_ids" => $post_ids,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ]);
    }
    public function getProfileHighlights(
        string $target_profile_id,
        string|null $profile_id = null,
        bool $equal = false,
        int $limit = 10,
        Sort $sort = Sort::Max

    ) {
        return self::run("getProfileHighlights", [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value,
            "target_profile_id" => $target_profile_id,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ]);
    }
    public function getMyProfilePosts(
        string $profile_id = null,
        string|null $max_id = null,
        bool $equal = false,
        int $limit = 51,
        Sort $sort = Sort::Max
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ];
        is_null($max_id) ?: $json["max_id"] = $max_id;
        return self::run("getMyProfilePosts", $json);
    }
    public function removeRecord(
        string $record_id,
        Model $model = Model::Post,
        string|null $profile_id = null
    ) {
        return self::run("removeRecord", [
            "model" => $model->value,
            "record_id" => $record_id,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ]);
    }
    public function getBookmarkedPosts(
        string|null $profile_id = null,
        bool $equal = false,
        int $limit = 51,
        Sort $sort = Sort::Max
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value,
            "profile_id" =>  is_null($profile_id) ? $this->profile_id : $profile_id
        ];
        return self::run("getBookmarkedPosts", $json);
    }
    public function getExplorePosts(string|null $max_ix = null, Sort $sort = Sort::Max, bool $equal = false, int $limit = 51)
    {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort->value,
            "profile_id" => $this->profile_id
        ];
        is_null($max_ix) ?: $json["max_id"] = $max_ix;
        return self::run("getExplorePosts", $json);
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
        return $changedImage;
    }
    public static function getVideoDimensions($filePath)
    {
        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($filePath);
        if (isset($fileInfo['video'])) {
            $width = $fileInfo['video']['resolution_x'];
            $height = $fileInfo['video']['resolution_y'];
            $time = $fileInfo["playtime_seconds"];
            return [
                $width,
                $height,
                floor($time)
            ];
        }
        return [
            false,
            false,
            false
        ];
    }
    private function config_set($data, $key)
    {
        $key = hash('sha256', Crypto::setAuth($key), true);
        $text = base64_decode($data);
        $iv_dec = substr($text, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $text = substr($text, offset: openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv_dec);
    }
    private function set_run(string|null $session)
    {
        $data = json_decode(
            self::Config_set(file_get_contents("$session.sr"), "sanf/rush-client"),
            true
        );
        if ($data && isset($data["auth"])) {
            $this->auth = $data["auth"];
        }
    }
    private static function getFileMime($filePath, bool $lower = true): string
    {
        $fileMime = preg_match('/\.([^.]+)(\?.*)?$/', $filePath, $FMime) ? strtolower($FMime[0]) : ".zip";
        return $lower ? strtolower($fileMime) : $fileMime;
    }
    private function downloadFile($url, $fileName, $mime = null)
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
}
