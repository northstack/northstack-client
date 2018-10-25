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
 */
class OrgPermissionName extends AbstractEnumeration
{
    const PERM_SAPP_CREATE = 'Create App';
    const PERM_SAPP_DEPLOY = 'Deploy App';
    const PERM_MANAGE_USERS = 'Manage Organization Users';
    const PERM_MANAGE_BILLING = 'Manage Billing';
    const PERM_MANAGE_ORG = 'Manage Organization Details';
    const PERM_MANAGE_ASSETS = 'Manage App Assets';
    const PERM_MANAGE_DATABASE = 'Manage App Database';
    const PERM_VIEW_LOGS = 'View Logs';
    const PERM_CANCEL = 'Cancel Organization Account';
    const PERM_MANAGE_SAPPS = 'Manage Apps';
}
