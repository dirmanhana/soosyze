<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Form;

use SoosyzeCore\FileSystem\Services\File;

class FormStyle extends \Soosyze\Components\Form\FormBuilder
{
    public const BACKGROUND_REPEAT = [
        'repeat'    => 'On both axes',
        'repeat-x'  => 'On horizontal axis',
        'repeat-y'  => 'On vertical axis',
        'no-repeat' => 'Do not repeat',
    ];

    public const BACKGROUD_POSITION_TOP = [
        'top left'   => 'Top left',
        'top center' => 'Top center',
        'top right'  => 'Top right',
    ];

    public const BACKGROUD_POSITION_CENTER = [
        'center left'   => 'Middle left',
        'center center' => 'Middle center',
        'center right'  => 'Middle right',
    ];

    public const BACKGROUD_POSITION_BOTTOM = [
        'bottom left'   => 'Bottom left',
        'bottom center' => 'Bottom center',
        'bottom right'  => 'Bottom right',
    ];

    public const BORDER_STYLE = [
        'dashed',
        'double',
        'dotted',
        'groove',
        'hidden',
        'inset',
        'none',
        'outset',
        'ridge',
        'solid',
    ];

    public const BACKGROUND_SIZE = [
        '100%',
        'auto',
        'contain',
        'cover',
    ];

    public const FONT_SANS_SERIF = [
        'Arial',
        'Calibri',
        'Helvetica',
        'Lucida Sans',
        'Open Sans',
        'Verdana',
    ];

    public const FONT_SERIF = [
        'DejaVu Serif',
        'FreeSerif',
        'Georgia',
        'Liberation Serif',
        'Norasi',
        'Times New Roman',
        'Times',
    ];

    public const FONT_FANTASY = [
        'Herculanum',
        'Impact',
        'Papyrus',
    ];

    public const FONT_CURSIVE = [
        'Lucida Calligraphy'
    ];

    public const FONT_MONOSPACE = [
        'Consolas',
        'DejaVu Sans Mono',
        'Lucida Console',
    ];

    private const FONTS = [
        [ 'label' => 'Sans-serif', 'value' => self::FONT_SANS_SERIF ],
        [ 'label' => 'serif', 'value' => self::FONT_SERIF ],
        [ 'label' => 'Fantasy', 'value' => self::FONT_FANTASY ],
        [ 'label' => 'Cursive', 'value' => self::FONT_CURSIVE ],
        [ 'label' => 'Monospace', 'value' => self::FONT_MONOSPACE ],
    ];

    private const BACKGROUD_POSITIONS = [
        [ 'label' => 'Top', 'value' => self::BACKGROUD_POSITION_TOP ],
        [ 'label' => 'Middle', 'value' => self::BACKGROUD_POSITION_CENTER ],
        [ 'label' => 'Bottom', 'value' => self::BACKGROUD_POSITION_BOTTOM ],
    ];

    /**
     * @var array
     */
    protected $values = [
        'background_image'    => '',
        'background_color'    => '',
        'background_position' => '',
        'background_repeat'   => '',
        'background_size'     => '',
        'block_id'            => null,
        'border_color'        => '',
        'border_radius'       => 0,
        'border_style'        => '',
        'border_width'        => 0,
        'color_link'          => '',
        'color_text'          => '',
        'color_title'         => '',
        'font_family_text'    => '',
        'font_family_title'   => '',
        'margin'              => '',
        'padding'             => ''
    ];

    /**
     * @var array
     */
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var File|null
     */
    private $file;

    public function __construct(array $attr, ?File $file = null)
    {
        parent::__construct($attr + [ 'class' => 'form-api' ]);
        $this->file = $file;
    }

