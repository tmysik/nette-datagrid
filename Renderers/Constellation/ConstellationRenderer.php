<?php

namespace DataGrid\Renderers;

use Nette,
    DataGrid,
    Nette\Utils\Arrays,
    Nette\Utils\Html,
    Nette\Utils\Strings,
    DataGrid\Columns,
    DataGrid\Action;

/**
 * Converts a data grid into the HTML output.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class ConstellationRenderer extends Nette\Object implements IRenderer {

    /** @var array  of HTML tags */
    public $wrappers = array(
        'row.content' => array(
            'container' => 'tr', // .even, .selected
            '.even' => 'even',
            'cell' => array(
                'container' => 'td', // .checker, .action
            ),
        ),
        'row.footer' => array(
            'container' => 'tr class=footer',
            'cell' => array(
                'container' => 'td',
            ),
        ),
        'paginator' => array(
            'container' => 'span class=paginator',
            'button' => array(
                'first' => 'span class="paginator-first"',
                'prev' => 'span class="paginator-prev"',
                'next' => 'span class="paginator-next"',
                'last' => 'span class="paginator-last"',
            ),
            'controls' => array(
                'container' => 'span class=paginator-controls',
            ),
        ),
        'operations' => array(
            'container' => 'span class=operations',
        ),
        'info' => array(
            'container' => 'ul class="message no-margin"',
            'item' => array(
                'container' => 'li',
            ),
        ),
    );
    /** @var string */
    public $footerFormat = '%operations% %paginator% %info%';
    /** @var string */
    public $paginatorFormat = '%label% %input% of %count%';
    /** @var string */
    public $infoFormat = 'Items %from% - %to% out of %count%';
    /** @var string  template file */
    public $file;
    /** @var DataGrid\DataGrid */
    protected $dataGrid;
    /** @var array  of function(Nette\Web\Html $row, DibiRow $data) */
    public $onRowRender;
    /** @var array  of function(Nette\Web\Html $cell, string $column, mixed $value) */
    public $onCellRender;
    /** @var array  of function(Nette\Web\Html $action, DibiRow $data) */
    public $onActionRender;

    /**
     * Data grid renderer constructor.
     * @return void
     */
    public function __construct() {
        $this->file = __DIR__.'/grid.phtml';
    }

    /**
     * Provides complete datagrid rendering.
     * @param  DataGrid\DataGrid
     * @param  string
     * @return string
     */
    public function render(DataGrid\DataGrid $dataGrid, $mode = NULL) {
        if ($this->dataGrid !== $dataGrid) {
            $this->dataGrid = $dataGrid;
        }

        if (!$dataGrid->dataSource instanceof DataGrid\DataSources\IDataSource) {
            throw new Nette\InvalidStateException('Data source is not instance of IDataSource. '.gettype($this->dataSource).' given.');
        }

        if ($mode !== NULL) {
            return call_user_func_array(array($this, 'render'.$mode), array());
        }

        $template = $this->dataGrid->getTemplate();
        $template->setFile($this->file);
        return $template->__toString(TRUE);
    }

    /**
     * Renders datagrid form begin.
     * @return string
     */
    public function renderFormBegin() {
        $form = $this->dataGrid->getForm(TRUE);
        foreach ($form->getControls() as $control) {
            $control->setOption('rendered', FALSE);
        }
        $form->getElementPrototype()->addClass('form datagrid');
        return $form->getElementPrototype()->startTag();
    }

    /**
     * Renders datagrid form end.
     * @return string
     */
    public function renderFormEnd() {
        $form = $this->dataGrid->getForm(TRUE);
        return $form->getElementPrototype()->endTag()."\n";
    }

    /**
     * Renders validation errors.
     * @return string
     */
    public function renderErrors() {
        $form = $this->dataGrid->getForm(TRUE);

        $errors = $form->getErrors();
        if (count($errors)) {
            $out = '';
            foreach ($errors as $error) {
                $container = Html::el('ul')->class('message error');
                $item = Html::el('li');
                if ($error instanceof Html) {
                    $item->add($error);
                } else {
                    $item->setText($error);
                }
                $container->add($item);
                // $container->add(Html::el('li')->class('close-bt'));
                $out .= $container->render(0);
            }
            return "\n" . $out;
        }
        return '';
    }

    /**
     * Renders data grid paginator.
     * @return string
     */
    public function renderNavigator() {
        $container = Html::el('div')
                ->class('block-controls');
        return $container->setHtml($this->renderPaginator())->render();
    }

    public function renderPaginator() {
        $paginator = $this->dataGrid->paginator;
        if ($paginator->pageCount <= 1) {
            return Html::el('p')->setHtml('&nbsp;')->render();
        }

        $container = $this->getWrapper('paginator container');
        $translator = $this->dataGrid->getTranslator();

        $a = Html::el('a');
        $a->addClass(Action::$ajaxClass);

        // to-first button
        $first = $this->getWrapper('paginator button first');
        $title = $this->dataGrid->translate('First');
        $link = clone $a->href($this->dataGrid->link('page', 1));
        if ($first instanceof Html) {
            if ($paginator->isFirst())
                $first->addClass('inactive');
            else
                $first = $link->add($first);
            $first->title($title);
        } else {
            $first = $link->setText($title);
        }
        $container->add($first);

        // previous button
        $prev = $this->getWrapper('paginator button prev');
        $title = $this->dataGrid->translate('Previous');
        $link = clone $a->href($this->dataGrid->link('page', $paginator->page - 1));
        if ($prev instanceof Html) {
            if ($paginator->isFirst())
                $prev->addClass('inactive');
            else
                $prev = $link->add($prev);
            $prev->title($title);
        } else {
            $prev = $link->setText($title);
        }
        $container->add($prev);

        // page input
        $controls = $this->getWrapper('paginator controls container');
        $form = $this->dataGrid->getForm(TRUE);
        $format = $this->dataGrid->translate($this->paginatorFormat);
        $html = str_replace(
                array('%label%', '%input%', '%count%'), array($form['page']->label, $form['page']->control, $paginator->pageCount), $format
        );
        $controls->add(Html::el()->setHtml($html));
        $container->add($controls);

        // next button
        $next = $this->getWrapper('paginator button next');
        $title = $this->dataGrid->translate('Next');
        $link = clone $a->href($this->dataGrid->link('page', $paginator->page + 1));
        if ($next instanceof Html) {
            if ($paginator->isLast())
                $next->addClass('inactive');
            else
                $next = $link->add($next);
            $next->title($title);
        } else {
            $next = $link->setText($title);
        }
        $container->add($next);

        // to-last button
        $last = $this->getWrapper('paginator button last');
        $title = $this->dataGrid->translate('Last');
        $link = clone $a->href($this->dataGrid->link('page', $paginator->pageCount));
        if ($last instanceof Html) {
            if ($paginator->isLast())
                $last->addClass('inactive');
            else
                $last = $link->add($last);
            $last->title($title);
        } else {
            $last = $link->setText($title);
        }
        $container->add($last);

        // page change submit
        $control = $form['pageSubmit']->control;
        $control->title = $control->value;
        $container->add($control);

        unset($first, $prev, $next, $last, $button, $paginator, $link, $a, $form);
        return $container->render();
    }

    /**
     * Renders data grid body.
     * @return string
     */
    public function renderTable() {
        $container = Html::el('table')
                        ->class('table datagrid')
                        ->cellspacing('0');

        // headers
        $header = Html::el('thead');
        $header->add($this->generateHeaderRow());

        if ($this->dataGrid->hasFilters()) {
            $header->add($this->generateFilterRow());
        }

        // body
        $body = Html::el('tbody');

        if ($this->dataGrid->paginator->itemCount) {
            $iterator = new Nette\Iterators\CachingIterator($this->dataGrid->getRows());
            foreach ($iterator as $data) {
                $row = $this->generateContentRow($data);
                $row->addClass($iterator->isEven() ? $this->getValue('row.content .even') : NULL);
                $body->add($row);
            }
        } else {
            $size = count($this->dataGrid->getColumns());
            if ($this->dataGrid->hasOperations()) {
                ++$size;
            }
            $row = $this->getWrapper('row.content container');
            $cell = $this->getWrapper('row.content cell container');
            $cell->colspan = $size;
            $cell->style = 'text-align:center';
            $cell->add(Html::el('div')->setText($this->dataGrid->translate('No data were found')));
            $row->add($cell);
            $body->add($row);
        }

        $container->add($header);
        $container->add($body);
        return Html::el('div')
                ->class('no-margin')
                ->add($container)
                ->render(0);
    }

    /**
     * Renders info about data grid.
     * @return string
     */
    public function renderInfo() {
        $container = $this->getWrapper('info container');
        $item = $this->getWrapper('info item container');
        $paginator = $this->dataGrid->paginator;
        $form = $this->dataGrid->getForm(TRUE);

        $stateSubmit = $form['resetSubmit']->control;
        $stateSubmit->title($stateSubmit->value);

        $this->infoFormat = $this->dataGrid->translate($this->infoFormat);
        $html = str_replace(
                array(
            '%from%',
            '%to%',
            '%count%',
                ), array(
            $paginator->itemCount != 0 ? $paginator->offset + 1 : $paginator->offset,
            $paginator->offset + $paginator->length,
            $paginator->itemCount,
                ), $this->infoFormat
        );

        $container->add($item->setHtml(trim($html)));
        return $container->render();
    }

    /**
     * Renders data grid operation controls.
     * @return string
     */
    public function renderOperations() {
        if (!$this->dataGrid->hasOperations())
            return '';

        $container = $this->getWrapper('operations container');
        $form = $this->dataGrid->getForm(TRUE);
        $container->add($form['operations']->label);
        $container->add($form['operations']->control);
        $container->add($form['operationSubmit']->control->title($form['operationSubmit']->control->value));

        return $container->render();
    }

    /**
     * Generates datagrid headrer.
     * @return Html
     */
    protected function generateHeaderRow() {
        $row = Html::el('tr');

        // checker
        if ($this->dataGrid->hasOperations()) {
            $cell = Html::el('th')
                    ->class('black-cell')
                    ->add(Html::el('span')
                            ->class('loading'));

            if ($this->dataGrid->hasFilters()) {
                $cell->rowspan(2);
            }
            $row->add($cell);
        }

        // headers
        foreach ($this->dataGrid->getColumns() as $column) {
            $cell = Html::el('th')->scope('col');
            $value = $text = $column->caption;

            if ($column->isOrderable()) {
                $i = 1;
                parse_str($this->dataGrid->order, $list);
                foreach ($list as $field => $dir) {
                    $list[$field] = array($dir, $i++);
                }

                if (isset($list[$column->getName()])) {
                    $a = $list[$column->getName()][0] === 'a';
                    $d = $list[$column->getName()][0] === 'd';
                } else {
                    $a = $d = FALSE;
                }

                // sort element
                $sort = Html::el('span')
                        ->class('column-sort');
                $linkUp = Html::el('a')
                        ->title($this->dataGrid->translate('Sort up'))
                        ->class('sort-up')
                        ->addClass(Columns\Column::$ajaxClass)
                        ->addClass($a ? 'active' : '')
                        ->href($column->getOrderLink('a'));
                $linkDown = Html::el('a')
                        ->title($this->dataGrid->translate('Sort down'))
                        ->class('sort-down')
                        ->addClass(Columns\Column::$ajaxClass)
                        ->addClass($d ? 'active' : '')
                        ->href($column->getOrderLink('d'));

                // NB - <ENTER> after sort -> indentation 0 and not 8
                $value = $sort
                        ->add($linkUp)
                        ->add($linkDown)
                        ->render() . $value;
            } else {
                if ($column instanceof Columns\ActionColumn) {
                    $value = trim($value.' '.$this->generateActions($cell, $this->dataGrid->getGlobalActions()));
                } else {
                    $value = $value;
                }
            }
            $cell->setHtml($value);

            $row->add($cell);
        }

        return $row;
    }

    /**
     * Generates datagrid filter.
     * @return Html
     */
    protected function generateFilterRow() {
        $row = Html::el('tr')->class('filters');
        $form = $this->dataGrid->getForm(TRUE);

        $submitControl = $form['filterSubmit']->control;
        $submitControl->addClass('button');
        $submitControl->title = $submitControl->value;

        foreach ($this->dataGrid->getColumns() as $column) {
            $cell = Html::el('td');

            // TODO: set on filters too?
            $cell->attrs = $column->getCellPrototype()->attrs;

            if ($column instanceof Columns\ActionColumn) {
                $value = (string) $submitControl;
                $cell->addClass('actions');
            } else {
                if ($column->hasFilter()) {
                    $filter = $column->getFilter();
                    if ($filter instanceof Filters\SelectboxFilter) {
                        $class = 'select';
                    } else {
                        $class = 'text';
                    }
                    $control = $filter->getFormControl()->control;
                    $control->addClass($class)->addClass('full-width');
                    $value = (string) $control;
                } else {
                    $value = '';
                }
            }

            $cell->setHtml($value);
            $row->add($cell);
        }

        if (!$this->dataGrid->hasActions()) {
            $submitControl->addStyle('display: none');
            $row->add($submitControl);
        }

        return $row;
    }

    /**
     * Generates datagrid row content.
     * @param  \Traversable|array data
     * @return Html
     */
    protected function generateContentRow($data) {
        $form = $this->dataGrid->getForm(TRUE);
        $row = $this->getWrapper('row.content container');

        if ($this->dataGrid->hasOperations() || $this->dataGrid->hasActions()) {
            $primary = $this->dataGrid->keyName;
            if (!isset($data[$primary])) {
                throw new \InvalidArgumentException("Invalid name of key for group operations or actions. Column '".$primary."' does not exist in data source.");
            }
        }

        // checker
        if ($this->dataGrid->hasOperations()) {
            $value = $form['checker'][$data[$primary]]->getControl();
            $cell = $this->getWrapper('row.content cell container')->setHtml((string) $value);
            $cell->addClass('checker');
            $row->add($cell);
        }

        // content
        foreach ($this->dataGrid->getColumns() as $column) {
            $cell = $this->getWrapper('row.content cell container');
            $cell->attrs = $column->getCellPrototype()->attrs;

            if ($column instanceof Columns\ActionColumn) {
                $value = $this->generateActions($cell, $this->dataGrid->getActions(), $data);
            } else {
                if (!array_key_exists($column->getName(), $data)) {
                    throw new \InvalidArgumentException("Non-existing column '".$column->getName()."' in datagrid '".$this->dataGrid->getName()."'");
                }
                $value = $column->formatContent($data[$column->getName()], $data);
            }

            $cell->setHtml((string) $value);
            $this->onCellRender($cell, $column->getName(), !($column instanceof Columns\ActionColumn) ? $data[$column->getName()] : $data);
            $row->add($cell);
        }
        unset($form, $primary, $cell, $value);
        $this->onRowRender($row, $data);
        return $row;
    }

    private function generateActions($cell, \ArrayIterator $actions, $data = null) {
        $value = '';
        $linkParams = array(
            $this->dataGrid->keyName => -1,
        );
        if ($data !== null) {
            $primary = $this->dataGrid->keyName;
            $linkParams = array(
                $primary => $data[$primary],
            );
        }
        foreach ($actions as $action) {
            $action->generateLink($linkParams);
            $html = clone $action->getHtml();
            $title = $this->dataGrid->translate($html->title);
            $html->title($title);
            $text = $html->getText();
            if (Strings::length($text)) {
                $text = $this->dataGrid->translate($text);
                $html->setText($text);
            }
            if (is_callable($action->ifDisableCallback) && callback($action->ifDisableCallback)->invokeArgs(array($data))) {
                // action disabled
                if ($text !== '') {
                    $html = Html::el('span')->setText($text);
                } else {
                    $html = Arrays::get($html->getChildren(), 0);
                }
                $html->title = $title;
            }
            $this->onActionRender($html, $data);
            $value .= $html->render().' ';
        }
        $cell->addClass('table-actions');
        return $value;
    }

    /**
     * Generates datagrid footer.
     * @return Html
     */
    protected function generateFooterRow() {
        $form = $this->dataGrid->getForm(TRUE);
        $paginator = $this->dataGrid->paginator;
        $row = $this->getWrapper('row.footer container');

        $count = count($this->dataGrid->getColumns());
        if ($this->dataGrid->hasOperations())
            $count++;

        $cell = $this->getWrapper('row.footer cell container');
        $cell->colspan($count);

        $this->footerFormat = $this->dataGrid->translate($this->footerFormat);
        $html = str_replace(
                array(
            '%operations%',
            '%paginator%',
            '%info%',
                ), array(
            $this->renderOperations(),
            $this->renderPaginator(),
            $this->renderInfo(),
                ), $this->footerFormat
        );
        $cell->setHtml($html);
        $row->add($cell);

        return $row;
    }

    /**
     * @param  string
     * @return Html
     */
    protected function getWrapper($name) {
        $data = $this->getValue($name);
        return $data instanceof Html ? clone $data : Html::el($data);
    }

    /**
     * @param  string
     * @return string
     */
    protected function getValue($name) {
        $name = explode(' ', $name);
        if (count($name) == 3) {
            $data = & $this->wrappers[$name[0]][$name[1]][$name[2]];
        } else {
            $data = & $this->wrappers[$name[0]][$name[1]];
        }
        return $data;
    }

    /**
     * Returns DataGrid.
     * @return DataGrid\DataGrid
     */
    public function getDataGrid() {
        return $this->dataGrid;
    }

}
