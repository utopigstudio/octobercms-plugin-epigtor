<?php namespace Utopigs\Epigtor\Traits;

use Backend\Classes\Controller;
use Media\Widgets\MediaManager;

trait EpigtorRicheditor
{
    public $ace_vendor_path;
    public $toolbarButtons;
    public $globalToolbarButtons;
    public $paragraphFormats;

    private function renderRicheditor($content)
    {
        if (!$this->isEditor) {
            return $content;
        }

        if (!$content) {
            $content = "[empty]";
        }

        $this->content = $content;
    }

    public function onUpload()
    {
        $controller = new Controller;
        new MediaManager($controller, 'ocmediamanager');

        return $controller->makeResponse(null);
    }

}