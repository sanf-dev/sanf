<?php

namespace Sanf\Enums;

enum ChatTypes: string
{
    case Service = "Service";
    case Group = "Group";
    case Channel = "Channel";
    case User = "User";
    case Contact = "Contact";
    case NonContact = "NonConatct";
    case Mute = "Mute";
    case Read = "Read";
    case Bot = "Bot";
}
