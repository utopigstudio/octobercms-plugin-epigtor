<?php namespace Utopigs\Epigtor\Traits;

use Backend\Classes\Controller;
use Media\Widgets\MediaManager;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;

trait EpigtorRicheditor
{
    public $ace_vendor_path;
    public $toolbarButtons;
    public $globalToolbarButtons;
    public $paragraphFormats;

    private function getContentRicheditor()
    {
        return Message::trans($this->message);
    }

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

    public function onSaveRicheditor()
    {
        if (!$this->checkEditor()) {
            return;
        }

        $locale = Translator::instance()->getLocale();

        $key = post('message');
        $content = post('content');

        if (post('model')) {
            $modelClass = post('model')['model'];
            $model = $modelClass::findOrFail(post('model')['id']);
            $model->$key = $content;
            $model->save();
        } else {
            $messages = Message::where('locale', $locale)->first();
            $message = $messages->data[$key] ?? '';

            if ($content != $message) {
                $messages->updateMessage($locale, $key, $content);
                CacheHelper::clear();
            }
        }
    }

}