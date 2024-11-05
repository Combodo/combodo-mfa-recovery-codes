<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('FR FR', 'French', 'Français', array(
	'combodo-mfa-recovery-codes/Operation:MFARecoveryCodesView/Title' => 'Voir les codes de récupération',
	'MFA:MFAUserSettingsRecoveryCodes:Description' => 'Fournit une liste de 10 codes de récupération à usage unique pour accéder à votre compte si vous perdez l\'accès à votre appareil',

	'MFA:login:switch:label:MFAUserSettingsRecoveryCodes' => 'Utiliser un code de récupération',

	'MFA:RC:CodeValidation:Title' => 'Entrez le code de récupération',
	'MFA:RC:EnterCode' => 'Code',
	'MFA:RC:Config:Title' => 'Codes de récupération MFA',
	'MFA:RC:Config:Warning' => 'Conservez vos codes de récupération de façon aussi sécurisée que votre mot de passe. Nous vous recommandons de les enregistrer avec un gestionnaire de mots de passe',
	'MFA:RC:Settings:Title' => 'Réglages',
	'MFA:RC:Settings:Code:label' => 'Liste des codes de récupération',
	'MFA:RC:Copy' => 'Copier les codes de récupération dans le presse-papiers',
	'MFA:RC:Copy:Done' => 'Codes de récupération copiés dans le presse-papiers',
	'MFA:RC:RebuildCodes' => 'Générer de nouveaux codes de récupération',
	'MFA:RC:RebuildCodes+' => 'Lorsque de nouveaux codes de récupération sont générés, les anciens ne fonctionneront plus',

	'Class:MFAUserSettingsRecoveryCodes' => 'Codes de récupération',
	'Class:MFAUserSettingsRecoveryCodes/Attribute:mfarecoverycodes_list' => 'Liste des codes de récupération',

	'Class:MFARecoveryCode' => 'Code de récupération',
	'Class:MFARecoveryCode/Attribute:code' => 'Code',
	'Class:MFARecoveryCode/Attribute:status' => 'Statut',
	'Class:MFARecoveryCode/Attribute:mfausersettingsrecoverycodes_id' => 'Réglages utilisateur',

));
