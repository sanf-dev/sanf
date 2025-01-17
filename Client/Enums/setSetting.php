<?php

namespace Sanf\Enums;

/**
 * Summary of setSetting
 * everybody = نمایش برای همه
 * myContacts = نمایش برای مخاطبین
 * nobody = نمایش نده
 */
enum setSetting: string
{
    case Everybody = "Everybody";
    case MyContacts = "MyContacts";
    case Nobody = "Nobody";
}
