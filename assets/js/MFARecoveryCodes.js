// @copyright   Copyright (C) 2010-2024 Combodo SARL
// @license     http://opensource.org/licenses/AGPL-3.0

function CheckLoginCode() {
	var sCode = $("#recovery_code").val();
	if (sCode.length === 16) {
		$("#totp_form").trigger("submit");
	}
}
