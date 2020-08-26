<?php

namespace SoosyzeCore\Template\Services;

use Soosyze\Components\Util\Util;

class Templating extends \Soosyze\Components\Http\Response
{
    /**
     * @var Block
     */
    protected $template;

    /**
     * @var \Soosyze\Config
     */
    protected $config;

    /**
     * @var \Soosyze\App
     */
    protected $core;

    /**
     * Nom du theme utilisé par défaut.
     *
     * @var string
     */
    protected $defaultThemeName = '';

    /**
     * Chemin du thème.
     *
     * @var string
     */
    protected $defaultThemePath = '';

    /**
     * Liste des répertoires contenant les thèmes.
     *
     * @var string[]
     */
    protected $themesPath = [];

    /**
     * Les données du fichier composer.json
     *
     * @var array
     */
    protected $composer = [];
    
    protected $isDarkTheme = false;

    public function __construct($core, $config)
    {
        parent::__construct();

        $this->core       = $core;
        $this->config     = $config;
        $this->themesPath = $core->getSetting('themes_path');
        $this->basePath   = $core->getRequest()->getBasePath();
        $this->pathViews  = dirname(__DIR__) . '/Views/';
    }

    public function __toString()
    {
        $content    = $this->getThemplate()->render();
        $this->body = new \Soosyze\Components\Http\Stream($content);
        
        return parent::__toString();
    }

    public function init()
    {
        $this->loadComposer();
        $messages = $this->createBlock('messages.php', $this->pathViews)
            ->addVars([
            'errors'   => [],
            'warnings' => [],
            'infos'    => [],
            'success'  => []
        ]);

        $page = $this->createBlock('page.php', $this->pathViews)
            ->addVars([
                'title'      => '',
                'title_main' => '',
                'icon'       => '',
                'logo'       => ''
            ])
            ->addVars($this->core->getSettings())
            ->addBlock('content')
            ->addBlock('messages', $messages)
            ->addBlock('main_menu')
            ->addBlock('second_menu');

        if (!empty($this->composer[ 'extra' ][ 'soosyze-theme' ][ 'blocks' ])) {
            foreach ($this->composer[ 'extra' ][ 'soosyze-theme' ][ 'blocks' ] as $newBlock) {
                $page->addBlock($newBlock);
            }
        }

        $this->template = $this->createBlock('html.php', $this->pathViews)
            ->addBlock('page', $page)
            ->addVars([
                'dark'        => $this->isDarkTheme ? 'dark' : '',
                'title'       => '',
                'logo'        => '',
                'favicon'     => '',
                'description' => '',
                'keyboard'    => '',
                'meta'        => '',
                'styles'      => '',
                'scripts'     => ''
            ])
            ->addVars($this->core->getSettings());
    }

    public function getTheme($theme = 'theme')
    {
        $granted = $this->core->callHook('app.granted', [ 'template.admin' ]);

        if ($theme === 'theme_admin' && $granted) {
            $this->defaultThemeName = 'theme_admin';
            $this->isDarkTheme      = $this->config[ 'settings.theme_admin_dark' ];
        } else {
            $this->defaultThemeName = 'theme';
        }

        foreach ($this->themesPath as $path) {
            $dir = $path . '/' . $this->config->get('settings.' . $this->defaultThemeName, '');
            if (is_dir($dir)) {
                $this->defaultThemePath = $dir;

                break;
            }
        }
        $this->init();

        return $this;
    }

    public function isTheme($themeName)
    {
        return $this->defaultThemeName === $themeName;
    }

    /**
     * Ajoute des variables à la template courante ou à une sous template.
     *
     * @param string $parent
     * @param array  $vars
     *
     * @return $this
     */
    public function view($parent, array $vars)
    {
        $this->getBlock($parent)->addVars($vars);

        return $this;
    }

    /**
     * Ajoute un bloc à la template courante ou une sous template.
     * Ce bloc peut recevoir des variables fournit en dernier paramètre de la fonction.
     *
     * @param sring  $parent
     * @param string $tpl
     * @param string $tplPath
     * @param array  $vars
     *
     * @return $this
     */
    public function make($parent, $tpl, $tplPath, array $vars = [])
    {
        $template = $this->createBlock($tpl, $tplPath);

        return $this->addBlock($parent, $template, $vars);
    }

    public function addFilterVar($parent, $key, callable $function)
    {
        $this->getBlock($parent)->addFilterVar($key, $function);

        return $this;
    }

    public function addFilterBlock($parent, $key, callable $function)
    {
        $this->getBlock($parent)->addFilterBlock($key, $function);

        return $this;
    }

    public function addFilterOutput($parent, $key, callable $function)
    {
        $this->getBlock($parent)->addFilterOutput($key, $function);

        return $this;
    }

    public function override($parent, array $templates)
    {
        $this->getBlock($parent)->addNamesOverride($templates);

        return $this;
    }

    public function getBlock($parent)
    {
        return $this->getThemplate()->getBlockWithParent($parent);
    }

    public function getThemes()
    {
        $themes = [];
        foreach ($this->themesPath as $path) {
            foreach (new \DirectoryIterator($path) as $splFile) {
                $composer = $splFile->getRealPath() . '/composer.json';
                if (!file_exists($composer)) {
                    continue;
                }

                $themes[ $splFile->getBasename() ] = Util::getJson($composer);
            }
        }

        return $themes;
    }

    /**
     * @return Block
     */
    public function createBlock($tpl, $tplPath)
    {
        return (new Block($tpl, $tplPath))
                ->addVars([
                    'base_path'  => $this->basePath,
                    'base_theme' => $this->basePath . $this->defaultThemePath . '/'
                ])
                ->pathOverride($this->getPathTheme());
    }

    public function addBlock($parent, $template, array $vars = [])
    {
        if ($template !== null) {
            $template->addVars($vars);
        }

        if ($block = strstr($parent, '.', true)) {
            $this->getBlock($block)
                ->addBlock(substr(strstr($parent, '.'), 1), $template);
        } else {
            $this->getThemplate()->addBlock($parent, $template);
        }

        return $this;
    }

    public function getPathTheme()
    {
        return is_dir(ROOT . $this->defaultThemePath)
            ? ROOT . $this->defaultThemePath . '/'
            : $this->defaultThemePath;
    }

    public function getSections()
    {
        if (!$this->composer) {
            $this->loadComposer();
        }

        return !empty($this->composer[ 'extra' ][ 'soosyze-theme' ][ 'sections' ])
            ? $this->composer[ 'extra' ][ 'soosyze-theme' ][ 'sections' ]
            : [];
    }

    public function loadComposer()
    {
        $pathTheme = $this->getPathTheme();
        if (is_file($pathTheme . 'composer.json')) {
            $this->composer = Util::getJson($pathTheme . 'composer.json');
        }
    }

    private function getThemplate()
    {
        if ($this->template) {
            return $this->template;
        }
        $this->getTheme();

        return $this->template;
    }
}
