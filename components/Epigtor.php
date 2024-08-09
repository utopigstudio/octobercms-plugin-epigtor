<?php namespace Utopigs\Epigtor\Components;

use Backend\Classes\Controller;
use BackendAuth;
use Cms\Classes\ComponentBase;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;
use Media\Widgets\MediaManager;
use Backend\Models\EditorSetting;
use Utopigs\Epigtor\Models\Settings;
use Url;
use Input;
use ApplicationException;
use Lang;
use Response;
use System\Models\File;
use Utopigs\Banners\Models\Image;
use Utopigs\Linkable\Models\Link;

class Epigtor extends ComponentBase
{
    public $content;
    public $isEditor;
    public $message;
    public $model_class;
    public $model_id;
    public $ace_vendor_path;
    public $type;
    public $toolbarButtons;
    public $paragraphFormats;
    public $csrf_token;
    public $imagePartial;
    public $imageEmptyPartial;
    public $refreshCode;
    public $uploadId;
    public $linkPartial;
    public $linkEmptyPartial;
    public $labelCreate;
    public $showDelete;
    public $labelDelete;
    public $labelDeleteConfirm;
    public $labelSave;
    public $labelCancel;
    public $labelUpload;
    public $labelReplace;
    public $labelImageTitle;
    public $labelLinkText;
    public $labelLinkType;
    public $labelLinkUrl;
    public $labelLinkReference;
    public $labelLinkIsNewTab;
    public $cssClass;

    /**
     * @var int imageWidth for preview
     */
    public $imageWidth = 190;

    /**
     * @var int imageHeight for preview
     */
    public $imageHeight = 190;

    /**
     * @var array thumbOptions used for generating thumbnails
     */
    public $thumbOptions = [
        'mode'      => 'crop',
        'extension' => 'auto'
    ];

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

            $this->addJs('assets/vendor/oc2/foundation.baseclass.js');
            $this->addJs('assets/vendor/oc2/foundation.controlutils.js');
            $this->addCss('assets/vendor/oc2/richeditor/assets/css/richeditor.css', 'core');
            $this->addJs('assets/vendor/oc2/richeditor/assets/js/build-min.js', 'core');
            $this->addJs('assets/vendor/oc2/richeditor/assets/js/build-plugins-min.js', 'core');
            $this->addJs('assets/vendor/oc2/codeeditor/assets/js/build-min.js', 'core');

            $this->addJs('/modules/backend/assets/js/october/october.lang.js');
            $this->addJs('/modules/backend/assets/vendor/dropzone/dropzone.js');
            $this->addJs('/modules/backend/formwidgets/fileupload/assets/js/fileupload.js', 'core');

            // $this->addJs('/modules/system/assets/ui/js/select.js');

            // $froala_custom_defaults = Settings::get('froala_custom_defaults_file');
            // if ($froala_custom_defaults) {
            //     $this->addJs('/storage/app/media/utopigs_epigtor/'.$froala_custom_defaults);
            // }

            $this->paragraphFormats = EditorSetting::getConfiguredFormats('html_paragraph_formats') ? json_encode(EditorSetting::getConfiguredFormats('html_paragraph_formats')) : null;

            $this->addCss('assets/css/epigtor.css?v=3.0.1');
            $this->addJs('assets/js/epigtor-panel.js?v=3.0.1');
            $this->addJs('assets/js/epigtor.js?v=3.0.1');
            $this->addJs('assets/js/epigtor-image.js?v=3.0.1');
            $this->addJs('assets/js/epigtor-link.js?v=3.0.1');

            $this->ace_vendor_path = Url::asset('/plugins/utopigs/epigtor/assets/vendor/oc2/codeeditor/assets/vendor/ace');

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
        $this->model_class = NULL;
        $this->model_id = NULL;
        $this->content = NULL;
        $this->cssClass = $this->property('cssClass');

        if ($this->property('model')) {
            $model = clone $this->property('model');
            $message = $this->message;
            $content = $model->$message;
        } else {
            if (!in_array($this->type, ['image', 'link'])) {
                //TODO: check if message already exists in db, and if not, load default message from theme config files if it exists
                $content = Message::trans($this->message);
            }
        }

        if ($this->type == 'plain') {
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
        }

        if (in_array($this->type, ['plain', 'richeditor'])) {
            if (!$this->isEditor) {
                //reset properties for next component
                $this->setProperty('type', '');
                $this->setProperty('toolbarButtons', '');
                $this->setProperty('content', '');
                $this->setProperty('showDelete', false);
                $this->setProperty('model', '');
                $this->setProperty('cssClass', '');
                return $content;
            }
    
            if (!$content) {
                $content = "[empty]";
            }
    
            $this->content = $content;
    
            if (isset($model)) {
                $this->model_class = get_class($model);
                $this->model_id = $model->id;
            }
        }

