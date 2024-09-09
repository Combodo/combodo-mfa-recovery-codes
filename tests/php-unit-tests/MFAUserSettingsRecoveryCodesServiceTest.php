<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Test;

use Combodo\iTop\MFARecoveryCodes\Service\MFAUserSettingsRecoveryCodesService;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;

class MFAUserSettingsRecoveryCodesServiceTest extends ItopDataTestCase
{
	public const USE_TRANSACTION = true;

	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('/env-production/combodo-mfa-recovery-codes/vendor/autoload.php');

	}

	public function testCreateUserSettingsHasNoCode()
	{
		// Given
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();
		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);

		// Then
		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$this->assertCount(0, $oService->GetCodesAndStatus($oUserSettings));
	}

	public function testCreateCodes()
	{
		// Given
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();
		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);
		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();

		// When
		$oService->CreateCodes($oUserSettings);

		// Then
		$aCodes = $oService->GetCodesById($oUserSettings);
		var_export($aCodes);
		$this->assertCount(MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT, $aCodes, 'The amount of codes is exact at creation');

		$aCodes = $oService->GetCodesAndStatus($oUserSettings);
		var_export($aCodes);
		foreach ($aCodes as $sCode => $sStatus) {
			$this->assertEquals('active', $sStatus, 'All the codes are valid when created');
		}
	}

	public function testCodesInvalidatedExistingCode()
	{
		// Given
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();
		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);
		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$oService->CreateCodes($oUserSettings);
		$aCodes = $oService->GetCodesAndStatus($oUserSettings);
		$aCodeValues = array_keys($aCodes);
		$sFirstCode = reset($aCodeValues);

		// When
		$oService->InvalidateCode($oUserSettings, $sFirstCode);

		// Then
		$aCodes = $oService->GetCodesAndStatus($oUserSettings);
		var_export($aCodes);
		$this->assertEquals('inactive', $aCodes[$sFirstCode] ?? 'not found', 'Code should be inactive when invalidated');
	}

	public function testDeleteCodes()
	{
		// Given
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();
		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);
		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$oService->CreateCodes($oUserSettings);

		// When
		$oService->DeleteCodes($oUserSettings);

		// Then
		$this->assertCount(0, $oService->GetCodesAndStatus($oUserSettings));
	}

	public function testRebuildCodesGenerateAllDifferentCodes()
	{
		// Given
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();
		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);
		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();
		$oService->CreateCodes($oUserSettings);
		$aCodes = $oService->GetCodesById($oUserSettings);

		// When
		$oService->RebuildCodes($oUserSettings);

		// Then
		$aNewCodes = $oService->GetCodesById($oUserSettings);

		foreach ($aCodes as $sCode) {
			foreach ($aNewCodes as $sNewCode) {
				$this->assertNotEquals($sNewCode, $sCode);
			}
		}
	}
}