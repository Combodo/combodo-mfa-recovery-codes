<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Service;

use Combodo\iTop\Application\Helper\Session;
use Combodo\iTop\MFABase\Helper\MFABaseException;
use Combodo\iTop\MFABase\Helper\MFABaseHelper;
use Combodo\iTop\MFABase\Helper\MFABaseLog;
use Combodo\iTop\MFARecoveryCodes\Helper\MFARecoveryCodesHelper;
use Dict;
use Exception;
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

	/**
	 * @param \MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 *
	 * @return string
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	public function GetConfigurationURLForMyAccountRedirection(MFAUserSettingsRecoveryCodes $oMFAUserSettings): string
	{
		try {
			return utils::GetAbsoluteUrlModulePage(MFARecoveryCodesHelper::MODULE_NAME, 'index.php', ['operation' => 'MFARecoveryCodesView']);
		} catch (MFABaseException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new MFABaseException(__FUNCTION__.' failed', 0, $e);
		}
	}

	/**
	 * @param \MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 *
	 * @return \LoginTwigContext
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	public function GetTwigContextForLoginValidation(MFAUserSettingsRecoveryCodes $oMFAUserSettings): LoginTwigContext
	{
		try {
			$oLoginContext = new LoginTwigContext();

			$aData = [];
			$aData['sTitle'] = Dict::S('MFA:RC:CodeValidation:Title');
			$aData['sTransactionId'] = utils::GetNewTransactionId();

			$oLoginContext->SetLoaderPath(MODULESROOT.MFARecoveryCodesHelper::MODULE_NAME.'/templates/login');
			$oLoginContext->AddBlockExtension('mfa_validation', new \LoginBlockExtension('MFARecoveryCodesValidate.html.twig', $aData));
			$oLoginContext->AddBlockExtension('mfa_title', new \LoginBlockExtension('MFARecoveryCodesTitle.html.twig', $aData));
			$oLoginContext->AddJsFile(MFARecoveryCodesHelper::GetJSFile());

			return $oLoginContext;
		} catch (MFABaseException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new MFABaseException(__FUNCTION__.' failed', 0, $e);
		}
	}

	/**
	 * @param \MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 * @param array $aData
	 *
	 * @return \LoginTwigContext|null
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	private function ValidateCode(MFAUserSettingsRecoveryCodes $oMFAUserSettings, array &$aData): ?LoginTwigContext
	{
		try {
			Session::Set('mfa_configuration_validated', 'true');
			$aData['sTitle'] = Dict::S('UI:MFA:Redirection:Title');
			$oLoginContext = new LoginTwigContext();
			$oLoginContext->SetLoaderPath(MODULESROOT.MFARecoveryCodesHelper::MODULE_NAME.'/templates/login');
			$oLoginContext->AddBlockExtension('mfa_title', new \LoginBlockExtension('MFALoginTitle.html.twig', $aData));
			$oLoginContext->AddBlockExtension('script', new \LoginBlockExtension('MFALoginRedirect.ready.js.twig', $aData));

			return $oLoginContext;
		} catch (MFABaseException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new MFABaseException(__FUNCTION__.' failed', 0, $e);
		}
	}

	/**
	 * @param \MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 *
	 * @return bool
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	public function HasToDisplayValidation(MFAUserSettingsRecoveryCodes $oMFAUserSettings): bool
	{
		try {
			$sCode = utils::ReadPostedParam('recovery_code', false, utils::ENUM_SANITIZATION_FILTER_INTEGER);
			if ($sCode !== false) {
				MFABaseLog::Debug('Recovery code received', null, ['code' => $sCode]);
			}

			return ($sCode === false);
		} catch (Exception $e) {
			throw new MFABaseException(__FUNCTION__.' failed', 0, $e);
		}
	}

	/**
	 * @param \MFAUserSettingsRecoveryCodes $oMFAUserSettings
	 *
	 * @return bool
	 * @throws \Combodo\iTop\MFABase\Helper\MFABaseException
	 */
	public function ValidateLogin(MFAUserSettingsRecoveryCodes $oMFAUserSettings): bool
	{
		try {
			MFABaseHelper::GetInstance()->ValidateTransactionId();

			$sCode = utils::ReadPostedParam('recovery_code', 0, utils::ENUM_SANITIZATION_FILTER_STRING);
			MFABaseLog::Debug("Recovery code received", null, ['code' => $sCode]);
			if ($sCode === 0) {
				MFABaseLog::Debug("Recovery code validation : no 'recovery_code' received", null, ['user_id' => $oMFAUserSettings->Get('user_id')]);

				return false;
			}

			if ($sCode === false) {
				MFABaseLog::Debug("Recovery code validation : invalid 'recovery_code' received (sanitization)", null, ['user_id' => $oMFAUserSettings->Get('user_id')]);
				unset($_POST['recovery_code']);

				return false;
			}

			$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();

			$oMFAUserSettingsRecoveryCodesService->InvalidateCode($oMFAUserSettings, $sCode);
			MFABaseLog::Debug("Recovery code validation : correct 'recovery_code' received", null, ['user_id' => $oMFAUserSettings->Get('user_id')]);

			return true;
		} catch (MFABaseException $e) {
			// Already logged
		} catch (Exception $e) {
			MFABaseLog::Info("Recovery code validation failed", null,
				[
					'user_id' => $oMFAUserSettings->Get('user_id'),
					'error' => $e->getMessage(),
					'stack' => $e->getTraceAsString(),
				]);
		}
		unset($_POST['recovery_code']);

		return false;
	}

}
