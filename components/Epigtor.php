<?php namespace Utopigs\Epigtor\Components;

use Backend\Models\EditorSetting;
use BackendAuth;
use Cms\Classes\ComponentBase;
use RainLab\Translate\Models\Message;
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
    public $propertyModel;
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
            // plain editor assets
            $this->addCss('assets/vendor/redactor/redactor.css');
            $this->addJs('assets/vendor/redactor/redactor.js');

            // richeditor assets
            $this->addJs('/modules/backend/assets/foundation/scripts/foundation/foundation.baseclass.js');
            $this->addJs('/modules/backend/assets/foundation/scripts/foundation/foundation.controlutils.js');
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
            $globalToolbarButtons = str_replace(" ", "", EditorSetting::getConfigured('html_toolbar_buttons'));
            // if one of the buttons is insertPageLink, we change it to insertLink because epigtor doesn't support insertPageLink
            if (strpos($globalToolbarButtons, 'insertPageLink') !== false) {
                if (strpos($globalToolbarButtons, 'insertLink') === false) {
                    $globalToolbarButtons = str_replace('insertPageLink', 'insertLink', $globalToolbarButtons);
                } else {
                    $globalToolbarButtons = str_replace('insertPageLink', '', $globalToolbarButtons);
                    $globalToolbarButtons = str_replace(',,', ',', $globalToolbarButtons);
                }
            }
            $this->globalToolbarButtons = $globalToolbarButtons;

            $this->addCss('assets/css/epigtor.css?v=3.0.2');
            $this->addJs('assets/js/epigtor-panel.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-plain.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-richeditor.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-image.js?v=3.0.2');
            $this->addJs('assets/js/epigtor-link.js?v=3.0.2');

            $this->ace_vendor_path = Url::asset('/modules/backend/formwidgets/codeeditor/assets/vendor/ace');

            $this->csrf_token = csrf_token();
        }
    }

    public function onRender()
    {
        $this->initData();

        $content = $this->getContent();
        
        return $this->renderContent($content);
    }

    private function initData()
    {
        $this->isEditor = $this->checkEditor();
        $this->message = $this->property('message');
        $this->type = $this->property('type') ?: 'plain';
        $this->toolbarButtons = $this->property('toolbarButtons');
        $this->showDelete = $this->property('showDelete', false);
        $this->propertyModel = $this->property('model');
        $this->model_class = null;
        $this->model_id = null;
        $this->content = null;
        $this->cssClass = $this->property('cssClass');

        // reset properties for next component
        // this is needed for multiple components on the same page,
        // otherwise if a component doesn't have a property set, it will use the last set property
        $this->setProperty('type', '');
        $this->setProperty('toolbarButtons', '');
        $this->setProperty('content', '');
        $this->setProperty('showDelete', false);
        $this->setProperty('model', '');
        $this->setProperty('cssClass', '');
        $this->setProperty('model', '');
    }

    private function getContent()
    {        
        if ($this->propertyModel) {
            /** @var stdClass $model */            
            $model = clone $this->propertyModel; // why clone?
            $message = $this->message;
            $content = $model->$message;
            $this->model_class = get_class($model);
            $this->model_id = $model->id;

            return $content;
        }

        if ($this->type == 'plain') {
            return $this->getContentPlain($this->message);
        }

        if ($this->type == 'richeditor') {
            return $this->getContentRicheditor($this->message);
        }

        if ($this->type == 'image') {
            return $this->getContentImage($this->message);
        }

        if ($this->type == 'link') {
            return $this->getContentLink($this->message);
        }

        return null;
    }

    private function renderContent($content)
    {
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

    private function checkEditor()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && ($backendUser->hasAccess('rainlab.translate.manage_messages'));
    }

}