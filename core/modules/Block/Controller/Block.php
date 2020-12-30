<?php

namespace SoosyzeCore\Block\Controller;

use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Block\Form\FormBlock;

class Block extends \Soosyze\Controller
{
    protected $pathViews;

    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function show($id, $req)
    {
        if (!($block = self::query()->from('block')->where('block_id', '==', $id)->fetch())) {
            return $this->get404($req);
        }

        $block[ 'link_edit' ]   = self::router()->getRoute('block.edit', [ ':id' => $id ]);
        $block[ 'link_delete' ] = self::router()->getRoute('block.delete', [ ':id' => $id ]);
        $block[ 'link_update' ] = self::router()->getRoute('block.update', [ ':id' => $id ]);

        if (!empty($block[ 'hook' ])) {
            $data = self::block()->getBlocks();
            $key  = $block[ 'key_block' ];

            $tpl = self::template()
                ->getTheme('theme_admin')
                ->createBlock($data[ $key ][ 'tpl' ], $data[ $key ][ 'path' ]);

            $block[ 'content' ] .= (string) $this->container->callHook(
                'block.' . $block[ 'hook' ],
                [ $tpl, $this->getOptions($block) ]
            );
        }

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/block-show.php', $this->pathViews)
                ->addVars([ 'block' => $block ]);
    }

