<?php

namespace app\work\controller;

use think\Controller;

class Index extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    //获取名片数据
    public function CardInfo()
    {
        if ($this->request->isPost()) 
        {
            $card_logo = config('site.card_logo');

            if (!empty($card_logo)) 
            {
                $domain = $this->request->domain();
                $card_logo = trim($card_logo, '/');
                $card_logo = $domain . '/' . $card_logo;
            }

            $data = [
                'card_nickname' => config('site.card_nickname'),
                'card_job' => config('site.card_job'),
                'card_company' => config('site.card_company'),
                'card_mobile' => config('site.card_mobile'),
                'card_email' => config('site.card_email'),
                'card_address' => config('site.card_address'),
                'card_logo' => $card_logo
            ];

            $this->success('返回名片数据', null, $data);
            exit;
        }
    }
}