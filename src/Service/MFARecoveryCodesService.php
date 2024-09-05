<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use Combodo\iTop\Application\Helper\Session;
use Combodo\iTop\MFABase\Helper\MFABaseLog;
use Combodo\iTop\MFARecoveryCode\Helper\MFARecoveryCodeHelper;
use Combodo\iTop\MFATotp\Helper\MFATOTPHelper;
use Dict;
use LoginTwigContext;
use MFAUserSettingsRecoveryCode;
use utils;

class MFARecoveryCodesService
{
	private static MFARecoveryCodesService $oInstance;

	protected function __construct()
	{
		MFABaseLog::Enable();
	}

	final public static function GetInstance(): MFARecoveryCodesService
	{
		if (!isset(static::$oInstance)) {
			static::$oInstance = new static();
		}

		return static::$oInstance;
	}

	public function GetConfigurationURLForMyAccountRedirection(MFAUserSettingsRecoveryCodes $oMFAUserSettings): string
	{
		return utils::GetAbsoluteUrlModulePage(MFATOTPHelper::MODULE_NAME, 'index.php', ['operation' => 'MFARecoveryCodesConfig']);
	}


	public function GetTwigContextForLoginValidation(MFAUserSettingsRecoveryCodes $oMFAUserSettings): LoginTwigContext
	{
		return new LoginTwigContext();
	}

	private function ValidateCode(MFAUserSettingsRecoveryCodes $oMFAUserSettings, array &$aData): ?LoginTwigContext
	{
		Session::Set('mfa-configuration-validated', 'true');
		$aData['sTitle'] = Dict::S('UI:MFA:Redirection:Title');
		$oLoginContext = new LoginTwigContext();
		$oLoginContext->SetLoaderPath(MODULESROOT.MFARecoveryCodesHelper::MODULE_NAME.'/templates/login');
		$oLoginContext->AddBlockExtension('mfa_title', new \LoginBlockExtension('MFALoginTitle.html.twig', $aData));
		$oLoginContext->AddBlockExtension('script', new \LoginBlockExtension('MFALoginRedirect.ready.js.twig', $aData));

		return $oLoginContext;
	}


	public function HasToDisplayValidation(MFAUserSettingsRecoveryCodes $oMFAUserSettings): bool
	{
		return true;
	}

	public function ValidateLogin(MFAUserSettingsRecoveryCodes $oMFAUserSettings): bool
	{
		return true;
	}




}