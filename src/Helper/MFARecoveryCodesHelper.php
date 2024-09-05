<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes\Helper;

use Combodo\iTop\MFABase\Helper\MFABaseLog;
use utils;

class MFARecoveryCodesHelper
{
	const MODULE_NAME = 'combodo-mfa-recovery-codes';

	private static MFARecoveryCodesHelper $oInstance;

	protected function __construct()
	{
		MFABaseLog::Enable();
	}

	final public static function GetInstance(): MFARecoveryCodesHelper
	{
		if (!isset(static::$oInstance)) {
			static::$oInstance = new static();
		}

		return static::$oInstance;
	}


	public static function GetSCSSFile(): string
	{
		return 'env-'.utils::GetCurrentEnvironment().'/'.self::MODULE_NAME.'/assets/css/MFARecoveryCodes.scss';
	}

	public static function GetJSFile(): string
	{
		return utils::GetAbsoluteUrlModulesRoot().self::MODULE_NAME.'/assets/js/MFARecoveryCodes.js';
	}
}