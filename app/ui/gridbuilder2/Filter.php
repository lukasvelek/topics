<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

class Filter implements IHTMLOutput {
    public string $name;
    private array $values;
    public mixed $currentValue;

    private string $componentName;

    /**
     * Methods called with parameters: QueryBuilder $qb, Filter $filter
     */
    public array $onSqlExecute;

    public function __construct(string $name, mixed $currentValue = null, array $values = []) {
        $this->name = $name;
        $this->currentValue = $currentValue;
        $this->values = $values;
        $this->onSqlExecute = [];
    }

    public function inject(string $componentName) {
        $this->componentName = $componentName;
    }

    public function output(): HTML {
        $el = HTML::el('select');
        $el->text($this->prepareOptionsCode());

        return $el;
    }

    private function prepareOptionsCode() {
        $options = [];
        foreach($this->values as $key => $value) {
            $options[] = '<option value="' . $key . '">' . $value . '</option>';
        }

        return implode('', $options);
    }
}

?>