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

	public function testCreateCodes()
	{
		$oUser = $this->CreateContactlessUser('NoOrgUser', ItopDataTestCase::$aURP_Profiles['Service Desk Agent'], 'ABCdefg@12345#');
		$sUserId = $oUser->GetKey();

		$oUserSettings = $this->createObject(\MFAUserSettingsRecoveryCodes::class, ['user_id' => $sUserId]);

		$oService = MFAUserSettingsRecoveryCodesService::GetInstance();

		$this->assertCount(0, $oService->GetCodesAsArray($oUserSettings));

		$oService->CreateCodes($oUserSettings);

		$aCodes = $oService->GetCodesAsArray($oUserSettings);
		var_export($aCodes);
		$this->assertCount(MFAUserSettingsRecoveryCodesService::RECOVERY_CODES_COUNT, $aCodes);
	}
}