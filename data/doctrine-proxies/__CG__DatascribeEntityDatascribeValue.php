<?php

namespace DoctrineProxies\__CG__\Datascribe\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class DatascribeValue extends \Datascribe\Entity\DatascribeValue implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = [];



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', 'field', 'record', 'isInvalid', 'isMissing', 'isIllegible', 'text', 'id'];
        }

        return ['__isInitialized__', 'field', 'record', 'isInvalid', 'isMissing', 'isIllegible', 'text', 'id'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (DatascribeValue $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function setField(\Datascribe\Entity\DatascribeField $field): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setField', [$field]);

        parent::setField($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getField(): \Datascribe\Entity\DatascribeField
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getField', []);

        return parent::getField();
    }

    /**
     * {@inheritDoc}
     */
    public function setRecord(\Datascribe\Entity\DatascribeRecord $record): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRecord', [$record]);

        parent::setRecord($record);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecord(): \Datascribe\Entity\DatascribeRecord
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRecord', []);

        return parent::getRecord();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsInvalid(bool $isInvalid): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsInvalid', [$isInvalid]);

        parent::setIsInvalid($isInvalid);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsInvalid(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsInvalid', []);

        return parent::getIsInvalid();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsMissing(bool $isMissing): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsMissing', [$isMissing]);

        parent::setIsMissing($isMissing);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsMissing(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsMissing', []);

        return parent::getIsMissing();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsIllegible(bool $isIllegible): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsIllegible', [$isIllegible]);

        parent::setIsIllegible($isIllegible);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsIllegible(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsIllegible', []);

        return parent::getIsIllegible();
    }

    /**
     * {@inheritDoc}
     */
    public function setText(?string $text): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setText', [$text]);

        parent::setText($text);
    }

    /**
     * {@inheritDoc}
     */
    public function getText(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getText', []);

        return parent::getText();
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getResourceId', []);

        return parent::getResourceId();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

}
