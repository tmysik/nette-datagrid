<?php

namespace DataGrid;
use Nette;

/**
 * Representation of data grid action.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class Action extends Nette\ComponentModel\Component implements IAction
{
	/**#@+ special action key */
	const WITH_KEY		= TRUE;
	const WITHOUT_KEY	= FALSE;
	/**#@-*/

	/** @var Nette\Utils\Html  action element template */
	protected $html;

	/** @var string */
	static public $ajaxClass = 'datagrid-ajax';

	/** @var string */
	public $destination;

	/** @var bool|string */
	public $key;

	/** @var Nette\Callback|Closure */
	public $ifDisableCallback;

        private $linkArgs = array();
        
	/**
	 * Data grid action constructor.
	 * @note   for full ajax support, destination should not change module,
	 * @note   presenter or action and must be ended with exclamation mark (!)
	 *
	 * @param  string  textual title
	 * @param  string|array  textual link destination or link 
	 * @param  Nette\Utils\Html    element which is added to a generated link
	 * @param  bool    use ajax? (add class self::$ajaxClass into generated link)
	 * @param  mixed   generate link with argument? (if yes you can specify name of parameter
	 * 				   otherwise variable DataGrid\DataGrid::$keyName will be used and must be defined)
	 * @return void
	 */
	public function __construct($title, $destination, Nette\Utils\Html $icon = NULL, $useAjax = FALSE, $key = self::WITH_KEY)
	{
		parent::__construct();
		$this->key = $key;

                if (is_string($destination)) {
                    $this->destination = $destination;
                } elseif (is_array($destination)) {
                    $this->destination = $destination[0];                    
                    $this->linkArgs = array_slice($destination, 1);
                }        
                
		$a = Nette\Utils\Html::el('a')->title($title);
		if ($useAjax) $a->addClass(self::$ajaxClass);

		if ($icon !== NULL && $icon instanceof Nette\Utils\Html) {
			$a->add($icon);
		} else {
			$a->setText($title);
		}
		$this->html = $a;
	}


	/**
	 * Generates action's link. (use before data grid is going to be rendered)
	 * @return void
	 */
	public function generateLink(array $args = NULL)
	{
		$dataGrid = $this->lookup('DataGrid\DataGrid', TRUE);
		$control = $dataGrid->lookup('Nette\Application\UI\Control', TRUE);

		switch ($this->key) {
		case self::WITHOUT_KEY:
			$link = $control->link($this->destination); break;
		case self::WITH_KEY:
		default:
			$key = $this->key == NULL || is_bool($this->key) ? $dataGrid->keyName : $this->key;                    
                        $this->linkArgs[$key] = $args[$dataGrid->keyName];                    
                        $link = $control->link($this->destination, $this->linkArgs); break;                          
		}

		$this->html->href($link);
	}



	/********************* interface DataGrid\IAction *********************/



	/**
	 * Gets action element template.
	 * @return Nette\Utils\Html
	 */
	public function getHtml()
	{
		return $this->html;
	}

}