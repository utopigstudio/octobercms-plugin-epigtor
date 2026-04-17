<?php namespace Utopigs\Epigtor\Traits;

use Backend;
use Illuminate\Support\Facades\Crypt;
use Lang;
use RainLab\Translate\Classes\Translator;

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
    public $imageInstanceId;
    public $imagePopupUrl;

    private function getContentImage()
    {
        /**
         * @disregard P1009 Undefined type
         */
        $image = \Utopigs\Banners\Models\Image::where('code', $this->message)->first();
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
            $this->imageInstanceId = $this->makeImageInstanceId();
            $this->imagePopupUrl = $this->makeImagePopupUrl();
        }

        $this->content = $content;
    }

    protected function makeImagePopupUrl(): string
    {
        $payload = [
            'instance_id' => $this->imageInstanceId,
            'message' => $this->message,
            'model_class' => $this->model_class,
            'model_id' => $this->model_id,
            'session_key' => $this->imageInstanceId,
            'locale' => Translator::instance()->getLocale(),
        ];

        return Backend::url('utopigs/epigtor/image') . '?payload=' . urlencode(Crypt::encryptString(json_encode($payload)));
    }

    protected function makeImageInstanceId(): string
    {
        return md5(implode('|', [
            $this->alias,
            $this->message,
            $this->model_class,
            $this->model_id,
            uniqid('', true),
        ]));
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

    public function onRefreshImage()
    {
        $modelClass = post('model')['model'] ?? null;
        $modelId = post('model')['id'] ?? null;
        $attribute = post('message');
        $imagePartial = post('imagePartial');

        if ($modelClass) {
            $model = $modelClass::findOrFail($modelId);
            $file = $model->$attribute;
            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
        } else {
            /** @disregard P1009 Undefined type */
            $model = \Utopigs\Banners\Models\Image::firstOrCreate(['code' => $attribute]);
            $file = $model->image;
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
        }

        return [
            '#epigtor-'.$widgetId => $this->renderPartial($this->alias.'::image-empty', [
                'labelCreate' => Lang::get('utopigs.epigtor::lang.image.create')
            ])
        ];
    }

}