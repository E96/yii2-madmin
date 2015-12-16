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

            $res[] = self::castByGramInfo($phpMorphy, $word, ['ЕД', 'ВН']);
            $res[] = self::castByGramInfo($phpMorphy, $word, ['ЕД', 'РД']);
            $res[] = self::castByGramInfo($phpMorphy, $word, ['МН', 'РД']);

            $res = array_map('mb_strtolower', $res);
            Yii::$app->cache->set($cacheKey, $res);
        }

        return $res;
    }

    /**
     * @param \phpMorphy $phpMorphy
     * @param $word
     * @param $gramInfo
     * @return string
     */
    protected static function castByGramInfo($phpMorphy, $word, $gramInfo)
    {
        $words = explode(' ', mb_strtoupper($word));

        $form = [];
        foreach ($words as $word) {
            $forms = $phpMorphy->castFormByGramInfo($word, null, $gramInfo, true);
            $form []= $forms[0];
        }
        return implode(' ', $form);
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