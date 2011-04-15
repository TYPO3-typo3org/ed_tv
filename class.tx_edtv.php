<?php

if (t3lib_extMgm::isLoaded('languagevisibility')) {
    require_once (t3lib_extMgm::extPath("languagevisibility") . 'class.tx_languagevisibility_feservices.php');
}

class tx_edtv {
	function fetchField($content, $conf) {
		
		$cObj = t3lib_div::makeInstance ( 'tslib_cObj' );
		
		$source = $cObj->stdWrap ( $conf ['source'], $conf ['source.'] );
		$field = $cObj->stdWrap ( $conf ['field'], $conf ['field.'] );
		$langChildren = intval ( $cObj->stdWrap ( $conf ['langChildren'], $conf ['langChildren.'] ) );
		$langDisable = intval ( $cObj->stdWrap ( $conf ['langDisable'], $conf ['langDisable.'] ) );
                $elementUid = intval ( $cObj->stdWrap ( $conf ['elementUid'], $conf ['elementUid.'] ) );
                $elementTable = intval ( $cObj->stdWrap ( $conf ['elementTable'], $conf ['elementTable.'] ) );

                if (!$elementUid) {
                    $elementUid = $GLOBALS['TSFE']->id;
                }

                if (!$elementTable) {
                    $elementTable = 'pages';
                }

		$value = '';
		
		if ($source) {
			$languageFallback = $this->cObj->stdWrap ( $conf ['languageFallback'], $conf ['languageFallback.'] );

                        if (strlen ( $languageFallback )) {
                            if (t3lib_extMgm::isLoaded('languagevisibility')) {
                                $tryLangArr = tx_languagevisibility_feservices::getFallbackOrderForElement($elementUid, $elementTable, $GLOBALS['TSFE']->sys_language_content);
                                $translatedLanguagesArr = $this->getAvailableLanguages ( );
                                $languageFallback = str_replace('languagevisibility', implode(',', $tryLangArr), $languageFallback);
                            }

                            $tryLangArr = t3lib_div::intExplode ( ',', $languageFallback );
			} else {
				$tryLangArr = array ();
			}

                        if (!$translatedLanguagesArr) {
                            $translatedLanguagesArr = $this->getAvailableLanguages ( $elementUid );
                        }
			
			$xml = t3lib_div::xml2array ( $source );
			
			$tryLang = $GLOBALS ['TSFE']->sys_language_content;

			do {
				if ($langArr = $translatedLanguagesArr [$tryLang]) {
					$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $langArr ['ISOcode']);
					$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $langArr ['ISOcode'] : 'vDEF');
				} else {
					$lKey = 'lDEF';
					$vKey = 'vDEF';
				}
				$value = '';
				if (is_array ( $xml ) && is_array ( $xml ['data'] ) && is_array ( $xml ['data'] ['sDEF'] ) && is_array ( $xml ['data'] ['sDEF'] [$lKey] )) {
					$value = $this->getSubKey ( $xml ['data'] ['sDEF'] [$lKey], t3lib_div::trimExplode ( ',', $field, 1 ), $vKey );
				}
			} while ( (! strlen ( $value )) && strlen ( $tryLang = array_shift ( $tryLangArr ) ) );
		}
		
		return $value;
	}
	
	function getSubKey($arr, $keys, $vKey) {
		if (! is_array ( $arr )) {
			return '';
		}
		if (! count ( $keys )) {
			return $arr [$vKey];
		} else {
			$sKey = array_shift ( $keys );
			return $this->getSubKey ( $arr [$sKey], $keys, $vKey );
		}
	}
	
	function getAvailableLanguages($id = 0, $onlyIsoCoded = true, $setDefault = true, $setMulti = false) {
		global $LANG, $TYPO3_DB, $BE_USER, $TCA, $BACK_PATH, $TSFE;
		
		$id = intval($id);
		
		if ($TSFE->txedtv_AvailableLanguages && $TSFE->txedtv_AvailableLanguages[$id]) {
			$output = $TSFE->txedtv_AvailableLanguages[$id];
		} else {
                        t3lib_div::loadTCA ( 'sys_language' );
                        $flagAbsPath = t3lib_div::getFileAbsFileName ( $TCA ['sys_language'] ['columns'] ['flag'] ['config'] ['fileFolder'] );
                        $flagIconPath = $BACK_PATH . '../' . substr ( $flagAbsPath, strlen ( PATH_site ) );

                        $output = array ();

                        if ($id) {
                                $res = $TYPO3_DB->exec_SELECTquery ( 'DISTINCT sys_language.*', 'pages_language_overlay,sys_language', 'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . intval ( $id ) . ' AND pages_language_overlay.deleted=0', '', 'sys_language.title' );
                        } else {
                                $res = $TYPO3_DB->exec_SELECTquery ( 'sys_language.*', 'sys_language', 'sys_language.hidden=0', '', 'sys_language.title' );
                        }

                        if ($setDefault) {
                                $output [0] = array ('uid' => 0, 'ISOcode' => 'DEF' );
                        }

                        if ($setMulti) {
                                $output [- 1] = array ('uid' => - 1, 'ISOcode' => 'DEF' );
                        }

                        while ( TRUE == ($row = $TYPO3_DB->sql_fetch_assoc ( $res )) ) {
                                t3lib_BEfunc::workspaceOL ( 'sys_language', $row );
                                $output [$row ['uid']] = $row;

                                if ($row ['static_lang_isocode']) {
                                        $staticLangRow = t3lib_BEfunc::getRecord ( 'static_languages', $row ['static_lang_isocode'], 'lg_iso_2' );
                                        if ($staticLangRow ['lg_iso_2']) {
                                                $output [$row ['uid']] ['ISOcode'] = $staticLangRow ['lg_iso_2'];
                                        }
                                }
                                if (strlen ( $row ['flag'] )) {
                                        $output [$row ['uid']] ['flagIcon'] = @is_file ( $flagAbsPath . $row ['flag'] ) ? $flagIconPath . $row ['flag'] : '';
                                }

                                if ($onlyIsoCoded && ! $output [$row ['uid']] ['ISOcode'])
                                        unset ( $output [$row ['uid']] );
                        }

                        $TSFE->txedtv_AvailableLanguages[$id] = $output;
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ed_tv/class.tx_edtv.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ed_tv/class.tx_edtv.php']);
}

?>