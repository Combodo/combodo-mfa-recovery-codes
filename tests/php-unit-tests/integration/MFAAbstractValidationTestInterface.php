<?php

namespace Combodo\iTop\MFARecoveryCodes\Test;

interface MFAAbstractValidationTestInterface {
	public function testValidationFirstScreenDisplay();

	public function testValidationFailed();

	public function testValidationForceReturnToLoginPage();

	public function testValidationOK();

	public function testValidationFailDueToInvalidTransactionId();
}
