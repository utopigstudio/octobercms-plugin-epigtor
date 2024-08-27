<?php namespace Utopigs\Epigtor\Traits;

use Lang;
use Utopigs\Linkable\Models\Link;

trait EpigtorLink
{
    public $labelLinkText;
    public $labelLinkType;
    public $labelLinkUrl;
    public $labelLinkReference;
    public $labelLinkIsNewTab;

    private function getContentLink()
    {
        $content = Link::where('code', $this->message)->first();

        return $content;
    }
    
    private function renderLink($content)
    {
        $this->linkPartial = $this->property('partial');
        $this->linkEmptyPartial = $this->alias.'::link-empty';
        if ($this->isEditor) {
            if ($this->model_class) {
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