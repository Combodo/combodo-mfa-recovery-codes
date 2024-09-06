<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Controller;

use Combodo\iTop\Application\TwigBase\Controller\Controller;
use Combodo\iTop\MFABase\Service\MFAUserSettingsService;
use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use MFAUserSettingsRecoveryCodes;
use UserRights;

class MFARecoveryCodesMyAccountController extends Controller
{
	public function OperationMFARecoveryCodesView()
	{
		$aParams = [];

		$sUserId = UserRights::GetUserId();
		$oUserSettings = MFAUserSettingsService::GetInstance()->GetMFAUserSettings($sUserId, MFAUserSettingsRecoveryCodes::Class);
		$oUserSettingsRecoveryCodesService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$aCodes = $oUserSettingsRecoveryCodesService->GetCodesAsArray($oUserSettings);

		if (count($aCodes) < MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT) {
			$oUserSettingsRecoveryCodesService->RebuildCodes($oUserSettings);
			$aCodes = $oUserSettingsRecoveryCodesService->GetCodesAsArray($oUserSettings);
		}

		$aParams['aCodes'] = $aCodes;
		$aParams['sCodes'] = implode("\n", $aCodes);

		MFAUserSettingsService::GetInstance()->SetIsValid($oUserSettings);

		$this->DisplayPage($aParams);
	}
}