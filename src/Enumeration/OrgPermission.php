<?php


namespace NorthStack\NorthStackClient\Enumeration;


use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static self PERM_SAPP_CREATE
 * @method static self PERM_SAPP_DEPLOY
 * @method static self PERM_MANAGE_USERS
 * @method static self PERM_MANAGE_BILLING
 * @method static self PERM_MANAGE_ORG
 * @method static self PERM_MANAGE_ASSETS
 * @method static self PERM_MANAGE_DATABASE
 * @method static self PERM_VIEW_LOGS
 * @method static self PERM_CANCEL
 * @method static self PERM_MANAGE_SAPPS
 * @method static self PERM_SAPP_SECRETS
 * @method static self PERM_STACK_MANAGE
 */
class OrgPermission extends AbstractEnumeration
{
    const PERM_SAPP_CREATE = 1;
    const PERM_SAPP_DEPLOY = 2;
    const PERM_MANAGE_USERS = 4;
    const PERM_MANAGE_BILLING = 8;
    const PERM_MANAGE_ORG = 16;
    const PERM_MANAGE_ASSETS = 32;
    const PERM_MANAGE_DATABASE = 64;
    const PERM_VIEW_LOGS = 128;
    const PERM_CANCEL = 256;
    const PERM_MANAGE_SAPPS = 512;
    const PERM_SAPP_SECRETS = 1024;
    const PERM_STACK_MANAGE = 2048;
}
