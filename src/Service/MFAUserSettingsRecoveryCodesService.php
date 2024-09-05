<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use MetaModel;
use MFARecoveryCode;
use MFAUserSettingsRecoveryCodes;
use ParagonIE\ConstantTime\Base32;

class MFAUserSettingsRecoveryCodesService
{
	public const RECOVERY_CODES_COUNT = 10;

	private static MFAUserSettingsRecoveryCodesService $oInstance;

	protected function __construct()
	{
	}

	final public static function GetInstance(): MFAUserSettingsRecoveryCodesService
	{
		if (!isset(static::$oInstance)) {
			static::$oInstance = new static();
		}

		return static::$oInstance;
	}

	public function CreateCodes(MFAUserSettingsRecoveryCodes $oMFAUserSettings): void
	{
		$sId = $oMFAUserSettings->GetKey();

		for ($i = 0; $i < MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT; $i++) {
			$oCode = MetaModel::NewObject(MFARecoveryCode::class, [
				'code' => Base32::encodeUpper(random_bytes(10)),
				'mfausersettingsrecoverycodes_id' => $sId,
			]);
			$oCode->AllowWrite();
			$oCode->DBInsert();
		}
		$oMFAUserSettings->Reload();
	}

	public function DeleteCodes(MFAUserSettingsRecoveryCodes $oMFAUserSettings): void
	{

	}

	public function GetCodesAsArray(MFAUserSettingsRecoveryCodes $oMFAUserSettings): array
	{
		$oCodesLinkSet = $oMFAUserSettings->Get('mfarecoverycodes_list');

		return array_values($oCodesLinkSet->GetColumnAsArray('code'));
	}


}