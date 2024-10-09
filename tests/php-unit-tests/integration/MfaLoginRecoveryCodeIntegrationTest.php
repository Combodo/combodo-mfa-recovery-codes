<?php

namespace Combodo\iTop\MFABase\Test\Integration;

use Combodo\iTop\MFABase\Service\MFAUserSettingsService;
use Combodo\iTop\MFABase\Test\AbstractMFATest;
use Combodo\iTop\MFABase\Test\MFAAbstractConfigurationTestInterface;
use Combodo\iTop\MFABase\Test\MFAAbstractValidationTestInterface;
use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use Dict;
use MetaModel;
use MFAAdminRule;
use User;

require_once dirname(__DIR__) . "/AbstractMFATest.php";
require_once __DIR__ . "/MFAAbstractValidationTestInterface.php";
require_once __DIR__ . "/MFAAbstractConfigurationTestInterface.php";

/**
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 *
 */
class MfaLoginRecoveryCodeIntegrationTest extends AbstractMFATest implements MFAAbstractValidationTestInterface {
	//iTop called from outside
	//users need to be persisted in DB
	const USE_TRANSACTION = false;

	protected string $sConfigTmpBackupFile;
	protected string $sPassword;
	protected User $oUser;
	protected string $sUniqId;

	public function GetMFAUserSettingsRecoveryCodes(): \MFAUserSettingsRecoveryCodes {
		$oActiveSetting = $this->CreateSetting(\MFAUserSettingsRecoveryCodes::class, $this->oUser->GetKey(), 'yes', [], false);
		$this->CreateSetting(\MFAUserSettingsTOTPApp::class, $this->oUser->GetKey(), 'yes', [], true);

		return $oActiveSetting;
	}

	protected function setUp(): void {
		parent::setUp();

		$sConfigPath = MetaModel::GetConfig()->GetLoadedFile();

		clearstatcache();
		echo sprintf("rights via ls on %s:\n %s \n", $sConfigPath, exec("ls -al $sConfigPath"));
		$sFilePermOutput = substr(sprintf('%o', fileperms('/etc/passwd')), -4);
		echo sprintf("rights via fileperms on %s:\n %s \n", $sConfigPath, $sFilePermOutput);

		$this->sConfigTmpBackupFile = tempnam(sys_get_temp_dir(), "config_");
		MetaModel::GetConfig()->WriteToFile($this->sConfigTmpBackupFile);

		$this->sUniqId = "MFABASE" . uniqid();
		$this->CleanupAdminRules();
		$this->CleanupMFASettings();
		$this->sPassword = "abCDEF12345@";
		/** @var User oUser */
		$this->oUser = $this->CreateContactlessUser('login' . uniqid(),
			ItopDataTestCase::$aURP_Profiles['Service Desk Agent'],
			$this->sPassword
		);

		$this->oiTopConfig = new \Config($sConfigPath);
		$this->oiTopConfig->SetModuleSetting('combodo-mfa-base', 'enabled', true);
		//$this->oiTopConfig->Set('transactions_enabled', false);
		//$this->oiTopConfig->Set('log_transactions', true);
		$this->SaveItopConfFile();
	}

	protected function tearDown(): void {
		parent::tearDown();

		if (! is_null($this->sConfigTmpBackupFile) && is_file($this->sConfigTmpBackupFile)){
			//put config back
			$sConfigPath = $this->oiTopConfig->GetLoadedFile();
			@chmod($sConfigPath, 0770);
			$oConfig = new \Config($this->sConfigTmpBackupFile);
			$oConfig->WriteToFile($sConfigPath);
			@chmod($sConfigPath, 0440);
		}

		$_SESSION = [];
	}

	public function CheckThereIsAReturnToLoginPageLink($sOutput) {
		$sForceRestartLoginLabelLink = Dict::S('Login:MFA:Restart:Label');
		$sHtml = <<<HTML
<a onclick="$('#mfa_restart_login_form').submit();">$sForceRestartLoginLabelLink</a></div>
HTML;
		$this->AssertStringContains($sHtml, $sOutput, 'The page should be contain a link to return to login page');
	}

