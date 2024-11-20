<?php

namespace addons\simditor;

use app\common\library\Menu;
use think\Addons;

/**
 * 插件
 */
class Simditor extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    public function upgrade()
    {
        return true;
    }

    /**
     * @param $params
     */
    public function configInit(&$params)
    {
        $config = $this->getConfig();
        $params['simditor'] = [
            'classname'     => $config['classname'] ?? '.editor',
            'height'        => $config['height'] ?? 250,
            'minHeight'     => $config['minHeight'] ?? 250,
            'placeholder'   => $config['placeholder'] ?? '',
            'toolbarFloat'  => $config['toolbarFloat'] ?? 0,
            'toolbar'       => (array)json_decode($config['toolbar'] ?? '', true),
            'mobileToolbar' => (array)json_decode($config['mobileToolbar'] ?? '', true),
        ];
    }

}
