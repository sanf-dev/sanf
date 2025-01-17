<p align="center"><b>Rush | Rubika - Shad API</b></p>

# What's New?

    - Update v2.1.1

## Updates:

    - Added Rubino Android Client
    - Added Auto-Login for a Source (Beta | Login on Android)
    - Synced with the Latest Rubika and Shad Updates
    - Added Method to Play Video from Link or File Path in Live with ffmpeg Tool (if installed)

## Debugged:

    - Debugged Methods and Parts of the Library
    - Fixed Issue of Not Running on Host
    - Changed Method of Calling the Library

# **Table of Contents**

- [Description](https://github.com/sanf-dev/sanf?tab=readme-ov-file#rubika-client)
- [Installation](https://github.com/sanf-dev/sanf?tab=readme-ov-file#installation)
- [Create a Bot](https://github.com/sanf-dev/sanf?tab=readme-ov-file#creating-a-bot)
- [Rubika and Shad Methods](https://github.com/sanf-dev/sanf?tab=readme-ov-file#methods)
- [Socket Methods](https://github.com/sanf-dev/sanf?tab=readme-ov-file#socket-methods)

# Rubika Client

A simple yet practical client for creating **self bots** for Rubika and Shad, supporting three platforms: **Android, Web, and PWA**. This client is designed for building self bots for various purposes, including **group management, entertainment, assistance, gaming, and more**.

# Installation:

    ```bash
    composer require sanf/rush
    ```

# Creating a Bot:

```php
// Loading the classes
require_once "vendor/autoload.php";

use Sanf\Client;
use Sanf\Tools\Message;

// Entering login information and setting up the client
$self = new Client('rush');

// Creating an anonymous function
$action = function (Message $update) use ($self) {
    // Receiving text updates
    $text = $update->text();

    // Creating a command for reacting to a message, for example:
    if ($text == "hello") {
        // Sending the response to the user using the reply command
        $update->reply("Hello, nice to meet you!\nMy name is Sanf.!!!!!");
    }
};

// Connecting to the WebSocket
$self->on_message($action);
```

**Congratulations, you have now set up a client!**

# Rubika and Shad Client Configuration

## Using Auto-Login

    ```php
    // Use your custom session name instead of Sanf
    $self = new Client("Sanf");
    ```

## Manually Entering Information

    ```php
    // Use your custom session name instead of Sanf
    $self = new Client("Sanf");

    /*
    To manually enter the information, you need to provide 4 parameters:

    1. auth        | Account ID
    2. key         | Private Key
    3. platform    | Platform (Web, Android, PWA - PWA is not available for Shad, default is Web)
    4. application | Application
    */

    // For example, our data will be as follows
    $option = [
        "auth" => "your Auth key",
        "key" => "your Private key",
        "platform" => "select Platform",
        "application" => "select Application"
    ];

    // For Rubika
    $option = [
        "auth" => "your Auth key",
        "key" => "your Private key",
        "platform" => Platform::PWA, // Can be changed to Web or Android
        "application" => Application::Rubika // Choosing an application
    ];

    // For Shad
    $option = [
        "auth" => "your Auth key",
        "key" => "your Private key",
        "platform" => Platform::Web, // Can be changed to Android - PWA is not available for Shad
        "application" => Application::Shad // Choosing an application
    ];

    // Now we provide the information to the client
    // Note that you must set the first value to null
    $self = new Client(null, $option);
    ```

# Rubino Configuration

**Rubino configurations are similar to Rubika, but for manual entry, only `auth` is used.**

## Using Auto-Login

    ```php
    // Use your custom session name instead of Sanf
    $self = new Rubino("Sanf");
    ```

## Manually Entering Information

    ```php
    /*
    In this section, you have 1 mandatory parameter and 1 optional parameter:
    1. auth        | Account ID (mandatory)
    2. profile_id  | Custom Profile ID (optional, if not entered, the bot will automatically retrieve all profile IDs and you will choose which account it will operate on)
    */

    // For example, our data will be as follows
    $option = [
        "auth" => "your auth key", // Mandatory
        "profile_id" => "custom profile id" // Optional
    ];

    // Now we provide the information to the client
    $self = new Rubino(null, $option);
    ```

# Rubika and Shad Methods

| Method               | Description                                        |
| -------------------- | -------------------------------------------------- |
| addChannel           | Add a new channel to your account.                 |
| addGroup             | Add a new group.                                   |
| addToMyGifSet        | Add a GIF to your personal GIF list.               |
| createGroupVoiceChat | Create a new voice chat group.                     |
| deleteMessages       | Delete messages from a chat.                       |
| getAvatars           | Fetch the profile picture using the provided GUID. |
| getChats             | Retrieve the list of chats available to you.       |
| getChatsUpdates      | Receive updates on chat activity.                  |
| getContact           | Get a list of your contacts.                       |
| getFolders           | Receive account folders.                           |
| getGroupAdminMembers | Get the members who are admins of a group.         |
| getGroupInfo         | Get information about a group.                     |
| getGroupLink         | Retrieve the link to the group.                    |
| getGroupOnlineCount  | Get the number of online members in a group.       |
| getInfoByUsername    | Get information about a user using their username. |
| getMessages          | Retrieve a list of messages from a specific chat.  |
| getMessagesByID      | Retrieve a message by its ID.                      |
| getMySessions        | Get a list of your active sessions.                |

# Socket Methods

| Method                    | Activity                                                               |
| ------------------------- | ---------------------------------------------------------------------- |
| author_guid               | Get the unique identifier (GUID) of the message sender.                |
| chat_type                 | Determine the type of chat (individual or group) based on the context. |
| count_unseen              | Retrieve the count of unread messages in a chat.                       |
| deleteMessage             | Remove a specific message using its ID.                                |
| editMessage               | Modify an existing message using its ID.                               |
| file_inline               | Handle non-text messages and their corrections.                        |
| forward_message_id        | Get the ID of a forwarded message.                                     |
| forward_object_guid       | Retrieve the GUID of the message being forwarded.                      |
| groupAccess               | Get chat access information (who can access the chat).                 |
| has_link                  | Determine if the message contains a hyperlink.                         |
| is_forward - forward_from | Identify if the message was forwarded from another chat.               |
| is_group                  | Check if the message was sent in a group chat.                         |
| is_private                | Verify if the message was sent from a private chat.                    |
| last_message_id           | Get the ID of the last message sent in the chat.                       |
| message_id                | Retrieve the ID of the last sent message.                              |
| message_type              | Identify the type of the sent message (text, image, etc.).             |
| reply                     | Send a reply to the last message received.                             |
| reply_message_id          | Get the ID of the message to which the current message is a reply.     |
| seen                      | Mark the message as read.                                              |
| setReaction               | Add a reaction (emoji) to the message.                                 |
| text                      | Retrieve the content of the received message.                          |
| title                     | Get the title of the current chat.                                     |
| getData                   | Receive all the output data from the websocket.                        |

**Need help? Send a message on [Telegram](https://t.me/coder95) or [Rubika](https://rubika.ir/coder95) .!**

<hr>
<p align="center">
    We hope you have enjoyed it ❤️.
    <br><br>
    Perseverance is the key to success !
</p>
