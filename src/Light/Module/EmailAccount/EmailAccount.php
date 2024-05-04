<?php


namespace Light\Module\EmailAccount;


use Light\Html;
use Light\Misc;
use Light\ModuleBase\ModuleAbstract;
use Light\Field\FieldAbstract as F;

class EmailAccount extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setNameFieldKey('Username');

        $this->addFields(

            F::createString('Host', [
                'name' => 'SMTP Host',
                'placeholder' => 'smtp.yandex.ru',
            ]),
            F::createInt('Port', [
                'default' => 587,
                'title' => 'TLS: 587; SSL: 465; No Encryption: 25',
            ]),
            F::createSelect('Encryption', [
                'nullable' => true,
                'default' => 'tls',
                'empty' => true,
                'options' => [
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                ],
            ]),
            F::createString('Username', [
                'name' => 'Username',
                'placeholder' => 'example@yandex.ru',
            ]),
            F::createString('Password', [
                'name' => 'Password',
                'modes' => F::MODE_ALL & ~F::MODE_LOG,
                'getHtmlFunction' => fn($value, array $data, $outHtml) => Html::showHide('Show', $outHtml),
            ])

        );
    }

}
