<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use Combodo\iTop\MFABase\Helper\MFABaseException;
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

	public function __construct()
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

	public function GetCodesById(MFAUserSettingsRecoveryCodes $oMFAUserSettings): array
	{
		$oCodesLinkSet = $oMFAUserSettings->Get('mfarecoverycodes_list');

		return $oCodesLinkSet->GetColumnAsArray('code');
	}


	public function GetCodesAndStatus(MFAUserSettingsRecoveryCodes $oMFAUserSettings): array
	{
		$sId = $oMFAUserSettings->GetKey();
		$oSearch = DBSearch::FromOQL("SELECT MFARecoveryCode WHERE mfausersettingsrecoverycodes_id=:id");
		$oSearch->AllowAllData();
		$oSet = new DBObjectSet($oSearch, [], ['id' => $sId]);
		$aCodes = [];
		while ($oCode = $oSet->Fetch()) {
			$aCodes[$oCode->Get('code')] = $oCode->Get('status');
		}

		return $aCodes;
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


	/**
	 * @param MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 * @param string $sCode
	 *
	 * @return void
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	public function InvalidateCode(MFAUserSettingsRecoveryCodes $oMFAUserSettings, string $sCode): void
	{
		try {
			$aCodes = array_flip($this->GetCodesById($oMFAUserSettings));
			if (!array_key_exists($sCode, $aCodes)) {
				throw new MFABaseException(__METHOD__.': Invalid recovery code');
			}
			/** @var \DBObject $oCode */
			$oCode = MetaModel::GetObject(MFARecoveryCode::class, $aCodes[$sCode], false, true);
			if (is_null($oCode)) {
				throw new MFABaseException(__METHOD__.': Invalid recovery code');
			}
			$oCode->Set('status', 'inactive');
			$oCode->AllowWrite();
			$oCode->DBUpdate();
		} catch (MFABaseException $e) {
			throw $e;
		}
		catch (\Exception $e) {
			throw new MFABaseException(__METHOD__.': Error', 0, $e);
		}
	}
}
