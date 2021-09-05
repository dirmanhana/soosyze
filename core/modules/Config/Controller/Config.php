<?php

declare(strict_types=1);

namespace SoosyzeCore\Config\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;

class Config extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
    }

    public function admin(ServerRequestInterface $req): ResponseInterface
    {
        if (($menu = $this->getMenuConfig()) && !empty($menu)) {
            return $this->getConfig($menu, array_keys($menu)[ 0 ], $req);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-cog" aria-hidden="true"></i>',
                    'title_main' => t('Configuration')
                ])
                ->make('page.content', 'config/content-config-form.php', $this->pathViews, [
                    'form' => null
        ]);
    }

    public function edit(string $id, ServerRequestInterface $req): ResponseInterface
    {
        if ($menu = $this->getMenuConfig()) {
            return $this->getConfig($menu, $id, $req);
        }

        return $this->get404($req);
    }

    public function update(string $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($menu = $this->getMenuConfig()) || !isset($menu[ $id ])) {
            return $this->get404($req);
        }
        if ($req->isMaxSize()) {
            return $this->json(400, [
                    'messages'    => [
                        'errors' => [
                            t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
                        ]
                    ]
            ]);
        }

        $validator = (new Validator())
            ->addRule('token_' . $id . '_config', 'token')
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles());
        $inputsFile = [];

        $config = $this->container->get("$id.hook.config");

        $config->validator($validator);
        $config->files($inputsFile);

        if ($validator->isValid()) {
            $fileConfig = empty($menu[ $id ][ 'config' ])
                ? 'settings'
                : $menu[ $id ][ 'config' ];

            $data = [];

            $config->before($validator, $data, $id);
            foreach ($data as $key => $value) {
                self::config()->set("$fileConfig.$key", $value);
            }
            foreach ($inputsFile as $file) {
                $this->saveFile($file, $validator);
            }
            $config->after($validator, $data, $id);

            $_SESSION[ 'messages' ][ 'success' ][] = t('Saved configuration');

            return $this->json(200, [
                    'redirect' => self::router()->getRoute('config.edit', [ ':id' => $id ])
            ]);
        }

        return $this->json(400, [
                'messages'    => [ 'errors' => $validator->getKeyErrors() ],
                'errors_keys' => $validator->getKeyInputErrors()
        ]);
    }

    private function getConfig(array $menu, string $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!isset($menu[ $id ])) {
            return $this->get404($req);
        }

        $config = $this->container->get("$id.hook.config");

        /* Replace les valeurs par défaut si la données et présente dans la config. */
        $data = array_replace_recursive(
            $config->defaultValues(),
            self::config()->get($menu[ $id ][ 'config' ] ?? 'settings', [])
        );

        $this->container->callHook("config.edit.$id.form.data", [ &$data, $id ]);

        $form = new FormBuilder([
            'action'  => self::router()->getRoute('config.update', [ ':id' => $id ]),
            'class'   => 'form-api',
            'enctype' => 'multipart/form-data',
            'method'  => 'put'
        ]);

        $config->form($form, $data, $req);

        $form->group('submit-group', 'div', function ($form) use ($id) {
            $form->token('token_' . $id . '_config')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ]);
        });
        $this->container->callHook("config.edit.$id.form", [ &$form, $data, $req ]);

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-cog" aria-hidden="true"></i>',
                    'title_main' => t('Configuration')
                ])
                ->make('page.content', 'config/content-config-form.php', $this->pathViews, [
                    'form' => $form
                ])
                ->make('content.menu_config', 'config/submenu-config.php', $this->pathViews, [
                    'menu'      => $menu,
                    'key_route' => $id
        ]);
    }

    private function getMenuConfig(): array
    {
        $menu = [];
        $this->container->callHook('config.edit.menu', [ &$menu ]);
        ksort($menu);

        $all = $this->container->callHook('app.granted', [ 'config.manage' ]);
        foreach ($menu as $key => &$link) {
            if ($all || $this->container->callHook('app.granted', [ $key . '.config.manage' ])) {
                $link[ 'link' ] = self::router()->getRoute('config.edit', [ ':id' => $key ]);

                continue;
            }
            unset($menu[ $key ]);
        }
        unset($link);

        return $menu;
    }

    private function saveFile(string $key, Validator $validator): void
    {
        self::file()
            ->add($validator->getInput($key), $validator->getInput("file-$key-name"))
            ->setName($key)
            ->setPath('/config')
            ->isResolvePath()
            ->callGet(function ($key, $name) {
                return self::config()->get("settings.$key");
            })
            ->callMove(function ($key, $name, $move) {
                self::config()->set("settings.$key", $move);
            })
            ->callDelete(function ($key, $name) {
                self::config()->set("settings.$key", '');
            })
            ->save();
    }
}
