<?php namespace Utopigs\Epigtor\Components;

use Backend\Classes\Controller;
use BackendAuth;
use Cms\Classes\ComponentBase;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;
use Backend\Models\EditorSetting;
use Url;
use Utopigs\Banners\Models\Image;
use Utopigs\Epigtor\Traits\EpigtorImage;
use Utopigs\Epigtor\Traits\EpigtorLink;
use Utopigs\Epigtor\Traits\EpigtorPlain;
use Utopigs\Epigtor\Traits\EpigtorRicheditor;
use Utopigs\Linkable\Models\Link;

class Epigtor extends ComponentBase
{
    use EpigtorPlain;
    use EpigtorRicheditor;
    use EpigtorImage;
    use EpigtorLink;

    public $content;
    public $isEditor;
    public $message;
    public $model_class;
    public $model_id;
    public $type;
    public $csrf_token;
    public $refreshCode;
    public $uploadId;
    public $labelCreate;
    public $showDelete;
    public $labelDelete;
    public $labelDeleteConfirm;
    public $labelSave;
    public $labelCancel;
    public $cssClass;

    public function componentDetails()
    {
        return [
            'name' => 'utopigs.epigtor::lang.component_epigtor.name',
            'description' => 'utopigs.epigtor::lang.component_epigtor.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'message' => [
                'title' => 'utopigs.epigtor::lang.component_epigtor.property_message.title',
                'description' => 'utopigs.epigtor::lang.component_epigtor.property_message.description',
                'default' => ''
            ]
        ];
    }

    public function onRun()
    {
        $this->isEditor = $this->checkEditor();

        if ($this->isEditor) {

            $this->addCss('assets/vendor/redactor/redactor.css');
            $this->addJs('assets/vendor/redactor/redactor.js');

            $this->addJs('/modules/backend/assets/foundation/scripts/foundation/foundation.baseclass.js');
            $this->addJs('/modules/backend/assets/foundation/scripts/foundation/foundation.controlutils.js');

            $this->addCss('/modules/backend/formwidgets/richeditor/assets/css/base-styles.css');
            $this->addCss('/modules/backend/formwidgets/richeditor/assets/css/richeditor.css');
            $this->addJs('/modules/backend/formwidgets/richeditor/assets/js/build-min.js');
            $this->addJs('/modules/backend/formwidgets/richeditor/assets/js/richeditor.js');
            $this->addJs('/modules/backend/formwidgets/codeeditor/assets/js/build-min.js');

            $this->addJs('/modules/backend/assets/js/october/october.lang.js');
            $this->addJs('/modules/backend/assets/vendor/dropzone/dropzone.js');
            $this->addJs('/modules/backend/formwidgets/fileupload/assets/js/fileupload.js', 'core');

            // $froala_custom_defaults = Settings::get('froala_custom_defaults_file');
            // if ($froala_custom_defaults) {
            //     $this->addJs('/storage/app/media/utopigs_epigtor/'.$froala_custom_defaults);
            // }

            $this->paragraphFormats = EditorSetting::getConfiguredFormats('html_paragraph_formats') ? json_encode(EditorSetting::getConfiguredFormats('html_paragraph_formats')) : null;
            $this->globalToolbarButtons = str_replace(" ", "", EditorSetting::getConfigured('html_toolbar_buttons'));

            $this->addCss('assets/css/epigtor.css?v=3.0.2');
            $this->addJs('assets/js/epigtor-panel.js?v=3.0.2');
            $this->addJs('assets/js/epigtor.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-richeditor.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-image.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-link.js?v=3.0.2');

            $this->ace_vendor_path = Url::asset('/modules/backend/formwidgets/codeeditor/assets/vendor/ace');

            $this->csrf_token = csrf_token();
        }
    }

    public function onRender()
    {
        $this->isEditor = $this->checkEditor();
        $this->message = $this->property('message');
        $this->type = $this->property('type') ?: 'plain';
        $this->toolbarButtons = $this->property('toolbarButtons');
        $this->showDelete = $this->property('showDelete', false);
        $this->model_class = null;
        $this->model_id = null;
        $this->content = null;
        $this->cssClass = $this->property('cssClass');
        $propertyModel = $this->property('model');

        //reset properties for next component
        $this->setProperty('type', '');
        $this->setProperty('toolbarButtons', '');
        $this->setProperty('content', '');
        $this->setProperty('showDelete', false);
        $this->setProperty('model', '');
        $this->setProperty('cssClass', '');
        $this->setProperty('model', '');

        $content = null;
        
        if ($propertyModel) {
            /** @var stdClass $model */            
            $model = clone $propertyModel; // why clone?
            $message = $this->message;
            $content = $model->$message;
            $this->model_class = get_class($model);
            $this->model_id = $model->id;
        } else {
            if (in_array($this->type, ['plain', 'richeditor'])) {
                //TODO: check if message already exists in db, and if not, load default message from theme config files if it exists
                $content = Message::trans($this->message);
            }
            if ($this->type == 'image') {
                $image = Image::where('code', $this->message)->first();
                $content = $image->image ?? null;
            }
            if ($this->type == 'link') {
                $content = Link::where('code', $this->message)->first();
            }
        }

        if ($this->type == 'plain') {
            return $this->renderPlain($content);
        }

        if ($this->type == 'richeditor') {
            return $this->renderRicheditor($content);
        }

        if ($this->type == 'image') {
            return $this->renderImage($content);
        }

        if ($this->type == 'link') {
            return $this->renderLink($content);
        }
    }

    public function onSave()
    {
        if (!$this->checkEditor()) {
            return;
        }

        $locale = Translator::instance()->getLocale();

        $key = post('message');
        $content = post('content');

        if (post('type') == 'plain') {
            $breaks = array("<br />","<br>","<br/>");  
            $content = str_ireplace($breaks, "\r\n", $content);
        }

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

    public function checkEditor()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && ($backendUser->hasAccess('rainlab.translate.manage_messages'));
    }

}