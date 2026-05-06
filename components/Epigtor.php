<?php namespace Utopigs\Epigtor\Components;

use BackendAuth;
use Cms\Classes\ComponentBase;
use Cms\Classes\PageManager;
use Cms\Models\PageLookupItem;
use Utopigs\Epigtor\Traits\EpigtorImage;
use Utopigs\Epigtor\Traits\EpigtorLink;
use Utopigs\Epigtor\Traits\EpigtorPlain;
use Utopigs\Epigtor\Traits\EpigtorRicheditor;

class Epigtor extends ComponentBase
{
    use EpigtorPlain;
    use EpigtorRicheditor;
    use EpigtorImage;
    use EpigtorLink;

    public $content;
    public $isEditor;
    public $contentIsEmpty;
    public $message;
    public $propertyModel;
    public $model_class;
    public $model_id;
    public $type;
    public $refreshCode;
    public $uploadId;
    public $labelCreate;
    public $showDelete;
    public $labelDelete;
    public $labelDeleteConfirm;
    public $labelSave;
    public $labelCancel;
    public $cssClass;
    public $isOldLink;
    public $isOldImage;

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

            $this->addCss('assets/css/epigtor.css?v=3.2.0');
            $this->addJs('assets/js/epigtor-panel.js?v=3.2.0');
            $this->addJs('assets/js/epigtor-plain.js?v=3.2.0');
            $this->addJs('assets/js/epigtor-richeditor.js?v=3.2.0');
            $this->addJs('assets/js/epigtor-image.js?v=3.2.0');
            $this->addJs('assets/js/epigtor-link.js?v=3.2.0');
        }
    }

    public function onRender()
    {
        $this->initData();

        $content = $this->getContent();
        
        $rendered = $this->renderContent($content);
        // For non-editors, return the rendered content directly
        // For editors, renderContent returns null and the default template is used
        if ($rendered !== null) {
            return $rendered;
        }
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
        $this->contentIsEmpty = false;
        $this->cssClass = $this->property('cssClass');

        $this->isOldLink = false;
        if ($this->type == 'link' && !$this->propertyModel) {
            $this->isOldLink = true;
        }

        $this->isOldImage = false;
        if ($this->type == 'image' && !$this->propertyModel) {
            $this->isOldImage = true;
        }

        // reset properties for next component
        // this is needed for multiple components on the same page,
        // otherwise if a component doesn't have a property set, it will use the last set property
        $this->setProperty('type', '');
        $this->setProperty('toolbarButtons', '');
        $this->setProperty('content', '');
        $this->setProperty('showDelete', false);
        $this->setProperty('model', '');
        $this->setProperty('cssClass', '');
    }

    private function getContent()
    {        
        if ($this->propertyModel) {
            /** @var stdClass $model */
            $model = clone $this->propertyModel;
            $message = $this->message;
            $content = $model->$message;
            if ($this->type == 'link' && is_string($content)) {
                $link = $message;
                $linkTitle = $message . '_title';
                $linkIsNewTab = $message . '_is_new_tab';
                $linkSchema = PageLookupItem::decodeSchema($model->$link);
                $content = [
                    'url' => $this->parseOctoberLink($model->$link),
                    'text' => $model->$linkTitle,
                    'is_new_tab' => $model->$linkIsNewTab,
                    'type' => $linkSchema['type'] ?? 'url',
                    'external_url' => $linkSchema['url'] ?? '',
                    'reference' => $linkSchema['reference'] ?? '',
                ];
            }
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
            if (!class_exists('\Utopigs\Banners\Models\Image')) {
                throw new \Exception('The plugin Utopigs.Banners is required to use the Epigtor image standalone feature. Please install and activate the plugin.');
            }
            return $this->getContentImage($this->message);
        }

        if ($this->type == 'link') {
            if (!class_exists('\Utopigs\Linkable\Models\Link')) {
                throw new \Exception('The plugin Utopigs.Linkable is required to use the Epigtor link standalone feature. Please install and activate the plugin.');
            }
            return $this->getContentLink($this->message);
        }

        throw new \Exception('Invalid Epigtor type: '.$this->type);
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

    protected function parseOctoberLink($value)
    {
        if (is_string($value) && str_starts_with($value, 'october://')) {
            $url = PageManager::url($value);
            return $url;
        }

        return $value;
    }

}
