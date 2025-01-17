<?php

require_once "vendor/autoload.php";

use Sanf\Rubino;

// Using automatic login
$self = new Rubino("rush");

// Receiving self account information
echo json_encode($self->getMyProfileInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
