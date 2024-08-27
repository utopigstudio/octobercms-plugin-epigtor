<?php namespace Utopigs\Epigtor\Traits;

use ApplicationException;
use Input;
use Lang;
use Response;
use System\Models\File;
use Utopigs\Banners\Models\Image;

trait EpigtorImage
{
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

    public $imagePartial;
    public $imageEmptyPartial;
    public $linkPartial;
    public $linkEmptyPartial;
    public $labelUpload;
    public $labelReplace;
    public $labelImageTitle;

    private function getContentImage()
    {
        $image = Image::where('code', $this->message)->first();
        $content = $image->image ?? null;

        return $content;
    }

    private function renderImage($content)
    {
        if ($content && !$content->title) {
            $content->title_default = $this->property('alt');
        }
        $this->imagePartial = $this->property('partial');
        $this->imageEmptyPartial = $this->alias.'::image-empty';

        if ($this->isEditor) {
            $content = $this->decorateFileAttributes($content);
            if ($this->model_class) {
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

    private function decorateFileAttributes($file)
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

}