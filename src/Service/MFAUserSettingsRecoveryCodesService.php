<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use Combodo\iTop\MFABase\Service\MFAUserSettingsService;
use DBObjectSet;
use DBSearch;
use MetaModel;
use MFARecoveryCode;
use MFAUserSettingsRecoveryCodes;

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
				'code' => strtoupper(bin2hex(random_bytes(8))),
				'mfausersettingsrecoverycodes_id' => $sId,
			]);
			$oCode->AllowWrite();
			$oCode->DBInsert();
		}
		$oMFAUserSettings->Reload();
	}

	public function DeleteCodes(MFAUserSettingsRecoveryCodes $oMFAUserSettings): void
	{
		$sId = $oMFAUserSettings->GetKey();
		$oSet = new DBObjectSet(DBSearch::FromOQL("SELECT MFARecoveryCode WHERE mfausersettingsrecoverycodes_id=:id"), [], ['id' => $sId]);
		while ($oCode = $oSet->Fetch()) {
			$oCode->AllowDelete();
			$oCode->DBDelete();
		}
	}

	public function GetCodesAsArray(MFAUserSettingsRecoveryCodes $oMFAUserSettings): array
	{
		$oCodesLinkSet = $oMFAUserSettings->Get('mfarecoverycodes_list');

		return array_values($oCodesLinkSet->GetColumnAsArray('code'));
	}

	public function RebuildCodes(MFAUserSettingsRecoveryCodes $oMFAUserSettings)
	{
		$oMFAUserSettingsService = MFAUserSettingsService::GetInstance();

		$bIsValid = $oMFAUserSettingsService->IsValid($oMFAUserSettings);
		$oMFAUserSettingsService->SetIsValid($oMFAUserSettings, false);

		$this->DeleteCodes($oMFAUserSettings);
		$this->CreateCodes($oMFAUserSettings);

		$oMFAUserSettingsService->SetIsValid($oMFAUserSettings, $bIsValid);
	}
}