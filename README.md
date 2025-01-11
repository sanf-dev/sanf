<p align="center"><b>Rush | Rubika - Shad API</b></p>

**Table of Contents**
>[Description](https://github.com/sanf-dev/sanf?tab=readme-ov-file#rubika-client)<br>
[Installation](https://github.com/sanf-dev/sanf?tab=readme-ov-file#installation)<br>
[Create a Bot](https://github.com/sanf-dev/sanf?tab=readme-ov-file#creating-a-bot)<br>
[Rubika and Shad Methods](https://github.com/sanf-dev/sanf?tab=readme-ov-file#methods)<br>
[Socket Methods](https://github.com/sanf-dev/sanf?tab=readme-ov-file#socket-methods)

# Rubika Client

A simple yet practical client for creating **self bots** for Rubika and Shad, supporting three platforms: **Android, Web, and PWA**. This client is designed for building self bots for various purposes, including **group management, entertainment, assistance, gaming, and more**.

## Installation

To get started, use the following command to install:

```bash
composer require sanf/rush
```

## Creating a Bot

1. Create a file with your preferred name.
2. Include the vendor autoload file.

   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

3. Use the following import statements to include the client.

   ```php
   use Sanf\Rubika;
   use Sanf\Tools\Message;
   use Sanf\Enums\{
       Application,
       Platform
   };
   ```

4. Now, instantiate the bot class.

   ```php
   $auth = "your Auth Key";
   $key = "your privateKey";
   $self = new Rubika($auth, $key, Platform::Android, Application::Rubika);
   ```

   ### Note:

   To use other platforms, you can specify:

   ```php
   // For Rubika:
   Platform::Android
   Platform::Web
   Platform::PWA

   // For Shad:
   Platform::Android
   Platform::Web
   ```

   _Note: Shad does not support the PWA platform._

   To use the self bots for Rubika and Shad, simply change the application type:

   ```php
   // For Rubika:
   Application::Rubika
   // For Shad:
   Application::Shad
   ```

   **Now just use the command you want!**

   ### Example:

   ```php
   // Get Chats
   $res = $self->getChats();
   echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

   // Send Message
   $res = $self->sendMessage("target guid", "text", "reply message id (optional, int|string)");
   ```

5. Now, use the following command to connect to the websocket.

   ```php
   // We create an anonymous function.

   $bot = function (Message $update) use ($self) {
       // Receive new messages
       $text = $update->text();
       // Condition for monitoring received messages.
       if (in_array($text, ["سلام", "hello"])) {
           $update->reply("Hello from **rush** Client ;)");
       }
   };
   // Connect to websockets
   $self->on_message($bot);
   ```

**Congratulations! You've now created a self bot and can add more features to it.**

# Methods

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
| getMySessions        | Get a list of your active sessions                 |

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

**Do you need help? Feel free to reach out on [Rubika](https://rubika.ir/coder95) or [Telegram](https://t.me/coder95)!**

<hr>
<p align="center">
    We hope you have enjoyed it ❤️.
    <br><br>
    Perseverance is the key to success !
</p>
