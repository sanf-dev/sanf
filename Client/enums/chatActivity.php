<?php

namespace Sanf\Enums;

enum chatActivity: string
{
    case Type = "Typing";
    case Upload = "Uploading";
    case  Record = "Recording";
}
