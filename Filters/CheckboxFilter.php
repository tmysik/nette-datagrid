<?php

namespace DataGrid\Filters;
use Nette, DataGrid;

/**
 * Representation of data grid column checkbox filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class CheckboxFilter extends ColumnFilter
{
	/**
	 * Returns filter's form element.
	 * @return Nette\Forms\Controls\BaseControl
	 */
	public function getFormControl()
	{
		if ($this->element instanceof Nette\Forms\Controls\BaseControl) return $this->element;
		$element = new Nette\Forms\Controls\Checkbox($this->getName());

		return $this->element = $element;
	}
}