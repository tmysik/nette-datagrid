<?php

namespace DataGrid\DataSources\Doctrine;

use Nette, Doctrine, DataGrid,
	Doctrine\ORM\Query\Expr,
	DataGrid\DataSources\IDataSource,
	DataGrid\DataSources\DataSource;

/**
 * Base class for Doctrine2 based data sources
 * 
 * @author Michael Moravec
 * @author Štěpán Svoboda
 */
abstract class Mapped extends DataSource
{
	/**
	 * Column alias to raw resultset columns mapping
	 *
	 * @var array
	 */
	protected $mapping = array();


	/**
	 * Set columns mapping
	 *
	 * @param $mapping array
	 */
	public function setMapping(array $mapping)
	{
		$this->mapping = $mapping;
	}


	/**
	 * Does datasource have column of given name?
	 *
	 * @return boolean
	 */
	public function hasColumn($name)
	{
		return \array_key_exists($name, $this->mapping);
	}


	/**
	 * Get aliased column name list
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array_keys($this->mapping);
	}
}
