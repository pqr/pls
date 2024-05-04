<?php


namespace Light;


use Light\Field\FieldSelect;
use Light\Field\PipeSeparatedIdsTrait;
use Psr\Http\Message\ServerRequestInterface;

class Select2ajaxController
{
    private const UPDATE_ACTION_RESET = 'reset';
    private const UPDATE_ACTION_CHANGE = 'change';
    private const UPDATE_ACTION_DONTCHANGE = 'dontchange';

    public function proceed(FieldSelect $field, ServerRequestInterface $request): array
    {
        $options = $field->getEvaluatedOptions();

        $loadOptionIds = $request->getQueryParams()['loadOptionIds'] ?? null;
        if ($loadOptionIds) {
            // От клиента пришел запрос на загрузку одной конкретной опции
            // обычно это происходит, когда в фильтрах вызывается ранее сохранённый набор фильтров
            return $this->proceedLoadOptionIds($field, $options, $loadOptionIds);
        }

        $parentFieldValuesFromAjax = $request->getQueryParams()['parentFieldValues'] ?? [];
        if ($parentFieldValuesFromAjax && \is_array($parentFieldValuesFromAjax)) {
            // Вместе с ajax запросом пришли какие-то значения parent полей
            // используем их, чтобы уменьшить массив валидных опций
            $options = $this->filterChildOptions($field, $options, $parentFieldValuesFromAjax);
        }

        $currentChildValue = $request->getQueryParams()['updateCurrentChildValue'] ?? null;
        if ($currentChildValue) {
            // Это запрос проверка - валидно ли текущее child значение в условиях новых parent значений?
            return $this->proceedUpdateCurrentChildValue($field, $options, $currentChildValue);
        }

        // Если дошли до этого места, значит мы работаем в режиме "показать все значения в выпадающем списке при раскрытии"
        $searchTerm = $request->getQueryParams()['term'] ?? null;
        return $this->proceedShowAll($field, $options, $searchTerm);
    }

    protected function proceedLoadOptionIds(FieldSelect $field, array $options, string $loadOptionIds): array
    {
        // От клиента пришел запрос на загрузку одной конкретной опции
        // обычно это происходит, когда в фильтрах вызывается ранее сохранённый набор фильтров
        $ids = array_filter(explode(',', $loadOptionIds));
        $returnOptions = [];
        foreach ($ids as $id) {
            if (isset($options[$id])) {
                $returnOptions[$id] = $options[$id];
            }
        }
        return [
            'results' => $this->getResultsArray($field, $returnOptions),
        ];
    }

    protected function proceedUpdateCurrentChildValue(FieldSelect $field, array $options, $currentChildValue): array
    {
        // Это запрос проверка - валидно ли текущее child значение в условиях новых parent значений?
        $availableOptionsCount = count($options);

        if ($availableOptionsCount === 1
            && ($currentChildValue === '' || !isset($options[$currentChildValue]))
        ) {
            // Текущее значение в выпадающем списке на экране пользователя - пусто
            // либо текущее значение не присуствует среди допустимых значений
            // при этом допустимых значений всего ровно одно - используем его!
            // Если среди отфильтрованных $options осталось только одно возможное значение - именно его и нужно выбрать
            $theOnlyOption = reset($options);
            $theOnlyOptionValue = key($options);
            return [
                'action' => self::UPDATE_ACTION_CHANGE,
                'id' => $theOnlyOptionValue,
                'text' => $field->getOptionText($theOnlyOption, $theOnlyOptionValue),
            ];
        }

        // Если же текущее значение child выпадающего списка на экране пользователя не пусто, то нужно понять
        // является ли оно допустимым?
        if ($currentChildValue === '' || isset($options[$currentChildValue])) {
            // Да, текущее значение допустимо
            return [
                'action' => self::UPDATE_ACTION_DONTCHANGE,
            ];
        }

        // Текущее значение не допустимо, нужно отправить инструкцию на сборс на пустой вариант
        return [
            'action' => self::UPDATE_ACTION_RESET,
        ];
    }

