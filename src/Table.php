<?php
namespace Vda\Query;

use Vda\Query\Key\ForeignKey;
use Vda\Query\Key\PrimaryKey;

abstract class Table implements IFieldList, IQueryPart, ISource
{
    /**
     * @var PrimaryKey
     */
    public $_primaryKey;

    protected $_schema;

    private $_name;
    private $_alias;

    /**
     * @var Field[]
     */
    private $_fields;
    private $_foreignKeys;

    protected function __construct($name, $alias, $forceInit = true)
    {
        $this->_name = $name;
        $this->_alias = $alias;

        if ($forceInit) {
            $this->loadFields(true);
        }
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        if (is_null($this->_fields)) {
            $this->loadFields(false);
        }

        return array_values($this->_fields);
    }

    public function getField($name)
    {
        return $this->{$name};
    }

    public function getForeignKeys()
    {
        if (is_null($this->_foreignKeys)) {
            $this->loadFields(false);
        }

        return $this->_foreignKeys;
    }

    public function getForeignKey($refTableClass)
    {
        if (is_null($this->_foreignKeys)) {
            $this->loadFields(false);
        }

        return isset($this->_foreignKeys[$refTableClass])
            ? $this->_foreignKeys[$refTableClass]
            : null;
    }

    public function getSchema()
    {
        return $this->_schema;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function onProcess(IQueryProcessor $processor)
    {
        return $processor->processTable($this);
    }

    private function loadFields($init)
    {
        $rc = new \ReflectionClass($this);
        
        $this->_foreignKeys = array();

        foreach ($rc->getProperties() as $p) {
            if (!$p->isPublic() || $p->isStatic()) {
                continue;
            }

            $this->registerProperty($p->getValue($this), $p->getName(), $init);
        }
    }

    private function registerProperty($property, $name, $init)
    {
        if (is_array($property)) {
            foreach ($property as $k => $v) {
                $this->registerProperty($v, $name . '.' . $k, $init);
            }
        } elseif ($property instanceof ForeignKey) {
            $this->registerForeignKey($property, $init);
        } elseif ($property instanceof Field) {
            $this->registerField($property, $name, $init);
        }
    }

    private function registerField(Field $field, $name, $init)
    {
        if ($init) {
            $field->init($name, $this);
        }

        $this->_fields[] = $field;
    }

    private function registerForeignKey(ForeignKey $key, $init)
    {
        if ($init) {
            $key->init($this);
        }

        $this->_foreignKeys[$key->getTargetClass()] = $key;
    }
}
