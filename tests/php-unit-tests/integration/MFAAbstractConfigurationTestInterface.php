<?php

namespace Combodo\iTop\MFARecoveryCodes\Test;

interface MFAAbstractConfigurationTestInterface {
	public function testConfigurationFirstScreenDisplay();

	public function testConfigurationFailed();

	public function testConfigurationForceReturnToLoginPage();

	public function testConfigurationOK();

	public function testConfigurationFailDueToInvalidTransactionId();
}
