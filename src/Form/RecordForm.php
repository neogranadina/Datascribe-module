<?php
namespace Datascribe\Form;

use Datascribe\Api\Representation\DatascribeDatasetRepresentation;
use Zend\Form\Element;
use Zend\Form\Form;
use Zend\Form\Fieldset;

class RecordForm extends Form
{
    public function init()
    {
        $this->addCommonElements();
        $this->addValueElements();
    }

    /**
     * Add all elements that are common to all records.
     */
    protected function addCommonElements()
    {
        $record = $this->getOption('record');

        // Add "needs_review" element.
        $element = new Element\Checkbox('o-module-datascribe:needs_review');
        $element->setLabel('Needs review'); // @translate
        $element->setAttributes([
            'required' => false,
            'value' => $record ? $record->needsReview() : null,
        ]);
        $this->add($element);

        // Add "needs_work" element.
        $element = new Element\Checkbox('o-module-datascribe:needs_work');
        $element->setLabel('Needs work'); // @translate
        $element->setAttributes([
            'required' => false,
            'value' => $record ? $record->needsWork() : null,
        ]);
        $this->add($element);

        // Add "transcriber_notes" element.
        $element = new Element\Textarea('o-module-datascribe:transcriber_notes');
        $element->setLabel('Transcriber notes'); // @translate
        $element->setAttributes([
            'required' => false,
            'value' => $record ? $record->transcriberNotes() : null,
        ]);
        $this->add($element);

        // Add "reviewer_notes" element.
        $element = new Element\Textarea('o-module-datascribe:reviewer_notes');
        $element->setLabel('Reviewer notes'); // @translate
        $element->setAttributes([
            'required' => false,
            'value' => $record ? $record->reviewerNotes() : null,
        ]);
        $this->add($element);
    }

    /**
     * Add all value elements configured for this dataset.
     */
    protected function addValueElements()
    {
        $manager = $this->getOption('data_type_manager');
        $dataset = $this->getOption('dataset');
        $record = $this->getOption('record');

        $valuesFieldset = new Fieldset('o-module-datascribe:value');
        $this->add($valuesFieldset);
        foreach ($dataset->fields() as $field) {
            $dataType = $manager->get($field->getDataType());

            $valueFieldset = new Fieldset($field->getId());
            $valueFieldset->setLabel($field->getLabel());
            $valueFieldset->setOption('info', $field->getInfo());
            $valuesFieldset->add($valueFieldset);
            $valueDataFieldset = new Fieldset('data');
            $valueFieldset->add($valueDataFieldset);

            $value = null;
            $valueData = [];
            if ($record) {
                $values = $record->getValues();
                if ($values->containsKey($field->getId())) {
                    $value = $values->get($field->getId());
                    $valueData = $value->getData();
                }
            }

            // Add the custom "data" elements.
            $dataType->addValueDataElements(
                $valueDataFieldset,
                $field->getLabel(),
                $field->getInfo(),
                $field->getData(),
                $valueData
            );

            // Add the common "is_missing" element.
            $element = new Element\Checkbox('is_missing');
            $element->setLabel('Is missing'); // @translate
            $element->setAttributes([
                'required' => false,
                'value' => $value ? $value->getIsMissing() : null,
            ]);
            $valueFieldset->add($element);

            // Add the common "is_illegible" element.
            $element = new Element\Checkbox('is_illegible');
            $element->setLabel('Is illegible'); // @translate
            $element->setAttributes([
                'required' => false,
                'value' => $value ? $value->getIsIllegible() : null,
            ]);
            $valueFieldset->add($element);
        }
    }
}
