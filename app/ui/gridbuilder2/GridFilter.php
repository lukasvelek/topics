<?php

namespace App\UI\GridBuilder2;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;
use App\UI\FormBuilder\FormBuilder;

/**
 * GridFilter class represents a filter modal
 * 
 * @author Lukas Velek
 */
class GridFilter extends AComponent {
    /**
     * @var array<string, Filter> $filters
     */
    private array $filters;
    private string $gridComponentName;
    private array $gridColumns;
    private array $activeFilters;
    private array $queryDependencies;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param array $cfg Application configuration
     */
    public function __construct(HttpRequest $request, array $cfg) {
        parent::__construct($request, $cfg);

        $this->filters = [];
        $this->activeFilters = [];
        $this->queryDependencies = [];
    }

    /**
     * Sets the grid query dependencies
     * 
     * @param array $queryDependencies Grid query dependencies
     */
    public function setQueryDependencies(array $queryDependencies) {
        $this->queryDependencies = $queryDependencies;
    }

    /**
     * Sets the grid component name
     * 
     * @param string $gridComponentName Grid component name
     */
    public function setGridComponentName(string $gridComponentName) {
        $this->gridComponentName = $gridComponentName;
    }

    /**
     * Sets all filters available for grid
     * 
     * @param array $filters Filters available
     */
    public function setFilters(array $filters) {
        $this->filters = $filters;
    }
    
    /**
     * Sets grid columns
     * 
     * @param array $columns Grid columns
     */
    public function setGridColumns(array $columns) {
        $this->gridColumns = $columns;
    }

    /**
     * Sets active filters
     * 
     * @param array $activeFilters Active filters
     */
    public function setActiveFilters(array $activeFilters) {
        $this->activeFilters = $activeFilters;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/grid-filter.html');

        $template->form = $this->createForm();
        $template->scripts = $this->createScripts();
        $template->close_button = $this->createCloseButton();

        return $template->render()->getRenderedContent();
    }

    /**
     * Creates code for a modal close button
     * 
     * @return string Modal close button HTML code
     */
    private function createCloseButton() {
        return '<a class="grid-link" href="#" onclick="' . $this->componentName . '_closeModal()">&times;</a>';
    }

    /**
     * Creates code for all JS scripts
     * 
     * @return string HTML code for JS scripts
     */
    private function createScripts() {
        $scripts = [];
        
        $script = '
            <script type="text/javascript">
                async function ' . $this->componentName . '_submit() {
        ';

        $args = [];
        foreach($this->filters as $name => $filter) {
            $script .= 'const _' . $name . ' = $("#' . $name . '").val();';
            $args[] = '_' . $name;
        }
        foreach($this->queryDependencies as $k => $v) {
            $args[] = '\'' . $v . '\'';
        }

        $script .= '
                    await ' . $this->gridComponentName . '_filter(' . implode(', ', $args) . ');
                }
            </script>';

        $scripts[] = $script;

        $scripts[] = '
            <script type="text/javascript">
                function ' . $this->componentName . '_closeModal() {
                   $("#grid-filter-modal-inner").css("visibility", "hidden")
                        .css("height", "0px");
                }
            </script>
        ';

        return implode('', $scripts);
    }

    /**
     * Creates a modal form
     * 
     * @return FormBuilder FormBuilder instance
     */
    private function createForm() {
        $form = new FormBuilder();

        $form->setMethod();

        $this->processFilters($form);

        $form->addButton('Submit', $this->componentName . '_submit()', 'formSubmit');

        return $form;
    }

    /**
     * Processes filters
     * 
     * @param FormBuilder $form FormBuilder instance
     */
    private function processFilters(FormBuilder &$form) {
        foreach($this->filters as $name => $filter) {
            $filterOptions = $filter->getOptions();

            $options = [
                [
                'value' => 'null',
                'text' => '-'
                ]
            ];
            foreach($filterOptions as $key => $value) {
                $option = [
                    'value' => $key,
                    'text' => $value
                ];

                if(!empty($this->activeFilters)) {
                    if(array_key_exists($name, $this->activeFilters)) {
                        if($key == $this->activeFilters[$name]) {
                            $option['selected'] = 'selected';
                        }
                    }
                }

                $options[] = $option;
            }

            $form->addSelect($name, $this->gridColumns[$name], $options);
        }
    }
    
    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest, $component->cfg);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);

        return $obj;
    }
}

?>