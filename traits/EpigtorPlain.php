<?php namespace Utopigs\Epigtor\Traits;

use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;

trait EpigtorPlain
{
    private function getContentPlain()
    {
        return Message::trans($this->message);
    }

    private function renderPlain($content)
    {
        //convert nl2br
        $content = nl2br($content);
        //replace paragraphs with break lines
        $content = str_replace(array('<p>','</p>'),array('','<br />'), $content);
        //remove all html tags except break lines
        $content = strip_tags($content, '<br>');
        //remove EOL
        $content = preg_replace( "/\r|\n/", "", $content);
        //remove excess <br> or <br /> from the end of the text
        $content = preg_replace('#(( ){0,}<br( {0,})(/{0,1})>){1,}$#i', '', $content);

        if (!$this->isEditor) {
            return $content;
        }

        if (!$content) {
            $content = "[empty]";
        }

        $this->content = $content;
    }

    public function onSavePlain()
    {
        if (!$this->checkEditor()) {
            return;
        }

        $locale = Translator::instance()->getLocale();

        $key = post('message');
        $content = post('content');

        $breaks = array("<br />","<br>","<br/>");  
        $content = str_ireplace($breaks, "\r\n", $content);

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