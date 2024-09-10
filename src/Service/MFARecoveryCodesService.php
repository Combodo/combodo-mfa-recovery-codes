<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use Combodo\iTop\Application\Helper\Session;
use Combodo\iTop\MFABase\Helper\MFABaseLog;
use Combodo\iTop\MFARecoveryCodes\Helper\MFARecoveryCodesHelper;
use Dict;
use LoginTwigContext;
use MFAUserSettingsRecoveryCodes;
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
		return utils::GetAbsoluteUrlModulePage(MFARecoveryCodesHelper::MODULE_NAME, 'index.php', ['operation' => 'MFARecoveryCodesView']);
	}

	public function GetTwigContextForLoginValidation(MFAUserSettingsRecoveryCodes $oMFAUserSettings): LoginTwigContext
	{
		$oLoginContext = new LoginTwigContext();
		/** @var \MFAUserSettingsTOTP $oMFAUserSettings */
		$oTOTPService = new OTPService($oMFAUserSettings);

		$aData = [];
		$aData['sTitle'] = Dict::S('MFATOTP:App:Validation:Title');
		$aData['sLabel'] = $oTOTPService->sLabel;
		$aData['sIssuer'] = $oTOTPService->sIssuer;

		$oLoginContext->SetLoaderPath(MODULESROOT.MFATOTPHelper::MODULE_NAME.'/templates/login');
		$oLoginContext->AddBlockExtension('mfa_validation', new \LoginBlockExtension('MFARecoveryCodesValidate.html.twig', $aData));
		$oLoginContext->AddBlockExtension('mfa_title', new \LoginBlockExtension('MFARecoveryCodesTitle.html.twig', $aData));
		$oLoginContext->AddJsFile(MFARecoveryCodesHelper::GetJSFile());

		return $oLoginContext;
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
		$sCode = utils::ReadPostedParam('recovery_code', 0, utils::ENUM_SANITIZATION_FILTER_STRING);
		if ($sCode === 0) {
			MFABaseLog::Debug("Recovery code validation : no 'recovery_code' received", null, [ 'user_id' => $oMFAUserSettings->Get('user_id')]);
			return false;
		}

		if ($sCode === false) {
			MFABaseLog::Debug("Recovery code validation : invalid 'recovery_code' received (sanitization)", null, [ 'user_id' => $oMFAUserSettings->Get('user_id') ]);

			unset($_POST['recovery_code']);
			return false;
		}

		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();

		try{
			$oMFAUserSettingsRecoveryCodesService->InvalidateCode($oMFAUserSettings, $sCode);
			MFABaseLog::Debug("Recovery code validation : correct 'recovery_code' received", null, [ 'user_id' => $oMFAUserSettings->Get('user_id') ]);
			return true;
		} catch(\Exception $e){
			MFABaseLog::Info("Recovery code validation : wrong 'recovery_code' received", null,
				[
					'user_id' => $oMFAUserSettings->Get('user_id'),
					'exception' => $e
				]);
			unset($_POST['recovery_code']);
		}

		return false;
	}




}
