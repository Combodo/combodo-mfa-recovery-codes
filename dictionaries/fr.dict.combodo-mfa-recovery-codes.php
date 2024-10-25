<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('FR FR', 'French', 'Français', array(
	'combodo-mfa-recovery-codes/Operation:MFARecoveryCodesView/Title' => 'View Recovery codes',
	'MFA:MFAUserSettingsRecoveryCodes:Description' => 'Provides a list of 10 single use recovery codes to access your account if you lose access to your device',

	'MFA:login:switch:label:MFAUserSettingsRecoveryCodes' => 'Use recovery code',

	'MFA:RC:CodeValidation:Title' => 'MFA Recovery codes authentication',
	'MFA:RC:EnterCode' => 'Recovery code',

	'MFA:RC:Config:Title' => 'MFA Recovery codes',
	'MFA:RC:Config:Warning' => 'Keep your recovery codes as safe as your password. We recommend saving them with a password manager',
	'MFA:RC:Settings:Title' => 'Settings',
	'MFA:RC:Settings:Code:label' => 'List of recovery codes',
	'MFA:RC:Copy' => 'Copy recovery codes to clipboard',
	'MFA:RC:Copy:Done' => 'Recovery codes copied to clipboard',
	'MFA:RC:RebuildCodes' => 'Rebuild recovery codes',
	'MFA:RC:RebuildCodes+' => 'This operation will invalidate the previous recovery codes',

	'Class:MFAUserSettingsRecoveryCodes' => 'Recovery codes',
	'Class:MFAUserSettingsRecoveryCodes/Attribute:mfarecoverycodes_list' => 'Recovery codes list',

	'Class:MFARecoveryCode' => 'Recovery code',
	'Class:MFARecoveryCode/Attribute:code' => 'Code',
	'Class:MFARecoveryCode/Attribute:status' => 'Status',
	'Class:MFARecoveryCode/Attribute:mfausersettingsrecoverycodes_id' => 'User settings',

));
