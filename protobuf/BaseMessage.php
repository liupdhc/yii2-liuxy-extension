<?php
namespace yii\liuxy\protobuf;

/**
 * Auto generated from base_message.proto at 2015-12-14 15:51:58
 */

/**
 * base_message message
 */
class BaseMessage extends \ProtobufMessage
{
    /* Field index constants */
    const CODE = 1;
    const MSG = 2;
    const UKEY = 3;

    /* @var array Field descriptors */
    protected static $fields = array(
        self::CODE => array(
            'name' => 'code',
            'required' => true,
            'type' => 5,
        ),
        self::MSG => array(
            'name' => 'msg',
            'required' => false,
            'type' => 7,
        ),
        self::UKEY => array(
            'name' => 'ukey',
            'required' => false,
            'type' => 7,
        ),
    );

    /**
     * Constructs new message container and clears its internal state
     *
     * @return null
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Clears message values and sets default ones
     *
     * @return null
     */
    public function reset()
    {
        $this->values[self::CODE] = null;
        $this->values[self::MSG] = null;
        $this->values[self::UKEY] = null;
    }

    /**
     * Returns field descriptors
     *
     * @return array
     */
    public function fields()
    {
        return self::$fields;
    }

    /**
     * Sets value of 'code' property
     *
     * @param int $value Property value
     *
     * @return null
     */
    public function setCode($value)
    {
        return $this->set(self::CODE, $value);
    }

    /**
     * Returns value of 'code' property
     *
     * @return int
     */
    public function getCode()
    {
        return $this->get(self::CODE);
    }

    /**
     * Sets value of 'msg' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setMsg($value)
    {
        return $this->set(self::MSG, $value);
    }

    /**
     * Returns value of 'msg' property
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->get(self::MSG);
    }

    /**
     * Sets value of 'ukey' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setUkey($value)
    {
        return $this->set(self::UKEY, $value);
    }

    /**
     * Returns value of 'ukey' property
     *
     * @return string
     */
    public function getUkey()
    {
        return $this->get(self::UKEY);
    }
}
