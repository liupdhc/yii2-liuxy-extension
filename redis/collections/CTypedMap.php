<?php
/**
 * CTypedMap represents a map whose items are of the certain type.
 *
 * CTypedMap extends {@link CMap} by making sure that the elements to be
 * added to the list is of certain class type.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package yii\liuxy\redis\collections
 * @since 1.0
 */
namespace yii\liuxy\redis\collections;

use yii\base\Exception;

class CTypedMap extends CMap {
    private $_type;

    /**
     * Constructor.
     * @param string $type class type
     */
    public function __construct ($type) {
        $this->_type = $type;
    }

    /**
     * Adds an item into the map.
     * This method overrides the parent implementation by
     * checking the item to be inserted is of certain type.
     * @param integer $index the specified position.
     * @param mixed $item new item
     * @throws \yii\base\Exception If the index specified exceeds the bound,
     * the map is read-only or the element is not of the expected type.
     */
    public function add ($index , $item) {
        if ($item instanceof $this->_type)
            parent::add ($index , $item);
        else
            throw new Exception(\Yii::t ('yii' , 'CTypedMap<{type}> can only hold objects of {type} class.' ,
                array('{type}' => $this->_type)));
    }
}