    protected function proceedShowAll(FieldSelect $field, array $options, ?string $searchTerm): array
    {
        // Если дошли до этого места, значит мы работаем в режиме "показать все значения в выпадающем списке при раскрытии"
        if ($searchTerm) {
            // Вместе с ajax запросом пришел параметр поиска по подстроке - отфильтруем по этой строке
            $options = $this->filterBySearchTerm($field, $options, $searchTerm);
        }

        // Соберём все полученные $options по optgroups
        $noOptgroup = [];
        $optgroups = [];
        // $inactiveGroup = [];
        foreach ($options as $optionValue => $option) {

            if (\is_array($option) && isset($option['Active']) && !$option['Active']) {
                // эта запись неактивна
                // $inactiveGroup[$optionValue] = $option;
                continue;
            }

            if (\is_array($option) && isset($option['optgroup']) && $option['optgroup'] !== '') {
                $optgroup = $option['optgroup'];
                $optgroups[$optgroup][$optionValue] = $option;
                continue;
            }

            $noOptgroup[$optionValue] = $option;
        }

        // Теперь добавляем всё в финальный массив $results
        // https://select2.org/data-sources/formats
        $results = [];
        if ($field->any && !$field->multiple) {
            $results[] = ['id' => '%', 'text' => $field->anyText];
        }
        if ($field->empty && !$field->multiple) {
            $results[] = ['id' => '', 'text' => $field->emptyText, 'cssClass' => $field->emptyCssClass];
        }

        // Сначала записываем все опции БЕЗ группы
        if ($noOptgroup) {
            $results = array_merge($results, $this->getResultsArray($field, $noOptgroup));
        }

        // Затем перебираем все группы
        foreach ($optgroups as $optgroup => $optionsInOptgroup) {
            $results[] = [
                'text' => $optgroup,
                'children' => $this->getResultsArray($field, $optionsInOptgroup),
            ];
        }

        // В самом конце добавляем группу Inactive, но только если пользователь явно хотел поискать по какому-то тексту
        // если же пользозовател просто раскрыл список "всех", то не будем выводить Inactive группу опций
//        if ($searchTerm && $inactiveGroup) {
//            $results[] = [
//                'text' => 'Inactive',
//                'children' => $this->getSelect2ajaxResultsArray($inactiveGroup),
//            ];
//        }

        return ['results' => $results];
    }

    /**
     * По полученному из ajax запроса массиву $parentFieldValuesFromAjax отфильтруем опции текущего FieldSelect
     * (который является child)
     *
     * @return array
     */
    private function filterChildOptions(FieldSelect $field, array $options, array $parentFieldValuesFromAjax): array
    {
        $relations = $field->relations;
        if (!$relations) {
            // Для этого поля не заданы соответсвеия parent-child, возвращаем входящий массив как есть без фильтрации
            return $options;
        }

        // Проверим пришедший вместе с ajax запросом массив $parentFields - есть ли в нём не пустые значения?
        $parentFieldValuesFromAjax = array_filter($parentFieldValuesFromAjax, static function ($parentValue) {
            $parentValue = (string)$parentValue;
            return $parentValue !== '' && $parentValue !== '%' && $parentValue !== '0' && $parentValue !== '||';
        });

        if ($field->parentFieldKeysMandatory) {
            $parentFieldKeysMandatory = is_string($field->parentFieldKeysMandatory)
                ? [$field->parentFieldKeysMandatory]
                : $field->parentFieldKeysMandatory;
            // Для данного поля определены некоторые parent поля, которые должны быть обязательно переданы
            // Проверим, все ли обязательные parent поля переданы с этим запросом?
            $fieldKeysDiff = array_diff($parentFieldKeysMandatory, array_keys($parentFieldValuesFromAjax));
            if ($fieldKeysDiff) {
                // Какие-то из обязательных полей указанных в $this->parentFieldKeysMandatory не переданы в запросе
                // тогда выведем пользователю пустой массив
                return [];
            }
        }

        if (!$parentFieldValuesFromAjax) {
            // Пришедшие от клиента по ajax значени parent полей все пустые, значит нет смысла фильтровать опции
            // текущиего child поля, возвращаем входящий массив как есть без фильтрации
            return $options;
        }

        // Расшифруем параметр $relations, записанный в настройках текщущего поля
        // - это может быть функция
        // - или sql запрос
        // - или готовый массив
        $relationsByCurrentFieldKey = null;
        if (is_callable($relations)) {
            // Пользователь определил метод, который возвращает массив связей - вызовем этот метод
            $relations = $relations($options, $parentFieldValuesFromAjax, $this);
        } elseif (is_string($relations)) {
            // Этот Relations в форме запроса - выполним запрос!
            $relations = Db::getAll($relations);
        }

        // Переформатируем массив relations так, чтобы в качестве ключей были значения относящиеся к текущему полю
        // Например, изначально массив $relations выглядел так:
        // $relations = [['CustomerAccountID' => 1, 'CustomerID' => 15], ['CustomerAccountID' => 1, 'CustomerID' => 16]]
        // После группировки по CustomerAccountID получим удобную для поиска структуру:
        // $relationsByCurrentFieldKey = [1 => [['CustomerID' => 15], ['CustomerID' => 16]]]
        $relationsByCurrentFieldKey = A::groupBy($relations, $field->key);

        $filteredOptions = [];

        foreach ($options as $optionValue => $option) {

            // Если заданы какие-то связи с родительскими полями, то нужно проверить текущий option - подходит ли он?
            if (!isset($relationsByCurrentFieldKey[$optionValue])) {
                // Текущее значение option вообще не отображено в массиве relations - значит для этого option
                // нет вообще удовлетворительной комбинации родительских полей
                continue;
            }

            // Проверим, удовлетворяет ли текущий optionValue массиву связям с родительскими полями?
            $relationsForThisChildValue = $relationsByCurrentFieldKey[$optionValue];
            if (!$this->isParentChildPass($relationsForThisChildValue, $parentFieldValuesFromAjax)) {
                // Текуща опция не проходит по условиям связи с родительскими полями
                continue;
            }

            $filteredOptions[$optionValue] = $option;
        }

        return $filteredOptions;

    }

