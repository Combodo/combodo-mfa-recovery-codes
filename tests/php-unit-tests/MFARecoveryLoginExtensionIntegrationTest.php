<?php

namespace Combodo\iTop\MFABase\Test;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use Dict;
use MetaModel;
use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use User;

require_once __DIR__ . "/AbstractMFATest.php";

/**
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 *
 */
class MFATOTPLoginExtensionIntegrationTest extends AbstractMFATest {
	//iTop called from outside
	//users need to be persisted in DB
	const USE_TRANSACTION = false;

	protected string $sConfigTmpBackupFile;
	protected string $sPassword;
	protected User $oUser;

	protected function setUp(): void {
		parent::setUp();

		$this->RequireOnceUnitTestFile("AbstractMFATest.php");
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


	public function testValidationScreenDisplay()
	{
		// Arrange
		$this->CreateSetting('MFAUserSettingsRecoveryCodes', $this->oUser->GetKey(), 'yes', [], true);

		// Act
		$sOutput = $this->CallItopUrl('/pages/UI.php', ['auth_user' => $this->oUser->Get('login'), 'auth_pwd' => $this->sPassword]);

		// Assert
		$sTitle = Dict::S('MFA:RC:CodeValidation:Title');
		$this->AssertStringContains($sTitle, $sOutput, 'The page should be the Recovery code validation screen');
		$this->AssertStringContains('<input type="text" id="recovery_code" name="recovery_code" value="" size="16"', $sOutput, 'The page should have a code input form');
	}

	public function testValidationCodeFailed_WrongCode()
	{
		// Arrange
		/** @var \MFAUserSettingsRecoveryCodes $oActiveSetting */
		$oActiveSetting = $this->CreateSetting('MFAUserSettingsRecoveryCodes', $this->oUser->GetKey(), 'yes', [], true);
		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);

		// Act
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'recovery_code' => 'WrongCode',
			'auth_user' => $this->oUser->Get('login'),
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringNotContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the TOTP App code validation screen');
		$this->AssertStringContains(Dict::S('UI:Login:Welcome'), $sOutput, 'The page should be the initial login page');
	}

	public function testValidationCodeFailed_AlreadyUsedCode()
	{
		// Arrange
		/** @var \MFAUserSettingsRecoveryCodes $oActiveSetting */
		$oActiveSetting = $this->CreateSetting('MFAUserSettingsRecoveryCodes', $this->oUser->GetKey(), 'yes', [], true);
		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);
		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesById($oActiveSetting);
		$sCode = array_pop($aCodes);
		$oMFAUserSettingsRecoveryCodesService->InvalidateCode($oActiveSetting, $sCode);

		// Act
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'recovery_code' => 'WrongCode',
			'auth_user' => $this->oUser->Get('login'),
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringNotContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the TOTP App code validation screen');
		$this->AssertStringContains(Dict::S('UI:Login:Welcome'), $sOutput, 'The page should be the initial login page');
	}

	public function testValidationCodeOK()
	{
		// Arrange
		/** @var \MFAUserSettingsRecoveryCodes $oActiveSetting */
		$oActiveSetting = $this->CreateSetting('MFAUserSettingsRecoveryCodes', $this->oUser->GetKey(), 'yes', [], true);
		$oMFAUserSettingsRecoveryCodesService = new MFAUserSettingsRecoveryCodesService();
		$oMFAUserSettingsRecoveryCodesService->CreateCodes($oActiveSetting);
		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesById($oActiveSetting);
		$sCode = array_pop($aCodes);

		// Act
		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesById($oActiveSetting);
		$sOutput = $this->CallItopUrl('/pages/UI.php', [
			'recovery_code' => $sCode,
			'auth_user' => $this->oUser->Get('login'),
			'auth_pwd' => $this->sPassword]);

		// Assert
		$this->AssertStringNotContains(Dict::S('MFA:RC:CodeValidation:Title'), $sOutput, 'The page should NOT be the TOTP App code validation screen');
		$sWelcomeWithoutIopApplicationName = str_replace(ITOP_APPLICATION, "", Dict::S('UI:WelcomeToITop'));
		$this->AssertStringContains($sWelcomeWithoutIopApplicationName, $sOutput, 'The page should be the welcome page');
		$sLoggedInAsMessage = Dict::Format('UI:LoggedAsMessage', '', $this->oUser->Get('login'));
		$this->AssertStringContains($sLoggedInAsMessage, $sOutput, 'The proper user should be connected');

		$aCodes = $oMFAUserSettingsRecoveryCodesService->GetCodesAndStatus($oActiveSetting);
		$sStatus=$aCodes[$sCode] ?? 'notfound';
		$this->assertEquals("inactive", $sStatus, "Recovery code once used should be not reusable");
	}
}