	public function testValidationFirstScreenDisplay()
	{
		// Arrange
		$this->GetMFAUserSettingsRecoveryCodes();

		// Act
		$sOutput = $this->CallItopUrl('/pages/UI.php',
			[
				'auth_user' => $this->oUser->Get('login'),
				'auth_pwd' => $this->sPassword,
				'selected_mfa_mode' => \MFAUserSettingsRecoveryCodes::class,
			]);

		// Assert
		$sTitle = Dict::S('MFA:RC:CodeValidation:Title');
		$this->AssertStringContains($sTitle, $sOutput, 'The page should be the Recovery code validation screen');
		$this->AssertStringContains('<input type="text" id="recovery_code" name="recovery_code" value="" size="16"', $sOutput, 'The page should have a code input form');
		$this->CheckThereIsAReturnToLoginPageLink($sOutput);

	}

	public function testValidationFailed()
	{
		// Arrange
		$oActiveSetting = $this->GetMFAUserSettingsRecoveryCodes();

		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);
		// Act
		$sLogin = $this->oUser->Get('login');
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'transaction_id' => $this->GetNewGeneratedTransId($sLogin),
			'recovery_code' => 'WrongCode',
			'selected_mfa_mode' => \MFAUserSettingsRecoveryCodes::class,
			'auth_user' => $sLogin,
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the Recovery code validation screen');
		$this->AssertStringNotContains(Dict::S('UI:Login:Welcome'), $sOutput, 'The page should be the initial login page');
	}

	public function testValidationForceReturnToLoginPage()
	{
		// Arrange
		$oActiveSetting = $this->GetMFAUserSettingsRecoveryCodes();

		// Act
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'auth_user' => $this->oUser->Get('login'),
			'auth_pwd' => $this->sPassword,
			'mfa_restart_login' => 'true',
			'selected_mfa_mode' => \MFAUserSettingsRecoveryCodes::class,
			]
		);

		// Assert
		$this->AssertStringContains(Dict::S('UI:Login:Welcome'), $sOutput, 'The page should be the initial login page');
	}

	public function testValidationOK()
	{
		// Arrange
		$oActiveSetting = $this->GetMFAUserSettingsRecoveryCodes();

		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);
		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesById($oActiveSetting);
		$sCode = array_pop($aCodes);

		// Act
		$sLogin = $this->oUser->Get('login');
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'transaction_id' => $this->GetNewGeneratedTransId($sLogin),
			'recovery_code' => $sCode,
			'selected_mfa_mode' => \MFAUserSettingsRecoveryCodes::class,
			'auth_user' => $sLogin,
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringNotContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the Recovery code validation screen');
		$sWelcomeWithoutIopApplicationName = str_replace(ITOP_APPLICATION, "", Dict::S('UI:WelcomeToITop'));
		$this->AssertStringContains($sWelcomeWithoutIopApplicationName, $sOutput, 'The page should be the welcome page');
		$sLoggedInAsMessage = Dict::Format('UI:LoggedAsMessage', '', $sLogin);
		$this->AssertStringContains($sLoggedInAsMessage, $sOutput, 'The proper user should be connected');

		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesAndStatus($oActiveSetting);
		$sStatus=$aCodes[$sCode] ?? 'notfound';
		$this->assertEquals("inactive", $sStatus, "Recovery code once used should be not reusable");}

	public function testValidationFailDueToInvalidTransactionId()
	{
		// Arrange
		$oActiveSetting = $this->GetMFAUserSettingsRecoveryCodes();

		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);
		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesById($oActiveSetting);
		$sCode = array_pop($aCodes);

		// Act
		$sLogin = $this->oUser->Get('login');
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'transaction_id' => "WrongID",
			'recovery_code' => $sCode,
			'selected_mfa_mode' => \MFAUserSettingsRecoveryCodes::class,
			'auth_user' => $sLogin,
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the Recovery code validation screen');
		$this->AssertStringNotContains(Dict::S('UI:Login:Welcome'), $sOutput, 'The page should be the initial login page');
	}

	private function GetNewGeneratedTransId(string $sLogin) {
		\UserRights::Login($sLogin);
		$sTransId = \utils::GetNewTransactionId();
		\UserRights::_ResetSessionCache();

		return $sTransId;
	}
}
