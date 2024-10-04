<?php
/**
 *  @copyright   Copyright (C) 2010-2019 Combodo SARL
 *  @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MFARecoveryCodes;


use Combodo\iTop\MFABase\Helper\MFABaseHelper;
use Combodo\iTop\MFARecoveryCodes\Controller\MFARecoveryCodesMyAccountController;
use Combodo\iTop\MFARecoveryCodes\Helper\MFARecoveryCodesHelper;

require_once(APPROOT.'application/startup.inc.php');

$sTemplates = MODULESROOT.MFARecoveryCodesHelper::MODULE_NAME.'/templates/my_account';
$aAdditionalPaths = [MODULESROOT.MFABaseHelper::MODULE_NAME.'/templates/my_account'];

$oUpdateController = new MFARecoveryCodesMyAccountController($sTemplates, MFARecoveryCodesHelper::MODULE_NAME, $aAdditionalPaths);
$oUpdateController->SetDefaultOperation('MFARecoveryCodesConfig');
$oUpdateController->HandleOperation();