    public function create($theme, $section)
    {
        $data = self::block()->getBlocks();

        $form = new FormBuilder([
            'method' => 'post',
            'action' => self::router()->getRoute('block.store', [
                ':theme' => $theme, ':section' => $section
            ])
        ]);

        foreach ($data as $key => $block) {
            if (empty($block[ 'hook' ])) {
                $content = self::template()
                    ->getTheme('theme_admin')
                    ->createBlock($block[ 'tpl' ], $block[ 'path' ])
                    ->addVars([
                    'src_image' => self::core()->getPath('modules', 'modules/core', false) . '/Block/Assets/static.svg'
                ]);
            } else {
                $tpl = self::template()
                    ->getTheme('theme_admin')
                    ->createBlock($block[ 'tpl' ], $block[ 'path' ]);

                $content = $this->container->callHook('block.' . $block[ 'hook' ], [
                    $tpl,
                    empty($block[ 'options' ])
                    ? []
                    : $block[ 'options' ]
                ]);
            }

            $form->group("key_block-$key-group", 'div', function ($form) use ($key, $content) {
                $form->radio('key_block', [
                        'id'    => "key_block-$key",
                        'value' => $key
                    ])
                    ->html('key_block-label', '<div:attr>:content</div>', [
                        'class'    => 'block-content',
                        ':content' => $content
                ]);
            });
        }

        $form->group('submit-group', 'div', function ($form) use ($section) {
            $form->token("token_$section")
                ->submit('submit', t('Add'), [ 'class' => 'btn btn-success' ]);
        });

        $this->container->callHook('block.create.form', [ &$form, $data ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/content-block-create.php', $this->pathViews)
                ->addVars([
                    'section' => $section,
                    'blocks'  => $data,
                    'form'    => $form
        ]);
    }

    public function store($theme, $section, $req)
    {
        $blocks = self::block()->getBlocks();

        $validator = (new Validator())
            ->setRules([
                'key_block'      => 'required|string|max:255',
                "token_$section" => 'token'
            ])
            ->setInputs($req->getParsedBody());

        $this->container->callHook('block.store.validator', [ &$validator ]);

        if ($validator->isValid()) {
            $block   = $blocks[ $validator->getInput('key_block') ];
            $content = '';

            if (empty($block[ 'hook' ])) {
                $block[ 'hook' ] = null;

                $content = (string) self::template()
                        ->getTheme('theme_admin')
                        ->createBlock($block[ 'tpl' ], $block[ 'path' ])
                        ->addVars([
                            'src_image' => self::core()->getPath('modules', 'modules/core', false) . '/Block/Assets/static.svg'
                ]);
            }

            $values = [
                'section'          => $section,
                'title'            => t($block[ 'title' ]),
                'content'          => $content,
                'weight'           => 1,
                'visibility_roles' => true,
                'roles'            => '1,2',
                'hook'             => $block[ 'hook' ],
                'key_block'        => $validator->getInput('key_block'),
                'options'          => empty($block[ 'options' ])
                    ? null
                    : json_encode($block[ 'options' ])
            ];

            $this->container->callHook('block.store.before', [ $validator, &$values ]);

            self::query()
                ->insertInto('block', array_keys($values))
                ->values($values)
                ->execute();

            $this->container->callHook('block.store.after', [ $validator, $values ]);
        }

        return new Redirect(
            self::router()->getRoute('block.section.admin', [ ':theme' => $theme ])
        );
    }

    public function edit($id, $req)
    {
        if (!($data = $this->find($id))) {
            return $this->get404($req);
        }
        $data[ 'roles' ]   = explode(',', $data[ 'roles' ]);
        $data[ 'options' ] = $this->getOptions($data);

        $this->container->callHook('block.edit.form.data', [ &$data, $id ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormBlock([
                'method' => 'post',
                'action' => self::router()->getRoute('block.update', [
                    ':id' => $data[ 'block_id' ]
                ])
            ]))
            ->setValues($data, $id, self::user()->getRoles())
            ->makeFields();

        if (!empty($data[ 'hook' ])) {
            $form->after('block-fieldset', function ($form) use ($data, $id) {
                self::core()->callHook("block.{$data[ 'hook' ]}.edit.form", [
                    &$form, $data, $id
                ]);
            });
        }

        $this->container->callHook('block.edit.form', [ &$form, $data, $id ]);

        if (isset($_SESSION[ 'errors' ])) {
            unset($_SESSION[ 'errors_keys' ][ 'roles' ]);
            $form->addErrors($_SESSION[ 'errors' ])
                ->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors' ], $_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/content-block-form.php', $this->pathViews)
                ->addVars([
                    'form'      => $form,
                    'link_show' => self::router()->getRoute('block.show', [ ':id' => $data[ 'block_id' ] ])
        ]);
    }

    public function update($id, $req)
    {
        if (!($block = $this->find($id))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'title'            => '!required|string|max:255',
                'content'          => '!required|string|max:5000',
                'class'            => '!required|string|max:255',
                'visibility_pages' => 'bool',
                'pages'            => '!required|string|to_htmlsc',
                'visibility_roles' => 'bool',
                'roles'            => '!required|array',
                "token_block_$id"  => 'token'
            ])
            ->setLabels([
                'title'   => t('Title'),
                'content' => t('Content'),
                'pages'   => t('List of pages'),
                'roles'   => t('User Roles')
            ])
            ->setInputs(
                $req->getParsedBody()
            );

        if ($block[ 'hook' ]) {
            $this->container->callHook("block.{$block[ 'hook' ]}.update.validator", [ &$validator, $id ]);
        }
        $this->container->callHook('block.update.validator', [ &$validator, $id ]);

        $validatorRoles = new Validator();

        if ($isValid = $validator->isValid()) {
            $listRoles = implode(',', self::query()->from('role')->lists('role_id'));
            foreach ($validator->getInput('roles', []) as $key => $role) {
                $validatorRoles
                    ->addRule($key, 'int|inarray:' . $listRoles)
                    ->addLabel($key, t($role))
                    ->addInput($key, $key);
            }
        }
        $isValid &= $validatorRoles->isValid();

        if ($isValid) {
            $idRoles = array_keys($validator->getInput('roles', []));
            $values  = [
                'title'            => $validator->getInput('title'),
                'content'          => $validator->getInput('content'),
                'class'            => $validator->getInput('class'),
                'visibility_pages' => (bool) $validator->getInput('visibility_pages'),
                'pages'            => $validator->getInput('pages'),
                'visibility_roles' => (bool) $validator->getInput('visibility_roles'),
                'roles'            => implode(',', $idRoles)
            ];

            if ($block[ 'hook' ]) {
                $this->container->callHook("block.{$block[ 'hook' ]}.update.before", [
                    &$validator, &$values, $id
                ]);
            }
            $this->container->callHook('block.update.before', [
                $validator, &$values, $id
            ]);

            self::query()
                ->update('block', $values)
                ->where('block_id', '==', $id)
                ->execute();

            if ($block[ 'hook' ]) {
                $this->container->callHook("block.{$block[ 'hook' ]}.update.after", [
                    &$validator, $id
                ]);
            }
            $this->container->callHook('block.update.after', [ $validator, $id ]);

            return $this->show($id, $req);
        }

        $_SESSION[ 'inputs' ]      = $validator->getInputs();
        $_SESSION[ 'errors' ]      = $validator->getKeyErrors() + $validatorRoles->getKeyErrors();
        $_SESSION[ 'errors_keys' ] = $validator->getKeyInputErrors();

        return $this->edit($id, $req);
    }

    public function delete($id, $req)
    {
        if (!$this->find($id)) {
            return $this->get404($req);
        }

        $this->container->callHook('block.delete.before', [ $id ]);
        self::query()->from('block')->where('block_id', '==', $id)->delete()->execute();
        $this->container->callHook('block.delete.after', [ $id ]);
    }

    protected function find($id)
    {
        return self::query()->from('block')->where('block_id', '==', $id)->fetch();
    }

    protected function getOptions($block, $default = [])
    {
        return empty($block[ 'options' ])
                ? $default
                : json_decode($block[ 'options' ], true);
    }
}
