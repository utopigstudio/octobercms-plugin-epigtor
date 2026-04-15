<?php namespace Utopigs\Epigtor\Traits;

use Backend;
use Illuminate\Support\Facades\Crypt;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;

trait EpigtorRicheditor
{
    public $instanceId;
    public $richeditorPopupUrl;
    public $toolbarButtons;

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
        } else {
            $html = $content;
            $content = preg_replace('/<img(?![^>]*\balt=)([^>]*)>/i', '<img alt="" $1>', $html);
        }

        $this->content = $content;
        $this->instanceId = $this->makeRicheditorInstanceId();
        $this->richeditorPopupUrl = $this->makeRicheditorPopupUrl();
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

    protected function makeRicheditorPopupUrl(): string
    {
        $payload = [
            'instance_id' => $this->instanceId,
            'message' => $this->message,
            'model_class' => $this->model_class,
            'model_id' => $this->model_id,
            'toolbar_buttons' => $this->toolbarButtons,
            'locale' => Translator::instance()->getLocale(),
        ];

        return Backend::url('utopigs/epigtor/richeditor') . '?payload=' . urlencode(Crypt::encryptString(json_encode($payload)));
    }

    protected function makeRicheditorInstanceId(): string
    {
        return md5(implode('|', [
            $this->alias,
            $this->message,
            $this->model_class,
            $this->model_id,
            uniqid('', true),
        ]));
    }

}