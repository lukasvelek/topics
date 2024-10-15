<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

/**
 * Class that represents a grid filter
 * 
 * @author Lukas Velek
 */
class Filter implements IHTMLOutput {
    public string $name;
    private array $values;
    public mixed $currentValue;

    private string $componentName;

    /**
     * Methods called with parameters: QueryBuilder $qb, Filter $filter
     */
    public array $onSqlExecute;

    /**
     * Class constructor
     * 
     * @param string $name Filter name
     * @param mixed $currentValue Current selected value
     * @param array $values Filter values
     */
    public function __construct(string $name, mixed $currentValue = null, array $values = []) {
        $this->name = $name;
        $this->currentValue = $currentValue;
        $this->values = $values;
        $this->onSqlExecute = [];
    }

    /**
     * Injects mandatory parameters
     * 
     * @param string $componentName Component name
     */
    public function inject(string $componentName) {
        $this->componentName = $componentName;
    }

    /**
     * Returns options available
     * 
     * @return array Options
     */
    public function getOptions() {
        return $this->values;
    }

    public function output(): HTML {
        $el = HTML::el('select');
        $el->text($this->prepareOptionsCode());

        return $el;
    }

    /**
     * Generates options code
     * 
     * @return string Options code
     */
    private function prepareOptionsCode() {
        $options = [];
        foreach($this->values as $key => $value) {
            $options[] = '<option value="' . $key . '">' . $value . '</option>';
        }

        return implode('', $options);
    }
}

?>