<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Hook;

use Soosyze\Components\Router\Router;

class Tool
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function hookToolAction(array &$actions)
    {
        $actions[] = [
            'icon'       => 'fa fa-paint-brush',
            'request'    => $this->router->getRequestByRoute('block.tool.style'),
            'title_link' => 'Regenerate the theme style'
        ];
    }
}
