<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Hook;

use Combodo\iTop\MFABase\Helper\MFABaseConfig;
use Combodo\iTop\MFABase\Helper\MFABaseHelper;
use Combodo\iTop\MFABase\Service\MFABaseService;
use Combodo\iTop\MFARecoveryCodes\Helper\MFARecoveryCodesHelper;
use Combodo\iTop\Portal\Hook\iPortalTabSectionExtension;
use Combodo\iTop\Portal\Twig\PortalBlockExtension;
use Combodo\iTop\Portal\Twig\PortalTwigContext;

class PortalTabSectionExtension implements iPortalTabSectionExtension
{

	/**
	 * @inheritDoc
	 */
	public function IsActive(): bool
	{
		return MFABaseConfig::GetInstance()->IsEnabled();
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

		$aData = ['aUserSettings' => MFABaseService::GetInstance()->GetMFAUserSettingsDataTable()];

		$oPortalTwigContext->AddBlockExtension('html', new PortalBlockExtension($sPath, $aData));

		return $oPortalTwigContext;
	}
}
