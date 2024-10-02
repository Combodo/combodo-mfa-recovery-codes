<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Hook;

use Combodo\iTop\MFABase\Service\MFAPortalService;
use Combodo\iTop\MFABase\Service\MFAUserSettingsService;
use Combodo\iTop\MFARecoveryCodes\Helper\MFARecoveryCodesHelper;
use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use Combodo\iTop\Portal\Hook\iPortalTabSectionExtension;
use Combodo\iTop\Portal\Twig\PortalBlockExtension;
use Combodo\iTop\Portal\Twig\PortalTwigContext;
use MFAUserSettingsRecoveryCodes;
use UserRights;

class MFAPortalTabSectionExtension implements iPortalTabSectionExtension
{

	/**
	 * @inheritDoc
	 */
	public function IsActive(): bool
	{
		return MFAPortalService::GetInstance()->IsUserSettingsConfigurationRequired(MFAUserSettingsRecoveryCodes::class);
	}

	/**
	 * @inheritDoc
	 */
	public function GetTabCode(): string
	{
		return 'MyAccount-Tab-MFA';
	}

	/**
	 * @inheritDoc
	 */
	public function GetSectionRank(): float
	{
		return 0;
	}

	public function GetTarget(): string
	{
		return 'p_user_profile_brick';
	}

	public function GetPortalTwigContext(): PortalTwigContext
	{
		$oPortalTwigContext = new PortalTwigContext();
		$sPath = MFARecoveryCodesHelper::MODULE_NAME.'/templates/portal/MFARecoveryCodesView.html.twig';

		$sUserId = UserRights::GetUserId();
		$oUserSettings = MFAUserSettingsService::GetInstance()->GetMFAUserSettings($sUserId, MFAUserSettingsRecoveryCodes::Class);
		$oUserSettingsRecoveryCodesService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$aCodes = $oUserSettingsRecoveryCodesService->GetCodesById($oUserSettings);

		if (count($aCodes) < MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT) {
			$oUserSettingsRecoveryCodesService->RebuildCodes($oUserSettings);
			$aCodes = $oUserSettingsRecoveryCodesService->GetCodesById($oUserSettings);
		}

		$aData['aCodes'] = $aCodes;
		$aData['sCodes'] = implode("\n", $aCodes);
		$aData['sCodesAsLine'] = implode("\\n", $aCodes);

		MFAUserSettingsService::GetInstance()->SetIsValid($oUserSettings);


		$oPortalTwigContext->AddBlockExtension('html', new PortalBlockExtension($sPath, $aData));

		return $oPortalTwigContext;
	}
}
