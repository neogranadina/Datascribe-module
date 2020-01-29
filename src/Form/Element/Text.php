<?php
namespace Datascribe\Form\Element;

use Zend\Form\Element\Text as ZendText;
use Zend\InputFilter\InputProviderInterface;
use Zend\Validator;

class Text extends ZendText implements InputProviderInterface
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $fieldData = $this->getOption('datascribe_field_data');

        $attributes = [];
        $attributes['pattern'] = $fieldData['pattern'] ?? null;
        $attributes['minlength'] = $fieldData['minlength'] ?? null;
        $attributes['maxlength'] = $fieldData['maxlength'] ?? null;
        $attributes['placeholder'] = $fieldData['placeholder'] ?? null;

        $this->setAttributes(array_filter($attributes));
    }

    public function getValidators()
    {
        $fieldData = $this->getOption('datascribe_field_data');

        $validators = [];
        if (isset($fieldData['pattern'])) {
            $validator = new Validator\Regex(['pattern' => sprintf('/%s/', $fieldData['pattern'])]);
            $validators[] = $validator;
        }
        if (isset($fieldData['minlength'])) {
            $validator = new Validator\StringLength(['min' => $fieldData['minlength']]);
            $validators[] = $validator;
        }
        if (isset($fieldData['maxlength'])) {
            $validator = new Validator\StringLength(['max' => $fieldData['maxlength']]);
            $validators[] = $validator;
        }
        return $validators;
    }

    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => null,
            'validators' => $this->getValidators(),
            'filters' => [],
        ];
    }
}