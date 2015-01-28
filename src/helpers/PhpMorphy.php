<?php

namespace e96\madmin\helpers;


class PhpMorphy 
{
    public static function getNeededForms($word)
    {
        $cacheKey = __CLASS__ . $word;
        $res = \Yii::$app->cache->get($cacheKey);
        if ($res === false) {
            $res = [];

            $phpMorphy = new \phpMorphy(
                \Yii::getAlias('@madmin/phpmorphy-dicts'),
                'ru_RU',
                ['storage' => PHPMORPHY_STORAGE_FILE]
            );
            mb_internal_encoding('UTF-8');
            $form = $phpMorphy->castFormByGramInfo(mb_strtoupper($word), null, ['ЕД', 'ВН'], true);
            $res[] = $form[0];
            $form = $phpMorphy->castFormByGramInfo(mb_strtoupper($word), null, ['ЕД', 'РД'], true);
            $res[] = $form[0];
            $form = $phpMorphy->castFormByGramInfo(mb_strtoupper($word), null, ['МН', 'РД'], true);
            $res[] = $form[0];

            $res = array_map('mb_strtolower', $res);
            \Yii::$app->cache->set($cacheKey, $res);
        }

        return $res;
    }
}