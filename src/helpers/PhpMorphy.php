<?php

namespace e96\madmin\helpers;


use phpMorphy_Util_MbstringOverloadFixer;
use Yii;

class PhpMorphy
{
    public static function getNeededForms($word)
    {
        $cacheKey = __CLASS__ . __FUNCTION__ .  $word;
        $res = Yii::$app->cache->get($cacheKey);
        if ($res === false) {
            $res = [];

            phpMorphy_Util_MbstringOverloadFixer::fix();
            $phpMorphy = new \phpMorphy(
                Yii::getAlias('@madmin/phpmorphy-dicts'),
                'ru_RU',
                ['storage' => PHPMORPHY_STORAGE_FILE]
            );
            mb_internal_encoding('UTF-8');

            $res[] = self::castByGramInfo($phpMorphy, $word, ['ЕД', 'ВН']); // добавить
            $res[] = self::castByGramInfo($phpMorphy, $word, ['ЕД', 'РД']); // редактирование
            $res[] = self::castByGramInfo($phpMorphy, $word, ['МН', 'РД']); // список

            $res = array_map('mb_strtolower', $res);
            Yii::$app->cache->set($cacheKey, $res);
        }

        return $res;
    }

    /**
     * @param \phpMorphy $phpMorphy
     * @param string $word
     * @param string[] $targetGramInfo
     * @return string
     */
    protected static function castByGramInfo($phpMorphy, $word, $targetGramInfo)
    {
        $words = explode(' ', mb_strtoupper($word));

        $form = [];
        $originalTargetGramInfo = $targetGramInfo;
        foreach ($words as $word) {
            $targetGramInfo = $originalTargetGramInfo;

            $gramInfo = $phpMorphy->getGramInfo($word);
            $gramInfo = self::selectAcceptableForm($gramInfo);
            if ($gramInfo['pos'] == 'ПРИЧАСТИЕ') {
                // учитываем залог у причастия
                $targetGramInfo[] = in_array('СТР', $gramInfo['grammems']) ? 'СТР' : 'ДСТ';
            }
            if ($gramInfo['pos'] == 'С') {
                // Если число существительного не единственное, используем его
                if (!array_intersect($gramInfo['grammems'], ['ЕД'])) {
                    unset($targetGramInfo[0]);
                    $targetGramInfo[0] = 'МН';
                }
                // Если падеж существительного не именительный, тогда используем его  
                if (!array_intersect($gramInfo['grammems'], ['ИМ', 'ВН'])) {
                    unset($targetGramInfo[1]);
                    $padezh = array_intersect($gramInfo['grammems'], ['РД', 'ДТ', 'ТВ', 'ПР']);
                    $targetGramInfo[1] = reset($padezh);
                }
            }

            $forms = $phpMorphy->castFormByGramInfo($word, $gramInfo['pos'], $targetGramInfo, true);
            $form []= $forms[0];
        }
        return implode(' ', $form);
    }

    protected static function selectAcceptableForm($gramInfo)
    {
        foreach (['С', 'П', 'ПРИЧАСТИЕ'] as $pos) {
            foreach ($gramInfo as $entry) {
                if ($entry[0]['pos'] == $pos) {
                    return $entry[0];
                }
            }
        }
        
        return $gramInfo[0][0];
    }

    /**
     * @param string $word
     * @return bool|string
     */
    public static function castChosenWordBy($word)
    {
        $cacheKey = __CLASS__ . __FUNCTION__ .  $word;
        $res = Yii::$app->cache->get($cacheKey);
        if ($res === false) {
            $phpMorphy = new \phpMorphy(
                Yii::getAlias('@madmin/phpmorphy-dicts'),
                'ru_RU',
                ['storage' => PHPMORPHY_STORAGE_FILE]
            );
            mb_internal_encoding('UTF-8');
            $forms = $phpMorphy->getGramInfo(mb_strtoupper($word));
            $forms = $forms[0];
            foreach ($forms as $form) {
                if (in_array('ИМ', $form['grammems'])) {
                    $rod = array_intersect($form['grammems'], ['МР', 'ЖР', 'СР', 'МР-ЖР']);
                    $rod = reset($rod);
                    $od = array_intersect($form['grammems'], ['ОД', 'НО']);
                    $od = reset($od);
                    break;
                }
            }
            if (!empty($rod) && !empty($od)) {
                $form = $phpMorphy->castFormByGramInfo(mb_strtoupper('выбранный'), 'ПРИЧАСТИЕ', [$rod, $od, 'ВН', 'ЕД', 'ПРШ', 'СТР'], true);
                $res = mb_strtolower($form[0]);
            }

            Yii::$app->cache->set($cacheKey, $res);
        }

        return $res;
    }
}