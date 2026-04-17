<?php namespace Utopigs\Epigtor\Controllers;

use Backend\Classes\FormField;
use Backend\Classes\Controller;
use Backend\FormWidgets\FileUpload as FileUploadWidget;
use Illuminate\Support\Facades\Crypt;
use October\Rain\Database\Model;
use RainLab\Translate\Classes\Translator;
use System\Models\File;

class Image extends Controller
{
    public $requiredPermissions = ['rainlab.translate.manage_messages'];

    protected $payload;

    public function __construct()
    {
        parent::__construct();

        $this->bodyClass = 'compact-container epigtor-image-backend';
        $this->vars['hideMainMenu'] = true;

        $this->addCss('/plugins/utopigs/epigtor/controllers/image/assets/image-window.css', 'Utopigs.Epigtor');
        $this->addJs('/plugins/utopigs/epigtor/controllers/image/assets/image-window.js', 'Utopigs.Epigtor');
    }

    public function index()
    {
        $this->payload = $this->decodePayload(get('payload'));
        $this->applyPayloadLocale($this->payload);

        $this->vars['payload'] = get('payload');
        $this->vars['instanceId'] = $this->payload['instance_id'];
        $this->vars['formWidget'] = $this->makeImageWidget();
    }

    public function onSave()
    {
        $payload = $this->decodePayload(post('payload'));
        $this->applyPayloadLocale($payload);
        [$model, $attribute] = $this->resolveTarget($payload);

        $model->save([
            'sessionKey' => $payload['session_key'],
            'propagate' => true,
        ]);

        $image = $model->$attribute;

        return [
            'saved' => true,
            'instanceId' => $payload['instance_id'],
            'imageId' => $image ? $image->id : null,
            'hasImage' => (bool) $image,
        ];
    }

    public function onCancel()
    {
        $payload = $this->decodePayload(post('payload'));
        $this->applyPayloadLocale($payload);
        [$model] = $this->resolveTarget($payload);

        $model->cancelDeferred($payload['session_key']);

        return [
            'canceled' => true,
        ];
    }

    protected function makeImageWidget()
    {
        [$model, $attribute] = $this->resolveTarget($this->payload);

        $formField = new FormField([
            'fieldName' => $attribute,
            'valueFrom' => $attribute,
            'label' => 'Image',
            'type' => 'fileupload',
        ]);
        $formField->arrayName('EpigtorImage');

        $widget = new FileUploadWidget($this, $formField, [
            'model' => $model,
            'sessionKey' => $this->payload['session_key'],
            'deferredBinding' => true,
            'mode' => 'image',
            'span' => 'full',
        ]);
        $widget->bindToController();

        return $widget;
    }

    protected function resolveTarget(array $payload): array
    {
        $message = $payload['message'];
        $modelClass = $payload['model_class'] ?? null;
        $modelId = $payload['model_id'] ?? null;

        if ($modelClass && $modelId) {
            if (!class_exists($modelClass)) {
                abort(404);
            }

            $model = $modelClass::findOrFail($modelId);
            if (!$model->hasRelation($message)) {
                abort(404);
            }

            return [$model, $message];
        }

        /** @disregard P1009 Undefined type */
        $model = \Utopigs\Banners\Models\Image::firstOrCreate(['code' => $message]);

        return [$model, 'image'];
    }

    protected function decodePayload(?string $payload): array
    {
        if (!$payload) {
            abort(404);
        }

        try {
            $decoded = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\Throwable $exception) {
            abort(404);
        }

        if (
            !is_array($decoded)
            || empty($decoded['message'])
            || empty($decoded['instance_id'])
            || empty($decoded['session_key'])
        ) {
            abort(404);
        }

        if (!array_key_exists('locale', $decoded)) {
            $decoded['locale'] = null;
        }

        return $decoded;
    }

    protected function applyPayloadLocale(array $payload): void
    {
        $locale = $this->resolvePayloadLocale($payload);
        Translator::instance()->setLocale($locale, false);
    }

    protected function resolvePayloadLocale(array $payload): string
    {
        $payloadLocale = (string) ($payload['locale'] ?? '');

        if ($payloadLocale !== '' && Translator::instance()->setLocale($payloadLocale, false)) {
            return $payloadLocale;
        }

        return Translator::instance()->getLocale();
    }
}