        if ($this->type == 'image') {
            if (!isset($model)) {
                $image = Image::where('code', $this->message)->first();
                $content = $image->image ?? null;
            }
            if ($content && !$content->title) {
                $content->title_default = $this->property('alt');
            }
            $this->imagePartial = $this->property('partial');
            $this->imageEmptyPartial = $this->alias.'::image-empty';

            if ($this->isEditor) {
                $content = $this->decorateFileAttributes($content);
                if (isset($model)) {
                    $this->model_class = get_class($model);
                    $this->model_id = $model->id;
                    $this->uploadId = str_slug($this->model_class).'-'.$this->model_id.'-'.$this->message;
                } else {
                    $this->uploadId = 'image-'.$this->message;
                }
                $this->refreshCode = $this->property('refresh');
                $this->labelCreate = Lang::get('utopigs.epigtor::lang.image.create');
                $this->labelDelete = Lang::get('utopigs.epigtor::lang.image.delete');
                $this->labelDeleteConfirm = Lang::get('utopigs.epigtor::lang.image.delete_confirm');
                $this->labelSave = Lang::get('utopigs.epigtor::lang.image.save');
                $this->labelCancel = Lang::get('utopigs.epigtor::lang.image.cancel');
                $this->labelUpload = Lang::get('utopigs.epigtor::lang.image.upload');
                $this->labelReplace = Lang::get('utopigs.epigtor::lang.image.replace');
                $this->labelImageTitle = Lang::get('utopigs.epigtor::lang.image.title');
            }

            $this->content = $content;
        }

        if ($this->type == 'link') {
            $this->linkPartial = $this->property('partial');
            $this->linkEmptyPartial = $this->alias.'::link-empty';
            if (!isset($model)) {
                $content = Link::where('code', $this->message)->first();
            }
            if ($this->isEditor) {
                if (isset($model)) {
                    $this->model_class = get_class($model);
                    $this->model_id = $model->id;
                    $this->uploadId = str_slug($this->model_class).'-'.$this->model_id.'-'.$this->message;
                } else {
                    $this->uploadId = 'link-'.$this->message;
                }
                $this->labelCreate = Lang::get('utopigs.epigtor::lang.link.create');
                $this->labelDelete = Lang::get('utopigs.epigtor::lang.link.delete');
                $this->labelDeleteConfirm = Lang::get('utopigs.epigtor::lang.link.delete_confirm');
                $this->labelSave = Lang::get('utopigs.epigtor::lang.link.save');
                $this->labelCancel = Lang::get('utopigs.epigtor::lang.link.cancel');
                $this->labelLinkText = Lang::get('utopigs.epigtor::lang.link.text');
                $this->labelLinkType = Lang::get('utopigs.epigtor::lang.link.type');
                $this->labelLinkUrl = Lang::get('utopigs.epigtor::lang.link.url');
                $this->labelLinkReference = Lang::get('utopigs.epigtor::lang.link.reference');
                $this->labelLinkIsNewTab = Lang::get('utopigs.epigtor::lang.link.is_new_tab');
            }
            $this->content = $content;
        }

