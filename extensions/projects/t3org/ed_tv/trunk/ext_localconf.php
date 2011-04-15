<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'FE') {
	require_once(t3lib_extMgm::extPath('ed_tv').'class.tx_edtv.php');
}

$TYPO3_CONF_VARS['FE']['addRootLineFields'] .= ',tx_templavoila_flex';

?>