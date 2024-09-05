<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Controller;

use Combodo\iTop\Application\TwigBase\Controller\Controller;

class MFARecoveryCodesMyAccountController extends Controller
{
	public function OperationMFARecoveryCodesConfig()
	{
		$aParams = [];


		$this->DisplayPage($aParams);
	}
}