        //reset properties for next component
        $this->setProperty('type', '');
        $this->setProperty('toolbarButtons', '');
        $this->setProperty('content', '');
        $this->setProperty('showDelete', false);
        $this->setProperty('model', '');
        $this->setProperty('cssClass', '');
    }

    protected function decorateFileAttributes($file)
    {
        if (!$file) return;

        $path = $thumb = $file->getPath();

        if ($this->imageWidth || $this->imageHeight) {
            $thumb = $file->getThumb($this->imageWidth, $this->imageHeight, $this->thumbOptions);
        }

        $file->pathUrl = $path;
        $file->thumbUrl = $thumb;

        return $file;
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

    public function onUpload()
    {
        $controller = new Controller;
        new MediaManager($controller, 'ocmediamanager');

        return $controller->makeResponse(null);
    }

    public function onUploadImage()
    {
        if (!Input::hasFile('file_data')) {
            throw new ApplicationException('File missing from request');
        }

        $uploadedFile = Input::file('file_data');

        $modelClass = post('model_class');
        $modelId = post('model_id');
        $attribute = post('message');

        if ($modelClass) {
            $model = $modelClass::findOrFail($modelId);
        } else {
            $model = Image::firstOrCreate(['code' => $attribute]);
            $attribute = 'image';
        }

        $fileModel = $model->makeRelation($attribute);
        $fileRelation = $model->{$attribute}();
        $file = $fileModel;
        $file->data = $uploadedFile;
        $file->is_public = $fileRelation->isPublic();
        $file->save();

        $file = $this->decorateFileAttributes($file);

        $result = [
            'id' => $file->id,
            'thumb' => $file->thumbUrl,
            'path' => $file->pathUrl
        ];

        $response = Response::make($result, 200);

        return $response;
    }

    public function onGetUploadedImage()
    {
        $modelClass = post('model')['model'];
        $modelId = post('model')['id'];
        $attribute = post('message');
        $imageId = post('imageId');
        $imagePartial = post('imagePartial');
        $save = post('save');

        $file = File::findOrFail($imageId);
        if ($save) {
            if ($modelClass) {
                $model = $modelClass::findOrFail($modelId);
                $fileRelation = $model->{$attribute}();
            } else {
                $model = Image::firstOrCreate(['code' => $attribute]);
                $fileRelation = $model->image();
            }
            $fileRelation->add($file);
        }

        if ($modelClass) {
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            $widgetId = 'image-'.$attribute;
        }

        return [
            'image' => $file,
            '#epigtor-'.$widgetId => $this->renderPartial($imagePartial, [
                'image' => $file
            ])
        ];
    }

    public function onDeleteImage()
    {
        $imageId = post('imageId');
        $modelClass = post('model')['model'];
        $modelId = post('model')['id'];
        $attribute = post('message');

        File::findOrFail($imageId)->delete();

        if ($modelClass) {
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            $widgetId = 'image-'.$attribute;
        }

        return [
            '#epigtor-'.$widgetId => $this->renderPartial($this->alias.'::image-empty', [
                'labelCreate' => Lang::get('utopigs.epigtor::lang.image.create')
            ])
        ];
    }

    public function onCancelUploadedImage()
    {
        $modelClass = post('model')['model'];
        $modelId = post('model')['id'];
        $attribute = post('message');
        if ($modelClass) {
            $model = $modelClass::findOrFail($modelId);
            $file = $model->$attribute;
        } else {
            $model = Image::firstOrCreate(['code' => $attribute]);
            $file = $model->image;
        }
        
        $imageId = post('imageId');
        $imagePartial = post('imagePartial');

        if (!$file || ($file->id != $imageId)) {
            $fileToDelete = File::findOrFail($imageId);
            $fileToDelete->delete();
        }

        if ($modelClass) {
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            $widgetId = 'image-'.$attribute;
        }

        if ($file) {
            $file = $this->decorateFileAttributes($file);
            return [
                'image' => $file,
                '#epigtor-'.$widgetId => $this->renderPartial($imagePartial, [
                    'image' => $file
                ])
            ];
        } else {
            return [
                '#epigtor-'.$widgetId => $this->renderPartial($this->alias.'::image-empty', [
                    'labelCreate' => Lang::get('utopigs.epigtor::lang.image.create')
                ])
            ];
        }
    }

    public function onSaveImageTitle()
    {
        $fileId = post('fileId');
        $title = post('title');
        $file = File::findOrFail($fileId);
        $file->title = $title;
        $file->save();
    }

    public function onSaveLink()
    {
        $linkId = post('linkId');
        $linkPartial = post('linkPartial');
        $text = post('text');
        $type = post('type');
        $externalUrl = post('external_url');
        $reference = post('reference');
        $isNewTab = post('is_new_tab');
        $modelClass = post('model')['model'];
        $modelId = post('model')['id'];
        $attribute = post('message');
        $cssClass = post('cssClass');

        if ($linkId) {
            $link = Link::findOrFail($linkId);
        } else {
            $link = new Link;
            if ($modelClass) {
                $model = $modelClass::findOrFail($modelId);
                $link->field = $attribute;
            } else {
                $link->code = $attribute;
            }
        }

        $link->text = $text;
        $link->type = $type;
        $link->external_url = $externalUrl;
        $link->reference = $reference;
        $link->is_new_tab = $isNewTab;
        $link->save();

        if (isset($model)) {
            $model->linkables()->add($link);
        }

        if ($modelClass) {
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            $widgetId = 'link-'.$attribute;
        }

        return [
            'link' => $link,
            '#epigtor-'.$widgetId => $this->renderPartial($linkPartial, [
                'link' => $link,
                'cssClass' => $cssClass
            ])
        ];
    }

    public function onDeleteLink()
    {
        $linkId = post('linkId');
        $modelClass = post('model')['model'];
        $modelId = post('model')['id'];
        $attribute = post('message');

        Link::findOrFail($linkId)->delete();

        if ($modelClass) {
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            $widgetId = 'link-'.$attribute;
        }

        return [
            '#epigtor-'.$widgetId => $this->renderPartial($this->alias.'::link-empty', [
                'labelCreate' => Lang::get('utopigs.epigtor::lang.link.create')
            ])
        ];
    }

    public function onGetTypeOptions()
    {
        $link = new Link;
        $options = $link->getTypeOptions();
        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return [
            'options' => $options
        ];
    }

    public function onGetReferenceOptions()
    {
        $link = new Link;
        $link->type = post('type');
        $options = $link->getReferenceOptions();

        return [
            'options' => $options
        ];
    }

}