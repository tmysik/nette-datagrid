<?php

namespace DataGrid\Columns;

/**
 * Representation of date data grid column.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class DateColumn extends TextColumn
{
	/** @var string */
	public $format;


	/**
	 * Date column constructor.
	 * @param  string  column's textual caption
	 * @param  string  date format supported by PHP date()
	 * @return void
	 */
	public function __construct($caption = NULL, $format = '%x')
	{
		parent::__construct($caption);
		$this->format = $format;
	}


	/**
	 * Formats cell's content.
	 * @param  mixed
	 * @param  \DibiRow|array
	 * @return string
	 */
	public function formatContent($value, $data = NULL)
	{
		if (is_object($value)) {
            if ($value == NULL) {
                return $this->getDataGrid()->translate('N/A');
            }
        } elseif ((int)$value == NULL || empty($value)) {
            return $this->getDataGrid()->translate('N/A');
        }
		$value = parent::formatContent($value, $data);

		$value = is_numeric($value) ? (int) $value : ($value instanceof \DateTime ? $value->format('U') : strtotime($value));
		return date($this->format, $value);
	}


	/**
	 * Applies filtering on dataset.
	 * @param  mixed
	 * @return void
	 */
	public function applyFilter($value)
	{
		if (! $this->hasFilter()) return;

		$this->getDataGrid()->getDataSource()->filter($this->name, '=', $value);
	}
}