    /**
     * Проходит ли текущий элемент проверку по массиву связей parent-child?
     * @return bool
     */
    private function isParentChildPass(array $relationsForThisChildValue, array $parentFieldValuesFromAjax): bool
    {
        // Вернём true, если хотя бы одна комбинация из массива $relations соответсвует значениям родительских полей
        // пришедших по ajax
        return A::some($relationsForThisChildValue, static function ($relation) use ($parentFieldValuesFromAjax) {
            // Удовлетворяет ли текущая комбинация $relation загруженная из базы тем значениям $parentFieldValues,
            // которые пришли по ajax?
            foreach ($relation as $relationParentFieldKey => $relationParentFieldValue) {
                // Проверяем только те поля, которые реально пришли с ajax запросом
                if (isset($parentFieldValuesFromAjax[$relationParentFieldKey])) {
                    $valueFromAjax = $parentFieldValuesFromAjax[$relationParentFieldKey];
                    if (str_contains($valueFromAjax, '|')) {
                        $multipleValuesFromAjax = PipeSeparatedIdsTrait::parseValue($valueFromAjax);
                        if (!in_array($relationParentFieldValue, $multipleValuesFromAjax)) {
                            // Текущая комбинация relations загруженная из базы провалила проверку, попробуем в цикле найти следующую
                            return false;
                        }
                    } elseif ($valueFromAjax != $relationParentFieldValue) {
                        // Текущая комбинация relations загруженная из базы провалила проверку, попробуем в цикле найти следующую
                        return false;
                    }
                }
            }

            // Проверку по всем родительcким полям прошли успешно!
            return true;
        });
    }

    protected function getResultsArray(FieldSelect $field, array $options)
    {
        $results = [];
        foreach ($options as $optionValue => $option) {
            $results[] = [
                'id' => $optionValue,
                'text' => $field->getOptionText($option, $optionValue),
            ];
        }
        return $results;
    }

    private function filterBySearchTerm(FieldSelect $field, array $options, string $searchTerm): array
    {
        $out = [];
        $oppositeSearchTerm = Translate::oppositeString($searchTerm);
        foreach ($options as $optionValue => $option) {
            $optionText = $field->getOptionText($option, $optionValue);
            if (mb_stripos($optionText, $searchTerm) !== false
                || mb_stripos($optionText, $oppositeSearchTerm) !== false
            ) {
                $out[$optionValue] = $option;
            }
        }
        return $out;
    }
}
