<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\BillingUnlyme;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property bool $IncludeInMobile
 * @property bool $IncludeInDesktop
 * @property array $GroupsLimits
 * @property array $BusinessTenantLimits
 * @property array $GroupwareModules
 * @property array $ReservedList
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "",
            ),
            "IncludeInMobile" => new SettingsProperty(
                true,
                "bool",
                null,
                "",
            ),
            "IncludeInDesktop" => new SettingsProperty(
                true,
                "bool",
                null,
                "",
            ),
            "BusinessTenantLimits" => new SettingsProperty(
                [
                    [
                        "AliasesCount" => 20,
                        "EmailAccountsCount" => 10,
                        "MailStorageQuotaMb" => 102400,
                        "FilesStorageQuotaMb" => 102400
                    ]
                ],
                "array",
                null,
                "Limits that are applied to tenants marked as business tenants",
            ),
            "GroupwareModules" => new SettingsProperty(
                [
                    "CorporateCalendar",
                    "S3CorporateFilestorage",
                    "SharedContacts",
                    "SharedFiles",
                    "TeamContacts",
                    "OutlookSyncWebclient"
                ],
                "array",
                null,
                "List of modules that are not available in tenants without Groupware functionality",
            ),
            "ReservedList" => new SettingsProperty(
                [],
                "array",
                null,
                "List of reserved usernames that cannot be used for new accounts",
            ),
            "StripeSecretKey" => new SettingsProperty(
                "",
                "string",
                null,
                "Stripe secret key",
            ),
            "StripeWebhookUrl" => new SettingsProperty(
                "",
                "string",
                null,
                "Stripe webhook url",
            ),
        ];
    }
}
