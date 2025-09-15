<?php namespace Utopigs\Epigtor\Traits;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use Cms\Models\PageLookupItem;
use Event;
use Lang;

trait EpigtorLink
{
    public $labelLinkText;
    public $labelLinkType;
    public $labelLinkUrl;
    public $labelLinkReference;
    public $labelLinkIsNewTab;

    private function getContentLink()
    {
        /**
         * @disregard P1009 Undefined type
         */
        $content = \Utopigs\Linkable\Models\Link::where('code', $this->message)->first();

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
        $isOldLink = post('isOldLink');

        if ($isOldLink) {
            if ($linkId) {
                /**
                 * @disregard P1009 Undefined type
                 */
                $link = \Utopigs\Linkable\Models\Link::findOrFail($linkId);
            } else {
                /**
                 * @disregard P1009 Undefined type
                 */
                $link = new \Utopigs\Linkable\Models\Link;
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
        } else {
            $model = $modelClass::findOrFail($modelId);
            $model->$attribute = PageLookupItem::encodeSchema($type, $reference ?? '', !empty($externalUrl) ? ['url' => $externalUrl] : []);
            $model->{$attribute . '_title'} = $text;
            $model->{$attribute . '_is_new_tab'} = $isNewTab;
            $model->save();

            $link = [
                'url' => $this->parseOctoberLink($model->$attribute),
                'text' => $text,
                'is_new_tab' => $isNewTab,
                'type' => $type,
                'external_url' => $externalUrl,
                'reference' => $reference,
            ];

            $widgetId = str_slug($modelClass).'-'.$modelId.'-'.$attribute;
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

        /**
         * @disregard P1009 Undefined type
         */
        \Utopigs\Linkable\Models\Link::findOrFail($linkId)->delete();

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
        $options = $this->getTypes();
        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return [
            'options' => $options
        ];
    }

    public function onGetReferenceOptions()
    {
        $options = $this->getReferences(post('type'));

        return [
            'options' => $options
        ];
    }

    private function getTypes()
    {
        $result = [];
        $apiResult = Event::fire('cms.pageLookup.listTypes');

        $result['url'] = trans('utopigs.linkable::lang.fields.type_url');

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeList) {
                if (!is_array($typeList)) {
                    continue;
                }

                foreach ($typeList as $typeCode => $typeName) {
                    $apiResult2 = Event::fire('cms.pageLookup.getTypeInfo', [$typeCode]);
                    if (is_array($apiResult2)) {
                        foreach ($apiResult2 as $typeInfo) {
                            if (isset($typeInfo['references'])) {
                                $result[$typeCode] = $typeName;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function getReferences($type)
    {
        $items = [];
        if (!$type) return $items;
        if ($type == 'url') return $items;

        if ($type == 'cms-page') {
            $theme = Theme::getActiveTheme();
            $pages = Page::listInTheme($theme, true);

            foreach ($pages as $page) {
                if (!isset($page->settings['is_linkable']) || $page->settings['is_linkable']==true) {
                    $items[$page->getBaseFileName()] = $page->title . ' [' . $page->getBaseFileName() . ']';
                }
            }

            return $items;
        }

        $apiResult = Event::fire('cms.pageLookup.getTypeInfo', [$type]);

        $iterator = function($children) use (&$iterator) {
            $child_items = [];

            foreach ($children as $child_key => $child) {
                if (is_array($child)) {
                    $child_items[$child_key] = $child['title'];
                    if (!empty($child['items'])) {
                        $child_items = array_replace($child_items, $iterator($child['items']));
                    }
                } else {
                    $child_items[$child_key] = $child;
                }
            }

            return $child_items;
        };

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeInfo) {
                if (isset($typeInfo['references'])) {
                    foreach ($typeInfo['references'] as $key => $item) {
                        if (is_array($item)) {
                            $items[$key] = $item['title'];
                            if (!empty($item['items'])) {
                                $items = array_replace($items, $iterator($item['items']));
                            }
                        } else {
                            $items[$key] = $item;
                        }

                    }
                    return $items;
                }
            }
        }

        return $items;
    }

}