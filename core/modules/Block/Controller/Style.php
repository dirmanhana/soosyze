<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Block\Form\FormStyle;
use SoosyzeCore\Template\Services\Block as ServiceBlock;

class Style extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    /**
     * @return ServiceBlock|ResponseInterface
     */
    public function edit(
        string $theme,
        int $id,
        ServerRequestInterface $req
    ) {
        if (!$this->find($id)) {
            return $this->get404($req);
        }

        $themeName = $theme === 'admin'
            ? self::template()->getThemeAdminName()
            : self::template()->getThemePublicName();

        $values = self::config()->get("template-$themeName.block", [])[ $id ] ?? [];

        $this->container->callHook('block.style.edit.form.data', [
            &$values, $theme, $id
        ]);

        $action = self::router()->getRoute('block.style.update', [
            ':theme' => $theme,
            ':id'    => $id
        ]);

        $form = (new FormStyle([
                'action'  => $action,
                'class'   => 'form-api',
                'enctype' => 'multipart/form-data',
                'method'  => 'put'
                ], self::file())
            )
            ->setValues($values)
            ->makeFields();

        $this->container->callHook('block.style.edit.form', [
            &$form, $values, $theme, $id
        ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/modal-form.php', $this->pathViews)
                ->addVars([
                    'fieldset_submenu' => $this->getStyleFieldsetSubmenu(),
                    'form'             => $form,
                    'menu'             => self::block()->getBlockSubmenu('block.style.edit', $theme, $id),
                    'title'            => t('Block style')
        ]);
    }

    public function update(string $theme, int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($block = $this->find($id))) {
            return $this->json(404, [
                    'messages' => [ 'errors' => [ t('The requested resource does not exist.') ] ]
            ]);
        }

        $validator = $this->getValidator($req, $theme, $id);

        $this->container->callHook('block.style.update.validator', [
            &$validator, $theme, $id
        ]);

        if ($validator->isValid()) {
            $data = $this->getData($validator);

            $this->container->callHook('block.style.update.before', [
                $validator, &$data, $theme, $id
            ]);

            $themeName = $theme === 'admin'
                ? self::template()->getThemeAdminName()
                : self::template()->getThemePublicName();

            $config        = self::config()->get("template-$themeName.block", []) ?? [];
            $config[ $id ] = array_merge($config[ $id ] ?? [], $data);
            self::config()->set("template-$themeName.block", $config);

            $this->saveBackgroundImage($id, $validator);

            $this->container->callHook('block.style.update.after', [
                $validator, $data, $theme, $id
            ]);

            $this->generateStyleCssFile([$themeName]);

            return $this->json(200, [
                    'redirect' => self::router()->getRoute('block.section.admin', [
                        ':theme' => $theme
                    ])
            ]);
        }

        return $this->json(400, [
                'messages'    => [ 'errors' => $validator->getKeyErrors() ],
                'errors_keys' => $validator->getKeyInputErrors()
        ]);
    }

    public function style(): ResponseInterface
    {
        $themeAdminName  = self::template()->getThemeAdminName();
        $themePublicName = self::template()->getThemePublicName();

        $this->generateStyleCssFile([ $themeAdminName, $themePublicName ]);

        $_SESSION[ 'messages' ][ 'success' ][] = t('The style of the theme have been updated');

        return new Redirect(self::router()->getRoute('system.tool.admin'), 302);
    }

    private function getStyleFieldsetSubmenu(): ServiceBlock
    {
        $menu = [
            [
                'class'      => 'active',
                'icon'       => 'fas fa-palette',
                'link'       => '#color-fieldset',
                'title_link' => t('Colors')
            ], [
                'class'      => '',
                'icon'       => 'fas fa-image',
                'link'       => '#background_image-fieldset',
                'title_link' => t('Background')
            ], [
                'class'      => '',
                'icon'       => 'fas fa-paragraph',
                'link'       => '#font-fieldset',
                'title_link' => t('Font')
            ], [
                'class'      => '',
                'icon'       => 'fas fa-border-style',
                'link'       => '#border-fieldset',
                'title_link' => t('Border')
            ], [
                'class'      => '',
                'icon'       => 'fas fa-expand-arrows-alt',
                'link'       => '#margin-fieldset',
                'title_link' => t('Spacing')
            ]
        ];

        $this->container->callHook('block.style.fieldset.submenu', [ &$menu ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/submenu-block_fieldset.php', $this->pathViews)
                ->addVars([
                    'menu' => $menu
        ]);
    }

    private function find(int $id): array
    {
        return self::query()->from('block')->where('block_id', '=', $id)->fetch();
    }

    private function getValidator(
        ServerRequestInterface $req,
        string $theme,
        ?int $id = null
    ): Validator {
        $backgroundPosition = implode(',', array_keys(FormStyle::BACKGROUD_POSITION_TOP))
            . ',' . implode(',', array_keys(FormStyle::BACKGROUD_POSITION_CENTER))
            . ',' . implode(',', array_keys(FormStyle::BACKGROUD_POSITION_BOTTOM));

        $fonts = implode(',', array_keys(FormStyle::FONT_CURSIVE))
            . ',' . implode(',', array_keys(FormStyle::FONT_FANTASY))
            . ',' . implode(',', array_keys(FormStyle::FONT_MONOSPACE))
            . ',' . implode(',', array_keys(FormStyle::FONT_SANS_SERIF))
            . ',' . implode(',', array_keys(FormStyle::FONT_SERIF));

        $rules = [
            'background_image'    => '!required|image:jpeg,jpg,png|max:1Mb',
            'background_color'    => '!required|string',
            'background_position' => '!required|inarray:' . $backgroundPosition,
            'background_repeat'   => '!required|inarray:' . implode(',', array_keys(FormStyle::BACKGROUND_REPEAT)),
            'background_size'     => '!required|inarray:' . implode(',', FormStyle::BACKGROUND_SIZE),
            'border_color'        => '!required|string',
            'border_radius'       => '!required|numeric|min_numeric:0',
            'border_style'        => '!required|inarray:' . implode(',', FormStyle::BORDER_STYLE),
            'border_width'        => '!required|numeric|min_numeric:0',
            'color_link'          => '!required|string',
            'color_text'          => '!required|string',
            'color_title'         => '!required|string',
            'font_family_text'    => '!required|inarray:' . $fonts,
            'font_family_title'   => '!required|inarray:' . $fonts,
            'margin'              => '!required|numeric',
            'padding'             => '!required|numeric',
            'token_style'         => 'token'
        ];

        return (new Validator())
                ->setRules($rules)
                ->setLabels([
                    'background_image'    => t('Image'),
                    'background_color'    => t('Background color'),
                    'background_position' => t('Position'),
                    'background_repeat'   => t('Repeat'),
                    'background_size'     => t('Size'),
                    'border_color'        => t('Border color'),
                    'border_radius'       => t('Rounding of angles'),
                    'border_style'        => t('Border style'),
                    'border_width'        => t('Border width'),
                    'color_link'          => t('Link color'),
                    'color_text'          => t('Text color'),
                    'color_title'         => t('Title color'),
                    'font_family_text'    => t('Text font'),
                    'font_family_title'   => t('Title font'),
                    'margin'              => t('Marging'),
                    'padding'             => t('Padding')
                ])
                ->setInputs(
                    $req->getParsedBody() + $req->getUploadedFiles()
                )
        ;
    }

    private function getData(Validator $validator): array
    {
        return [
            'background_color'    => $validator->getInput('background_color'),
            'background_position' => $validator->getInput('background_position'),
            'background_repeat'   => $validator->getInput('background_repeat'),
            'background_size'     => $validator->getInput('background_size'),
            'border_color'        => $validator->getInput('border_color'),
            'border_radius'       => (int) $validator->getInput('border_radius'),
            'border_style'        => $validator->getInput('border_style'),
            'border_width'        => (int) $validator->getInput('border_width'),
            'color_link'          => $validator->getInput('color_link'),
            'color_text'          => $validator->getInput('color_text'),
            'color_title'         => $validator->getInput('color_title'),
            'font_family_text'    => $validator->getInput('font_family_text'),
            'font_family_title'   => $validator->getInput('font_family_title'),
            'margin'              => (int) $validator->getInput('margin'),
            'padding'             => (int) $validator->getInput('padding'),
        ];
    }

    private function generateStyleCssFile(array $themesName): void
    {
        $vendor = self::core()->getDir('assets_public', 'public/vendor', false);

        foreach ($themesName as $themeName) {
            $styles    = self::config()->get("template-$themeName", []) ?? [];
            $stylesCss = '';

            foreach ($styles as $selector => $style) {
                $stylesCss .= $this->getStyleCss($selector, $style);
            }

            $handle = fopen("$vendor/$themeName.css", 'w+');
            fwrite($handle, $stylesCss);
            fclose($handle);
        }
    }

    private function getStyleCss(string $selector, array $templateConfig): string
    {
        $style = '';
        foreach ($templateConfig as $keyBlock => $block) {
            /* Style du bloc. */
            $css ='';
            if (!empty($block[ 'border_style' ]) ||
                !empty($block[ 'border_width' ]) ||
                !empty($block[ 'border_color' ])) {
                $css = sprintf(
                    'border:%s%s%s;',
                    empty($block[ 'border_style' ]) ? '' : $block[ 'border_style' ] . ' ',
                    empty($block[ 'border_width' ]) ? '' : $block[ 'border_width' ] . 'px ',
                    empty($block[ 'border_color' ]) ? '' : $block[ 'border_color' ] . ' '
                );
            }
            if (!empty($block[ 'border_radius' ])) {
                $css .= sprintf('border-radius:%spx;', $block[ 'border_radius' ]);
            }
            if (!empty($block[ 'color_text' ])) {
                $css .= sprintf('color:%s;', $block[ 'color_text' ]);
            }
            if (!empty($block[ 'font_family_text' ])) {
                $css .= sprintf('font-family:"%s";', $block[ 'font_family_text' ]);
            }
            if (!empty($block[ 'background_image' ]) &&
                (!empty($block[ 'background_position' ]) || !empty($block[ 'background_repeat' ]))) {
                $css .= sprintf(
                    'background:%s %s;',
                    $block[ 'background_position' ] ?? '',
                    $block[ 'background_repeat' ] ?? ''
                );
            }
            if (!empty($block[ 'background_size' ])) {
                $css .= sprintf('background-size:%s;', $block[ 'background_size' ]);
            }
            if (!empty($block[ 'background_image' ])) {
                $css .= sprintf('background-image: url(\'%s\');', self::router()->getBasePath() . $block[ 'background_image' ]);
            }
            if (!empty($block[ 'background_color' ])) {
                $css .= sprintf('background-color:%s;', $block[ 'background_color' ]);
            }
            if (!empty($block[ 'margin' ])) {
                $css .= sprintf('margin:%spx;', $block[ 'margin' ]);
            }
            if (!empty($block[ 'padding' ])) {
                $css .= sprintf('padding:%spx;', $block[ 'padding' ]);
            }
            if (!empty($css)) {
                $style .= sprintf('#block-%s{%s}', $keyBlock, $css) . PHP_EOL;
            }

            /* Style des titres. */
            $css = '';
            if (!empty($block[ 'font_family_title' ])) {
                $css .= sprintf('font-family:"%s";', $block[ 'font_family_title' ]);
            }
            if (!empty($block[ 'color_title' ])) {
                $css .= sprintf('color:%s;', $block[ 'color_title' ]);
            }
            if (!empty($css)) {
                $style .= str_replace(
                    [ ':key', ':styles' ],
                    [ $keyBlock, $css ],
                    '#block-:key h2, #block-:key h3, #block-:key h4, #block-:key h5, #block-:key h6{:styles}'
                ) . PHP_EOL;
            }

            /* Style des liens. */
            $css = '';
            if (!empty($block[ 'color_link' ])) {
                $css .= sprintf('color:%s;', $block[ 'color_link' ]);
            }
            if (!empty($css)) {
                $style .= sprintf('#block-%s a{%s}', $keyBlock, $css) . PHP_EOL;
            }
        }

        return $style;
    }

    private function saveBackgroundImage(int $id, Validator $validator): void
    {
        $key = 'background_image';

        self::file()
            ->add($validator->getInput($key), $validator->getInput("file-$key-name"))
            ->setName($key . $id)
            ->setPath('/block')
            ->isResolvePath()
            ->callGet(function ($name, $namefile) use ($id, $key): string {
                $data = self::config()->get('template.' . $id);

                return $data[ $key ] ?? '';
            })
            ->callMove(function ($name, $namefile, $move) use ($id, $key): void {
                $data         = self::config()->get('template.' . $id);
                $data[ $key ] = $move;
                self::config()->set('template.' . $id, $data);
            })
            ->callDelete(function ($name, $namefile) use ($id, $key): void {
                $data         = self::config()->get('template.' . $id);
                $data[ $key ] = '';
                self::config()->set('template.' . $id, $data);
            })
            ->save();
    }
}
