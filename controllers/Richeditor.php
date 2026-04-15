<?php namespace Utopigs\Epigtor\Controllers;

use Backend\Classes\Controller;
use Illuminate\Support\Facades\Crypt;
use October\Rain\Database\Model;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use System\Helpers\Cache as CacheHelper;

class Richeditor extends Controller
{
    public $requiredPermissions = ['rainlab.translate.manage_messages'];

    protected $payload;

    public function __construct()
    {
        parent::__construct();

        $this->bodyClass = 'compact-container epigtor-richeditor-backend';
        $this->vars['hideMainMenu'] = true;

        $this->addCss('/plugins/utopigs/epigtor/controllers/richeditor/assets/richeditor-window.css', 'Utopigs.Epigtor');
        $this->addJs('/plugins/utopigs/epigtor/controllers/richeditor/assets/richeditor-window.js', 'Utopigs.Epigtor');
    }

    public function index()
    {
        $this->payload = $this->decodePayload(get('payload'));
        $this->applyPayloadLocale($this->payload);

        $this->vars['payload'] = get('payload');
        $this->vars['instanceId'] = $this->payload['instance_id'];
        $this->vars['formWidget'] = $this->makeEditorWidget();
    }

    public function onSave()
    {
        $payload = $this->decodePayload(post('payload'));
        $this->applyPayloadLocale($payload);

        $content = (string) data_get(post('EpigtorRicheditor'), 'content', '');
        $this->saveContent($payload, $content);

        // Keep output consistent with frontend rendering behavior.
        $content = preg_replace('/<img(?![^>]*\balt=)([^>]*)>/i', '<img alt="" $1>', $content);

        return [
            'saved' => true,
            'instanceId' => $payload['instance_id'],
            'content' => $content,
        ];
    }

    protected function makeEditorWidget()
    {
        $model = new Model();
        $model->forceFill([
            'content' => $this->getCurrentContent($this->payload),
        ]);

        $widgetConfig = $this->makeConfig([
            'model' => $model,
            'alias' => 'epigtorRicheditorForm',
            'arrayName' => 'EpigtorRicheditor',
            'fields' => [
                'content' => [
                    'label' => 'Content',
                    'type' => 'richeditor',
                    'size' => 'huge',
                    'span' => 'full',
                ],
            ],
        ]);

        $toolbarButtons = trim((string) ($this->payload['toolbar_buttons'] ?? ''));
        if ($toolbarButtons !== '') {
            $widgetConfig->fields['content']['toolbarButtons'] = $toolbarButtons;
        }

        $widget = $this->makeWidget('Backend\\Widgets\\Form', $widgetConfig);
        $widget->bindToController();

        return $widget;
    }

    protected function decodePayload(?string $payload): array
    {
        if (!$payload) {
            abort(404);
        }

        try {
            $decoded = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            abort(404);
        }

        if (!is_array($decoded) || empty($decoded['message']) || empty($decoded['instance_id'])) {
            abort(404);
        }

        if (!array_key_exists('locale', $decoded)) {
            $decoded['locale'] = null;
        }

        return $decoded;
    }

    protected function getCurrentContent(array $payload): string
    {
        $message = $payload['message'];
        $modelClass = $payload['model_class'] ?? null;
        $modelId = $payload['model_id'] ?? null;

        if ($modelClass && $modelId) {
            if (!class_exists($modelClass)) {
                abort(404);
            }

            $model = $modelClass::find($modelId);
            if (!$model) {
                abort(404);
            }

            return (string) data_get($model, $message, '');
        }

        $locale = $this->resolvePayloadLocale($payload);

        return (string) Message::trans($message, [], $locale);
    }

    protected function saveContent(array $payload, string $content): void
    {
        $message = $payload['message'];
        $modelClass = $payload['model_class'] ?? null;
        $modelId = $payload['model_id'] ?? null;

        if ($modelClass && $modelId) {
            if (!class_exists($modelClass)) {
                abort(404);
            }

            $model = $modelClass::findOrFail($modelId);
            $model->$message = $content;
            $model->save();

            return;
        }

        $locale = $this->resolvePayloadLocale($payload);
        $messages = Message::where('locale', $locale)->first() ?: new Message();
        $storedContent = $messages?->data[$message] ?? '';

        if ($content !== $storedContent) {
            $messages->updateMessage($locale, $message, $content);
            CacheHelper::clear();
        }
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
