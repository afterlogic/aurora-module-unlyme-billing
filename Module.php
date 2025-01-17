<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\BillingUnlyme;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Core\Models\Tenant;
use Aurora\System\Enums\UserRole;

/**
 * Provides user groups.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2025, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    public function init()
    {
        // @TODO review MailQuota check
        $this->subscribeEvent('Mail::CreateAccount::after', array($this, 'onAfterCreateAccount'));

        $this->subscribeEvent('Files::GetSettingsForEntity::after', array($this, 'onAfterGetSettingsForEntity'));

        $this->subscribeEvent('Core::CreateUser::before', array($this, 'onBeforeCreateUser'));
        $this->subscribeEvent('Core::CreateUser::after', array($this, 'onAfterCreateUser'));
        $this->subscribeEvent('Core::CreateTenant::after', array($this, 'onAfterCreateTenant'));
        $this->subscribeEvent('Core::UpdateTenant::after', array($this, 'onAfterUpdateTenant'));

        // check if mailbox is allowed for creation
        $this->subscribeEvent('Mail::CreateAccount::before', array($this, 'onBeforeCreateAccount'));
        $this->subscribeEvent('Mail::IsEmailAllowedForCreation::after', array($this, 'onAfterIsEmailAllowedForCreation'));

        $oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();

        if ($oAuthenticatedUser instanceof User) {
            if ($oAuthenticatedUser->Role === UserRole::SuperAdmin) {
                $this->aAdditionalEntityFieldsToEdit[] = [
                    'DisplayName' => $this->i18N('LABEL_ITS_BUSINESS_TENANT'),
                    'Entity' => 'Tenant',
                    'FieldName' => self::GetName() . '::IsBusiness',
                    'FieldType' => 'bool',
                    'Hint' => $this->i18N('HINT_ITS_BUSINESS_TENANT_HTML'),
                    'EnableOnCreate' => true,
                    'EnableOnEdit' => false
                ];
            }
            if ($oAuthenticatedUser->Role === UserRole::SuperAdmin || $oAuthenticatedUser->Role === UserRole::TenantAdmin) {
                $this->aAdditionalEntityFieldsToEdit[] = [
                    'DisplayName' => $this->i18N('LABEL_ENABLE_GROUPWARE'),
                    'Entity' => 'Tenant',
                    'FieldName' => self::GetName() . '::EnableGroupware',
                    'FieldType' => 'bool',
                    'Hint' => '',
                    'EnableOnCreate' => $oAuthenticatedUser->Role === UserRole::SuperAdmin,
                    'EnableOnEdit' => $oAuthenticatedUser->Role === UserRole::SuperAdmin
                ];
            }
        }
    }

    /**
     * @return Module
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     * @return Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    private function checkIfEmailReserved($sEmail)
    {
        $sAccountName = \MailSo\Base\Utils::GetAccountNameFromEmail($sEmail);
        $sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
        $oMailDomains = \Aurora\System\Api::GetModuleDecorator('MailDomains');
        $aDomains = [];
        if ($oMailDomains) {
            $aDomainObjects = \Aurora\System\Api::GetModuleDecorator('MailDomains')->getDomainsManager()->getFullDomainsList();
            $aDomains = $aDomainObjects->map(function ($oDomain) {
                return $oDomain->Name;
            })->toArray();
        }

        $aReservedAccountNames = $this->oModuleSettings->ReservedList;
        if (
            is_array($aDomains)
            && is_array($aReservedAccountNames)
            && in_array($sAccountName, $aReservedAccountNames)
            && in_array($sDomain, $aDomains)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check is tenant is a business tenant
     * 
     * @param int $TenantId
     * @return bool
     */
    public function GetGroupwareState($TenantId)
    {
        $bState = false;
        $oAuthenticatedUser = \Aurora\Api::getAuthenticatedUser();
        if ($oAuthenticatedUser instanceof User && ($oAuthenticatedUser->Role === UserRole::SuperAdmin || ($oAuthenticatedUser->Role === UserRole::TenantAdmin && $oAuthenticatedUser->IdTenant === $TenantId))) {
            $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($TenantId);
            if ($oTenant instanceof Tenant) {
                $aDisabledModules = $oTenant->getDisabledModules();
                if (count($aDisabledModules) === 0) {
                    $bState = true;
                }
            }
        }

        return $bState;
    }

    /**
     * Update groupware state for tenant
     * 
     * @param int $TenantId
     * @param bool $EnableGroupware
     * @return bool
     */
    public function UpdateGroupwareState($TenantId, $EnableGroupware = false)
    {
        $bResult = false;
        $oAuthenticatedUser = \Aurora\Api::getAuthenticatedUser();
        if ($oAuthenticatedUser->Role === UserRole::SuperAdmin || ($oAuthenticatedUser->Role === UserRole::TenantAdmin && $oAuthenticatedUser->IdTenant === $TenantId)) {
            $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($TenantId);
            if ($oTenant instanceof Tenant) {
                if ($EnableGroupware) {
                    $oTenant->clearDisabledModules();
                } else {
                    $aGroupwareModules = $this->oModuleSettings->GroupwareModules;
                    if (is_array($aGroupwareModules) && count($aGroupwareModules) > 0) {
                        $oTenant->disableModules($aGroupwareModules);
                        $bResult = true;
                    }
                }
            }
        }

        return $bResult;
    }

    protected function getBusinessTenantLimits($oTenant, $sSettingName)
    {
        if ($oTenant && $oTenant->{self::GetName() . '::IsBusiness'}) {
            return $oTenant->{self::GetName() . '::' . $sSettingName};
        }
        return $this->getBusinessTenantLimitsFromConfig($sSettingName);
    }

    protected function getBusinessTenantLimitsFromConfig($sSettingName)
    {
        $aBusinessTenantLimitsConfig = $this->oModuleSettings->BusinessTenantLimits;
        $aBusinessTenantLimits = is_array($aBusinessTenantLimitsConfig) && count($aBusinessTenantLimitsConfig) > 0 ? $aBusinessTenantLimitsConfig[0] : [];
        return is_array($aBusinessTenantLimitsConfig) && isset($aBusinessTenantLimits[$sSettingName]) ? $aBusinessTenantLimits[$sSettingName] : null;
    }

    public function onBeforeCreateUser($aArgs, &$mResult)
    {
        $iTenantId = $aArgs['TenantId'];
        $oTenant = \Aurora\Modules\Core\Module::Decorator()->getTenantsManager()->getTenantById($iTenantId);
        if ($oTenant && $oTenant->{self::GetName() . '::IsBusiness'}) {
            $iEmailAccountsLimit = $this->getBusinessTenantLimits($oTenant, 'EmailAccountsCount');
            if (is_int($iEmailAccountsLimit) && $iEmailAccountsLimit > 0) {
                $iUserCount = User::where('IdTenant', $iTenantId)->count();
                if ($iUserCount >= $iEmailAccountsLimit) {
                    throw new \Exception($this->i18N('ERROR_BUSINESS_TENANT_EMAIL_ACCOUNTS_LIMIT_PLURAL', ['COUNT' => $iEmailAccountsLimit], $iEmailAccountsLimit));
                }
            }
        }

        //@ TODO: check if Forced argument is used
        if (isset($aArgs['PublicId']) && $this->checkIfEmailReserved($aArgs['PublicId'])) {
            $oUser = \Aurora\System\Api::getAuthenticatedUser();
            if ($oUser instanceof User && ($oUser->Role === UserRole::SuperAdmin || $oUser->Role === UserRole::TenantAdmin)
                // && isset($aArgs['Forced']) && $aArgs['Forced'] === true
            ) {
                //Only SuperAdmin or TenantAdmin can creaete User if it was reserved
            } else {
                throw new \Exception($this->i18N('ERROR_EMAIL_IS_RESERVED'));
            }
        }
    }

    public function onBeforeCreateAccount($aArgs, &$mResult)
    {
        if (isset($aArgs['Email']) && $this->checkIfEmailReserved($aArgs['Email'])) {
            $oUser = \Aurora\System\Api::getAuthenticatedUser();
            if ($oUser instanceof User && ($oUser->Role === UserRole::SuperAdmin || $oUser->Role === UserRole::TenantAdmin)) {
                //Only SuperAdmin or TenantAdmin can create Account if it was reserved
            } else {
                throw new \Exception($this->i18N('ERROR_EMAIL_IS_RESERVED'));
            }
        }
    }

    /**
     * TODO: check user groups case
     * 
     * @param array $aArgs
     * @param mixed $mResult
     */
    public function onAfterCreateUser($aArgs, &$mResult)
    {
        if ($mResult) {
            $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($mResult);
            if ($oUser instanceof User) {
                $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($oUser->IdTenant);
                if ($oTenant instanceof Tenant) {
                    if ($oTenant->{self::GetName() . '::IsBusiness'}) {
                        $UserSpaceLimitMb = $oTenant->{'Files::UserSpaceLimitMb'};

                        \Aurora\Modules\Files\Module::Decorator()->CheckAllocatedSpaceLimitForUsersInTenant($oTenant, $UserSpaceLimitMb);

                        $oUser->setExtendedProp('Files::UserSpaceLimitMb', $UserSpaceLimitMb);
                        $oUser->save();
                    // } else {
                    //     $oFilesDecorator = \Aurora\Modules\Files\Module::Decorator();
                    //     $iFilesQuotaMb = $this->getGroupSetting($oUser->Id, 'FilesQuotaMb');
                    //     if ($oFilesDecorator && is_int($iFilesQuotaMb)) {
                    //         $oFilesDecorator->UpdateUserSpaceLimit($oUser->Id, $iFilesQuotaMb);
                    //     }
                    }
                }
            }
        }
    }

    /**
     * TODO: check user groups case
     * 
     * @param array $aArgs
     * @param mixed $mResult
     */
    public function onAfterCreateAccount($aArgs, &$mResult)
    {
        if ($mResult instanceof \Aurora\Modules\Mail\Models\MailAccount) {
            $oAccount = $mResult;
            $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($oAccount->IdUser);
            if ($oUser instanceof User) {
                $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($oUser->IdTenant);
                if ($oTenant instanceof Tenant && $oUser->PublicId === $oAccount->Email) {
                    // $iMailQuotaMb = $oTenant->{self::GetName() . '::IsBusiness'}
                    //     ? $oTenant->{'Mail::UserSpaceLimitMb'}
                    //     : $this->getGroupSetting($oUser->Id, 'MailQuotaMb');
                    // \Aurora\Modules\Mail\Module::Decorator()->UpdateEntitySpaceLimits('User', $oUser->Id, $oUser->IdTenant, null, $iMailQuotaMb);
                    if ($oTenant->{self::GetName() . '::IsBusiness'}) {
                        $iMailQuotaMb = $oTenant->{'Mail::UserSpaceLimitMb'};
                        \Aurora\Modules\Mail\Module::Decorator()->UpdateEntitySpaceLimits('User', $oUser->Id, $oUser->IdTenant, null, $iMailQuotaMb);
                    }
                }
            }
        }
    }

    public function onAfterCreateTenant($aArgs, &$mResult)
    {
        $iTenantId = $mResult;
        if (!empty($iTenantId)) {
            $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($iTenantId);
            if ($oTenant && isset($aArgs[self::GetName() . '::IsBusiness']) && is_bool($aArgs[self::GetName() . '::IsBusiness'])) {
                if (isset($aArgs[self::GetName() . '::IsBusiness']) && is_bool($aArgs[self::GetName() . '::IsBusiness'])) {
                    $aAttributesToSave = [];

                    $oTenant->setExtendedProp(self::GetName() . '::IsBusiness', $aArgs[self::GetName() . '::IsBusiness']);
                    $aAttributesToSave[] = self::GetName() . '::IsBusiness';

                    if ($oTenant->{self::GetName() . '::IsBusiness'}) {
                        $oFilesModule = \Aurora\Api::GetModule('Files');
                        $iFilesStorageQuotaMb = $this->getBusinessTenantLimitsFromConfig('FilesStorageQuotaMb');
                        if ($oFilesModule) {
                            $oTenant->setExtendedProp('Files::UserSpaceLimitMb', $oFilesModule->oModuleSettings->UserSpaceLimitMb);
                            $oTenant->setExtendedProp('Files::TenantSpaceLimitMb', $iFilesStorageQuotaMb);

                            $aAttributesToSave[] = 'Files::UserSpaceLimitMb';
                            $aAttributesToSave[] = 'Files::TenantSpaceLimitMb';
                        }

                        $iMailStorageQuotaMb = $this->getBusinessTenantLimitsFromConfig('MailStorageQuotaMb');
                        if (is_int($iMailStorageQuotaMb)) {
                            $oTenant->setExtendedProp('Mail::TenantSpaceLimitMb', $iMailStorageQuotaMb);
                            $aAttributesToSave[] = 'Mail::TenantSpaceLimitMb';
                        }
                    } else {
                        $oTenant->setExtendedProp('Mail::AllowChangeUserSpaceLimit', false);
                        $aAttributesToSave[] = 'Mail::AllowChangeUserSpaceLimit';
                    }

                    $oTenant->save();
                }

                if (isset($aArgs[self::GetName() . '::EnableGroupware']) && is_bool($aArgs[self::GetName() . '::EnableGroupware'])) {
                    $this->UpdateGroupwareState($iTenantId, $aArgs[self::GetName() . '::EnableGroupware']);
                }
            }
        }
    }

    public function onAfterUpdateTenant($aArgs, &$mResult)
    {
        $oUser = \Aurora\Api::getAuthenticatedUser();
        if ($oUser instanceof  User && $oUser->Role === UserRole::SuperAdmin) {
            $iTenantId = (int) $aArgs['TenantId'];
            if (!empty($iTenantId)) {
                $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($iTenantId);
                if ($oTenant) {
                    if (isset($aArgs[self::GetName() . '::EnableGroupware']) && is_bool($aArgs[self::GetName() . '::EnableGroupware'])) {
                        $this->UpdateGroupwareState($iTenantId, $aArgs[self::GetName() . '::EnableGroupware']);
                    }
                }
            }
        }
    }

    public function onAfterGetSettingsForEntity($aArgs, &$mResult)
    {
        if (isset($aArgs['EntityType'], $aArgs['EntityId']) && 	$aArgs['EntityType'] === 'Tenant') {
            $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($aArgs['EntityId']);
            if ($oTenant instanceof Tenant) {
                $mResult['AllowEditUserSpaceLimitMb'] = $oTenant->{self::GetName() . '::IsBusiness'};
            }
        }
        if (isset($aArgs['EntityType'], $aArgs['EntityId']) && 	$aArgs['EntityType'] === 'User') {
            $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($aArgs['EntityId']);
            if ($oUser instanceof User) {
                $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($oUser->IdTenant);
                if ($oTenant instanceof Tenant) {
                    $mResult['AllowEditUserSpaceLimitMb'] = $oTenant->{self::GetName() . '::IsBusiness'};
                }
            }
        }
    }

    public function onAfterIsEmailAllowedForCreation($aArgs, &$mResult)
    {
        if ($mResult && isset($aArgs['Email'])) {
            $mResult = !$this->checkIfEmailReserved($aArgs['Email']);
        }
    }

    /**
     * Sets limits for business tenant
     * 
     * @param int $TenantId
     * @param int $AliasesCount
     * @param int $EmailAccountsCount
     * @param int $MailStorageQuotaMb
     * @param int $FilesStorageQuotaMb
     * 
     * @return boolean
     */
    public function UpdateBusinessTenantLimits($TenantId, $AliasesCount, $EmailAccountsCount, $MailStorageQuotaMb, $FilesStorageQuotaMb)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::SuperAdmin);

        $oTenant = \Aurora\Modules\Core\Module::Decorator()->GetTenantWithoutRoleCheck($TenantId);
        if ($oTenant instanceof Tenant && $oTenant->{self::GetName() . '::IsBusiness'}) {
            $aAttributesToSave = [];
            if (is_int($AliasesCount)) {
                $oTenant->setExtendedProp(self::GetName() . '::AliasesCount', $AliasesCount);
                $aAttributesToSave[] = self::GetName() . '::AliasesCount';
            }
            if (is_int($EmailAccountsCount)) {
                $oTenant->setExtendedProp(self::GetName() . '::EmailAccountsCount', $EmailAccountsCount);
                $aAttributesToSave[] = self::GetName() . '::EmailAccountsCount';
            }
            if (!empty($aAttributesToSave)) {
                $oTenant->save();
            }
            if (is_int($MailStorageQuotaMb)) {
                \Aurora\Modules\Mail\Module::Decorator()->UpdateEntitySpaceLimits('Tenant', 0, $TenantId, $MailStorageQuotaMb);
            }
            if (is_int($FilesStorageQuotaMb)) {
                \Aurora\Modules\Files\Module::Decorator()->UpdateSettingsForEntity('Tenant', $TenantId, null, $FilesStorageQuotaMb);
            }
            return true;
        }

        return false;
    }

    /**
     * Obtains list of module settings for authenticated user.
     *
     * @param int|null $TenantId Tenant ID
     * @return array
     */
    public function GetSettings($TenantId = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::NormalUser);

        $bIsBusiness = false;
        $sIsGroupwareEnabled = false;

        $oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
        $TenantId = $TenantId ?? $oAuthenticatedUser->IdTenant;

        $oTenant = \Aurora\System\Api::getTenantById($TenantId);
        if ($oTenant && ($oAuthenticatedUser->isAdmin() || $oAuthenticatedUser->IdTenant === $oTenant->Id)) {
            $bIsBusiness = (bool) $oTenant->{self::GetName() . '::IsBusiness'};
            $sIsGroupwareEnabled = (bool) $oTenant->{self::GetName() . '::IsGroupwareEnabled'};
        }

        return array(
            'IsBusiness' => $bIsBusiness,
            'IsGroupwareEnabled' => $sIsGroupwareEnabled,
        );
    }

    /**
     * Updates module's settings - saves them to config.json file or to user settings in db.
     *
     * @param int $TenantId
     * @param string $IsBusiness
     * @param string $IsGroupwareEnabled
     * @return boolean
     */
    public function UpdateSettings($TenantId, $IsBusiness, $IsGroupwareEnabled)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::SuperAdmin);

        $bResult = false;

        $oTenant = \Aurora\System\Api::getTenantById($TenantId);

        if ($oTenant) {
            $oTenant->{self::GetName() . '::IsBusiness'} = (bool) $IsBusiness;
            $oTenant->{self::GetName() . '::IsGroupwareEnabled'} = (bool) $IsGroupwareEnabled;
            $bResult = $oTenant->save();
        }

        return $bResult;
    }

    /**
     * Reterns a list of reserved names
     * @return array
     */
    public function GetReservedNames()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::SuperAdmin);

        return $this->oModuleSettings->ReservedList;
    }

    /**
     * Adds a new item to the reserved list
     *
     * @param string AccountName
     * @return boolean
     */
    public function AddNewReservedName($AccountName)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::SuperAdmin);

        $bResult = false;
        $sAccountName = strtolower($AccountName);
        $aCurrentReservedList = $this->oModuleSettings->ReservedList;
        if (in_array($sAccountName, $aCurrentReservedList)) {
            throw new \Exception($this->i18N('ERROR_NAME_ALREADY_IN_RESERVED_LIST'));
        } else {
            try {
                $aCurrentReservedList[] = $sAccountName;
                $this->setConfig('ReservedList', $aCurrentReservedList);
                $this->saveModuleConfig();
                $bResult = true;
            } catch (\Exception $ex) {
                throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::CanNotSaveSettings);
            }
        }

        return $bResult;
    }

    /**
     * Removes the specified names from the list
     *
     * @param array ReservedNames
     * @return boolean
     */
    public function DeleteReservedNames($ReservedNames)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(UserRole::SuperAdmin);

        $bResult = false;

        if (!is_array($ReservedNames) || empty($ReservedNames)) {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        } else {
            $aCurrentReservedList = $this->oModuleSettings->ReservedList;
            $newReservedList = array_diff($aCurrentReservedList, $ReservedNames);
            //"array_values" needed to reset array keys after deletion
            $this->setConfig('ReservedList', array_values($newReservedList));
            $this->saveModuleConfig();
            $bResult = true;
        }

        return $bResult;
    }
}
