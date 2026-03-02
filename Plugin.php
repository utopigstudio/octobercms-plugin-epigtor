<?php namespace Utopigs\Epigtor;

use System\Classes\PluginBase;
use Utopigs\Epigtor\Models\Settings;
use Event;

class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = [
        'RainLab.Translate',
    ];

    public function pluginDetails()
    {
        return [
            'name' => 'utopigs.epigtor::lang.plugin.name',
            'description' => 'utopigs.epigtor::lang.plugin.description',
            'author' => 'Utopig Studio',
            'icon' => 'icon-pencil'
        ];
    }

    public function registerComponents()
    {
        return [
            'Utopigs\Epigtor\Components\Epigtor' => 'editme',
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'utopigs.epigtor::lang.settings.name',
                'description' => 'utopigs.epigtor::lang.settings.description',
                'category'    => \System\Classes\SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-code',
                'class'       => 'Utopigs\Epigtor\Models\Settings',
                'permissions' => ['backend.manage_editor'],
                'order'       => 600,
            ]
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        $froala_custom_defaults = Settings::get('froala_custom_defaults_file');
        if ($froala_custom_defaults) {
            \Backend\Classes\Controller::extend(function ($controller) use ($froala_custom_defaults) {
                $controller->addJs('/storage/app/media/utopigs_epigtor/'.$froala_custom_defaults);
            });
        }

        Event::listen('cms.content.postProcessMarkup', function(&$markup) {
            if (empty($markup)) return $markup;
            $markup = preg_replace('/<img(?![^>]*\balt=)([^>]*)>/i', '<img alt="" $1>', $markup);
        });
    }

}
