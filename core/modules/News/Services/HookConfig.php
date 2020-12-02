<?php

namespace SoosyzeCore\News\Services;

class HookConfig implements \SoosyzeCore\Config\Services\ConfigInterface
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function defaultValues()
    {
        return [
            'new_default_icon'  => '',
            'new_default_image' => '',
            'news_pagination'   => 6
        ];
    }
    
    public function menu(&$menu)
    {
        $menu[ 'news' ] = [
            'title_link' => 'News'
        ];
    }

    public function form(&$form, $data, $req)
    {
        $form->group('news_pagination-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('news_pagination-legend', t('Settings'))
                ->group('news_pagination-group', 'div', function ($form) use ($data) {
                    $form->label('news_pagination-group', t('Number of articles per page'), [
                        'for'      => 'news_pagination',
                        'required' => 1
                    ])
                    ->group('news_pagination-flex', 'div', function ($form) use ($data) {
                        $form->number('news_pagination', [
                            ':actions' => 1,
                            'class'    => 'form-control',
                            'max'      => 50,
                            'min'      => 1,
                            'required' => 1,
                            'value'    => $data[ 'news_pagination' ]
                        ]);
                    }, [ 'class' => 'form-group-flex' ]);
                }, [ 'class' => 'form-group' ]);
        })
            ->group('new_default_image-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('new_default_image-legend', t('Default image'))
                ->group('new_default_image-group', 'div', function ($form) use ($data) {
                    $form->label('new_default_image-label', t('Default image'), [
                        'class'        => 'control-label',
                        'data-tooltip' => '200ko maximum.',
                        'for'          => 'new_default_image'
                    ]);
                    $this->file->inputFile('new_default_image', $form, $data[ 'new_default_image' ]);
                }, [ 'class' => 'form-group' ])
                ->group('new_default_icon-group', 'div', function ($form) use ($data) {
                    $form->label('new_default_icon-group', t('Default icon'), [
                        'data-tooltip' => t('Icon Font Awesome if there is no default image'),
                        'for'      => 'new_default_icon',
                        'required' => 1,
                    ])
                    ->group('new_default_icon-flex', 'div', function ($form) use ($data) {
                        $form->text('new_default_icon', [
                            'class'    => 'form-control',
                            'required' => 1,
                            'value'    => $data[ 'new_default_icon' ]
                        ])
                        ->html('new_default_icon-btn', '<button:attr>:content</button>', [
                            ':content'      => '<i class="' . $data[ 'new_default_icon' ] . '" aria-hidden="true"></i>',
                            'aria-label'    => t('Rendering'),
                            'class'         => 'btn render_icon',
                            'type'          => 'button',
                            'data-tooltip'  => t('Rendering')
                        ]);
                    }, [ 'class' => 'form-group-flex' ]);
                }, [ 'class' => 'form-group' ]);
            });
    }

    public function validator(&$validator)
    {
        $validator->setRules([
            'new_default_icon'  => 'required|string|fontawesome:solid,brands',
            'new_default_image' => '!required|image|max:200Kb',
            'news_pagination'   => 'required|between_numeric:1,50'
        ])->setLabels([
            'new_default_icon'  => t('Icone par défaut'),
            'new_default_image' => t('Image par défaut'),
            'news_pagination'   => t('Number of articles per page')
        ]);
    }

    public function before(&$validator, &$data, $id)
    {
        $data = [
            'new_default_icon' => $validator->getInput('new_default_icon'),
            'news_pagination'  => (int) $validator->getInput('news_pagination')
        ];
    }

    public function after(&$validator, $data, $id)
    {
    }

    public function files(&$inputsFile)
    {
        $inputsFile = [ 'new_default_image' ];
    }
}
