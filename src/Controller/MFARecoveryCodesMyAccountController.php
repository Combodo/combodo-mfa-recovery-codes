<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Controller;

use Combodo\iTop\Application\TwigBase\Controller\Controller;
use Combodo\iTop\MFABase\Helper\MFABaseHelper;
use Combodo\iTop\MFABase\Service\MFAUserSettingsService;
use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use MFAUserSettingsRecoveryCodes;
use UserRights;
use utils;

class MFARecoveryCodesMyAccountController extends Controller
{
	public function OperationMFARecoveryCodesView()
	{
		$aParams = [];

		MFABaseHelper::GetInstance()->ValidateTransactionId();

		$sUserId = UserRights::GetUserId();
		$oUserSettings = MFAUserSettingsService::GetInstance()->GetMFAUserSettings($sUserId, MFAUserSettingsRecoveryCodes::Class);
		$oUserSettingsRecoveryCodesService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$aCodes = $oUserSettingsRecoveryCodesService->GetCodesById($oUserSettings);

		if (count($aCodes) < MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT) {
			$oUserSettingsRecoveryCodesService->RebuildCodes($oUserSettings);
			$aCodes = $oUserSettingsRecoveryCodesService->GetCodesById($oUserSettings);
		}

		$aParams['aCodes'] = $aCodes;
		$aParams['sTransactionId'] = utils::GetNewTransactionId();
		$aParams['sCodes'] = implode("\n", $aCodes);
		$aParams['sCodesAsLine'] = implode("\\n", $aCodes);

		MFAUserSettingsService::GetInstance()->SetIsValid($oUserSettings);
		$this->AddLinkedScript(utils::GetAbsoluteUrlModulesRoot().MFABaseHelper::MODULE_NAME.'/assets/js/MFABase.js');

		$this->DisplayPage($aParams);
	}

	public function OperationRebuildCodes()
	{
		MFABaseHelper::GetInstance()->ValidateTransactionId();
		$sUserId = UserRights::GetUserId();
		$oUserSettings = MFAUserSettingsService::GetInstance()->GetMFAUserSettings($sUserId, MFAUserSettingsRecoveryCodes::Class);
		$oUserSettingsRecoveryCodesService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$oUserSettingsRecoveryCodesService->RebuildCodes($oUserSettings);

		$this->m_sOperation = 'MFARecoveryCodesView';
		$this->OperationMFARecoveryCodesView();
	}
}