    public function makeFields(): self
    {
        $this->group('color-fieldset', 'fieldset', function ($form) {
            $form->legend('color-legend', t('Colors'))
                ->group('color_title-group', 'div', function ($form) {
                    $form->label('color_title-label', t('Title color'))
                    ->text('color_title', [
                        'class' => 'form-control color-picker',
                        'value' => $this->values[ 'color_title' ]
                    ]);
                }, self::$attrGrp)
                ->group('color_text-group', 'div', function ($form) {
                    $form->label('color_text-label', t('Text color'))
                    ->text('color_text', [
                        'class' => 'form-control color-picker',
                        'value' => $this->values[ 'color_text' ]
                    ]);
                }, self::$attrGrp)
                ->group('color_link-group', 'div', function ($form) {
                    $form->label('color_link-label', t('Link color'))
                    ->text('color_link', [
                        'class' => 'form-control color-picker',
                        'value' => $this->values[ 'color_link' ]
                    ]);
                }, self::$attrGrp);
        }, [
                'class' => 'tab-pane fade active',
                'id'    => 'color-fieldset'
            ])
            ->group('background_image-fieldset', 'fieldset', function ($form) {
                $form->legend('background_image-legend', t('Background'))
                ->group('background_color-group', 'div', function ($form) {
                    $form->label('background_color-label', t('Background color'))
                    ->text('background_color', [
                        'class' => 'form-control color-picker',
                        'value' => $this->values[ 'background_color' ]
                    ]);
                }, self::$attrGrp)
                ->group('background_image-group', 'div', function ($form) {
                    $form->label('background_image-label', t('Image'), [ 'for' => 'background_image' ]);
                    $this->file->inputFile('background_image', $form, $this->values[ 'background_image' ]);
                }, self::$attrGrp)
                ->group('background_repeat-group', 'div', function ($form) {
                    $form->label('background_repeat-label', t('Repeat'))
                    ->select('background_repeat', $this->getOptionsRepeat(), [
                        ':selected' => $this->values[ 'background_repeat' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp)
                ->group('background_position-group', 'div', function ($form) {
                    $form->label('background_position-label', t('Position'))
                    ->select('background_position', $this->getOptionsPosition(), [
                        ':selected' => $this->values[ 'background_position' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp)
                ->group('background_size-group', 'div', function ($form) {
                    $form->label('background_size-label', t('Size'))
                    ->select('background_size', $this->getOptionsBackgroundSize(), [
                        ':selected' => $this->values[ 'background_size' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp);
            }, [
                'class' => 'tab-pane fade',
                'id'    => 'background_image-fieldset'
            ])
            ->group('font-fieldset', 'fieldset', function ($form) {
                $form->legend('font-legend', t('Font'))
                ->group('font_family_text-group', 'div', function ($form) {
                    $form->label('font_family_text-label', t('Text font'))
                    ->select('font_family_text', $this->getOptionsFont(), [
                        ':selected' => $this->values[ 'font_family_text' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp)
                ->group('font_family_title-group', 'div', function ($form) {
                    $form->label('font_family_title-label', t('Title font'))
                    ->select('font_family_title', $this->getOptionsFont(), [
                        ':selected' => $this->values[ 'font_family_title' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp);
            }, [
                'class' => 'tab-pane fade',
                'id'    => 'font-fieldset'
            ])
            ->group('border-fieldset', 'fieldset', function ($form) {
                $form->legend('border-legend', t('Border'))
                ->group('border_style-group', 'div', function ($form) {
                    $form->label('border_style-label', t('Border style'))
                    ->select('border_style', $this->getOptionsStyleBorder(), [
                        ':selected' => $this->values[ 'border_style' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp)
                ->group('border_width-group', 'div', function ($form) {
                    $form->label('border_width-label', t('Border width'), [
                        'data-tooltip' => t('Size expressed pixels')
                    ])
                    ->number('border_width', [
                        'class'    => 'form-control',
                        'min'      => 0,
                        'value'    => $this->values[ 'border_width' ],
                    ]);
                }, self::$attrGrp)
                ->group('border_color-group', 'div', function ($form) {
                    $form->label('border_color-label', t('Border color'))
                    ->text('image', [
                        'class' => 'form-control color-picker',
                        'value' => $this->values[ 'border_color' ]
                    ]);
                }, self::$attrGrp)
                ->group('border_radius-group', 'div', function ($form) {
                    $form->label('border_radius-label', t('Rounding of angles'), [
                        'data-tooltip' => t('Size expressed pixels')
                    ])
                    ->number('border_radius', [
                        'class'    => 'form-control',
                        'min'      => 0,
                        'value'    => $this->values[ 'border_radius' ],
                    ]);
                }, self::$attrGrp);
            }, [
                'class' => 'tab-pane fade',
                'id'    => 'border-fieldset'
            ])
            ->group('margin-fieldset', 'fieldset', function ($form) {
                $form->legend('margin-legend', t('Spacing'))
                ->group('margin-group', 'div', function ($form) {
                    $form->label('margin-label', t('Marging'), [
                        'data-tooltip' => t('Size expressed pixels')
                    ])
                    ->number('margin', [
                        'class'    => 'form-control',
                        'value'    => $this->values[ 'margin' ],
                    ]);
                }, self::$attrGrp)
                ->group('padding-group', 'div', function ($form) {
                    $form->label('padding-label', t('Padding'), [
                        'data-tooltip' => t('Size expressed pixels')
                    ])
                    ->number('padding', [
                        'class'    => 'form-control',
                        'value'    => $this->values[ 'padding' ],
                    ]);
                }, self::$attrGrp);
            }, [
                'class' => 'tab-pane fade',
                'id'    => 'margin-fieldset'
            ])
            ->group('submit-group', 'div', function ($form) {
                $form->token('token_style')
                ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ]);
            });

        return $this;
    }

    private function getOptionsRepeat(): array
    {
        $options[] = [ 'label' => t('-- Select --'), 'value' => '' ];
        foreach (self::BACKGROUND_REPEAT as $value => $label) {
            $options[] = [ 'label' => t($label), 'value' => $value ];
        }

        return $options;
    }

    private function getOptionsStyleBorder(): array
    {
        $options[] = [ 'label' => t('-- Select --'), 'value' => '' ];
        foreach (self::BORDER_STYLE as $style) {
            $options[] = [ 'label' => $style, 'value' => $style ];
        }

        return $options;
    }

    private function getOptionsPosition(): array
    {
        $options[ 0 ] = [ 'label' => t('-- Select --'), 'value' => '' ];
        foreach (self::BACKGROUD_POSITIONS as $key => $position) {
            $options[ $key + 1 ][ 'label' ] = t($position[ 'label' ]);
            foreach ($position[ 'value' ] as $value => $label) {
                $options[ $key + 1 ][ 'value' ][] = [ 'label' => t($label), 'value' => $value ];
            }
        }

        return $options;
    }

    private function getOptionsBackgroundSize(): array
    {
        $options[ 0 ] = [ 'label' => t('-- Select --'), 'value' => '' ];
        foreach (self::BACKGROUND_SIZE as $size) {
            $options[] = [ 'label' => $size, 'value' => $size ];
        }

        return $options;
    }

    private function getOptionsFont(): array
    {
        $options[ 0 ] = [ 'label' => t('-- Select --'), 'value' => '' ];
        foreach (self::FONTS as $key => $font) {
            $options[ $key + 1 ][ 'label' ] = $font[ 'label' ];
            foreach ($font[ 'value' ] as $value) {
                $options[ $key + 1 ][ 'value' ][] = [
                    'label' => $value,
                    'value' => $value,
                    'attr'  => [ 'style' => "font-family:'$value'" ]
                ];
            }
        }

        return $options;
    }
}
