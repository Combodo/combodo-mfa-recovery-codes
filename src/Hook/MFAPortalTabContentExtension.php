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
use Combodo\iTop\Portal\Hook\iPortalTabContentExtension;
use Combodo\iTop\Portal\Twig\PortalBlockExtension;
use Combodo\iTop\Portal\Twig\PortalTwigContext;
use MFAUserSettingsRecoveryCodes;
use UserRights;
use utils;

if (interface_exists('Combodo\iTop\Portal\Hook\iPortalTabContentExtension')) {

	class MFAPortalTabContentExtension implements iPortalTabContentExtension
	{
		/** @var array Current recovery codes */
		private array $aCodes;

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

		/**
		 * Handle actions based on posted vars
		 */
		public function HandlePortalForm(array &$aData): void
		{
			$sUserId = UserRights::GetUserId();
			$oUserSettings = MFAUserSettingsService::GetInstance()->GetMFAUserSettings($sUserId, MFAUserSettingsRecoveryCodes::Class);

			if (utils::ReadPostedParam('operation') === 'rebuild_code') {
				MFAUserSettingsRecoveryCodesService::GetInstance()->RebuildCodes($oUserSettings);
			} else {
				$this->aCodes = MFAUserSettingsRecoveryCodesService::GetInstance()->GetCodesById($oUserSettings);

				if (count($this->aCodes) < MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT) {
					MFAUserSettingsRecoveryCodesService::GetInstance()->RebuildCodes($oUserSettings);
				}
			}

			$this->aCodes = MFAUserSettingsRecoveryCodesService::GetInstance()->GetCodesById($oUserSettings);
			MFAUserSettingsService::GetInstance()->SetIsValid($oUserSettings);
		}

		/**
		 * List twigs and variables for the tab content per block
		 *
		 * @return PortalTwigContext
		 */
		public function GetPortalTabContentTwigs(): PortalTwigContext
		{
			$oPortalTwigContext = new PortalTwigContext();
			$sPath = MFARecoveryCodesHelper::MODULE_NAME.'/templates/portal/MFARecoveryCodesView.html.twig';

			$aData['sAction'] = MFAPortalService::GetInstance()->GetSelectedAction();
			$aData['sClass'] = MFAUserSettingsRecoveryCodes::class;
			$aData['aCodes'] = $this->aCodes;
			$aData['sCodes'] = implode("\n", $this->aCodes);
			$aData['sCodesAsLine'] = implode("\\n", $this->aCodes);

			$oPortalTwigContext->AddBlockExtension('html', new PortalBlockExtension($sPath, $aData));

			return $oPortalTwigContext;
		}
	}

}
