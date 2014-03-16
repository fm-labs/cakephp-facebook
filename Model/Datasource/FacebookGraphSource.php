<?php
//App::uses('FacebookAuthException','Facebook.Lib');
App::uses('DataSource', 'Model/Datasource');
App::uses('FacebookApi', 'Facebook.Lib');

class FacebookGraphSource extends DataSource {

/**
 * Base config
 *
 * @var array
 * @deprecated Use facebook config file instead
 */
	protected $_baseConfig = array(
		'appId' => '',
		'appSecret' => ''
	);

/**
 * @var FacebookApi
 */
	public $FB;

/**
 * The datasource configuration will be ignored.
 * Credentials will be read from the facebook configuration file
 *
 * @param array $config
 */
	public function __construct($config = array()) {
		$this->FB = FacebookApi::getInstance();
		parent::__construct($config);
	}

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * @param Model $model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @param integer $recursive Number of levels of association
 * @return mixed
 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		return false;
	}

/**
 * Update a record(s) in the datasource.
 *
 * @param Model $model Instance of the model class being updated
 * @param array $fields Array of fields to be updated
 * @param array $values Array of values to be update $fields to.
 * @param mixed $conditions
 * @return boolean Success
 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		return false;
	}

/**
 * Delete a record(s) in the datasource.
 *
 * @param Model $model The model class having record(s) deleted
 * @param mixed $id The conditions to use for deleting.
 * @return boolean Success
 */
	public function delete(Model $model, $id = null) {
		return false;
	}

/**
 * Query the Facebook Graph Api
 *
 * @param Model $model
 * @param $path
 * @param string $method GET/POST/DELETE
 * @param array $params
 * @return mixed
 */
	public function query(Model $model, $path, $method = 'GET', $params = array()) {
		if ($method === 'GET') {
			$path = $this->buildPath($path, $params);
			$params = array();
		}

		return $this->FB->api($path, $method, $params);
	}

/**
 * Apply filters to path
 *
 * @param $path
 * @param array $params
 * @return string
 */
	public function buildPath($path, $params = array()) {
		$filter = $this->_parseFilterParams($params);
		if ($filter) {
			$path .= '?' . $filter;
		}
		return $path;
	}

/**
 * Helper method to parse filter params
 *
 * @param array $params
 * @param int $level
 * @return string
 */
	protected function _parseFilterParams($params = array(), $level = 1) {
		$filter = array();

		foreach ($params as $param => $val) {
			switch($param) {
				case "fields":
					$_fields = array();
					foreach ($params['fields'] as $field => $nested) {
						if (is_numeric($field)) {
							$field = $nested;
							$nested = null;
						}
						if ($nested) {
							$field = $field . '.' . $this->_parseFilterParams($nested, $level + 1);
						}
						$_fields[] = $field;
					}
					$filter['fields'] = join(',', $_fields);
					break;

				default:
					$filter[$param] = $val;
					break;
			}
		}

		if ($level === 1) {
			$_filter = array();
			foreach ($filter as $f => $fv) {
				$_filter[] = sprintf("%s=%s", $f, $fv);
			}
			$filterStr = join('&', $_filter);
		} else {
			$_filter = array();
			foreach ($filter as $f => $fv) {
				$_filter[] = sprintf("%s(%s)", $f, $fv);
			}
			$filterStr = join('.', $_filter);
		}
		return $filterStr;
	}

	public function listSources($data = null) {
		return null;
	}

	public function describe(Model $model) {
		//@todo Implement me
		return null;
	}
}