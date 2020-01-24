<?php
namespace Datascribe\Api\Adapter;

use Datascribe\DatascribeDataType\Fallback;
use Datascribe\Entity\DatascribeField;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class DatascribeDatasetAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'name' => 'name',
        'created' => 'created',
    ];

    public function getResourceName()
    {
        return 'datascribe_datasets';
    }

    public function getRepresentationClass()
    {
        return 'Datascribe\Api\Representation\DatascribeDatasetRepresentation';
    }

    public function getEntityClass()
    {
        return 'Datascribe\Entity\DatascribeDataset';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['datascribe_project_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.project', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['datascribe_project_id']))
            );
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()) {
            if (!isset($data['o-module-datascribe:project'])) {
                $errorStore->addError('o-module-datascribe:project', 'A dataset must have a project'); // @translate
            } elseif (!isset($data['o-module-datascribe:project']['o:id'])) {
                $errorStore->addError('o-module-datascribe:project', 'A project must have an ID'); // @translate
            }
        }
        if (isset($data['o:item_set']) && !isset($data['o:item_set']['o:id'])) {
            $errorStore->addError('o:item_set', 'An item set must have an ID'); // @translate
        }
        if (isset($data['o:owner']) && !isset($data['o:owner']['o:id'])) {
            $errorStore->addError('o:owner', 'An owner must have an ID'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o-module-datascribe:name')) {
            $entity->setName($request->getValue('o-module-datascribe:name'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:description')) {
            $entity->setDescription($request->getValue('o-module-datascribe:description'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:guidelines')) {
            $htmlPurifier = $this->getServiceLocator()->get('Omeka\HtmlPurifier');
            $entity->setGuidelines($htmlPurifier->purify($request->getValue('o-module-datascribe:guidelines')));
        }
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }
        if (Request::CREATE === $request->getOperation()) {
            $projectData = $request->getValue('o-module-datascribe:project');
            $project = $this->getAdapter('datascribe_projects')->findEntity($projectData['o:id']);
            $entity->setProject($project);
        }
        if ($this->shouldHydrate($request, 'o:item_set')) {
            $itemSet = $request->getValue('o:item_set');
            if ($itemSet) {
                $itemSet = $this->getAdapter('item_sets')->findEntity($itemSet['o:id']);
            }
            $entity->setItemSet($itemSet);
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:field')) {
            $this->hydrateFields($request, $entity, $errorStore);
        }
    }

    protected function hydrateFields(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $dataTypes = $this->getServiceLocator()->get('Datascribe\DataTypeManager');
        $fields = $entity->getFields();
        $fieldsToRetain = [];
        $position = 1;

        // Update existing fields and create new fields.
        foreach ($request->getValue('o-module-datascribe:field', []) as $fieldData) {
            if (isset($fieldData['o:id']) && $fields->containsKey($fieldData['o:id'])) {
                // This field exists. Update it.
                $field = $fields->get($fieldData['o:id']);
                $fieldsToRetain[] = $field->getId();
            } elseif (isset($fieldData['o-module-datascribe:data_type']) && $dataTypes->has($fieldData['o-module-datascribe:data_type'])) {
                // This is a new field. Create it.
                $field = new DatascribeField;
                $field->setDataset($entity);
                $field->setDataType($fieldData['o-module-datascribe:data_type']);
                $fields->add($field);
            } else {
                // This field is in an invalid format. Ignore it.
                continue;
            }

            $label =
                (isset($fieldData['o-module-datascribe:label']) && preg_match('/^.+$/', $fieldData['o-module-datascribe:label']))
                ? $fieldData['o-module-datascribe:label'] : null;
            $info =
                (isset($fieldData['o-module-datascribe:info']) && preg_match('/^.+$/', $fieldData['o-module-datascribe:info']))
                ? $fieldData['o-module-datascribe:info'] : null;

            $field->setLabel($label);
            $field->setInfo($info);
            $field->setIsPrimary($fieldData['o-module-datascribe:is_primary'] ?? false);
            $field->setPosition($position++);
            $dataType = $dataTypes->get($field->getDataType());
            if (!($dataType instanceof Fallback)) {
                $field->setData($dataType->getFieldData($fieldData['data']));
            }
        }

        // Remove fields not passed in the request.
        foreach ($fields as $field) {
            if ($field->getId() && !in_array($field->getId(), $fieldsToRetain)) {
                $fields->removeElement($field);
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$this->isUnique($entity, ['name' => $entity->getName()])) {
            $errorStore->addError('o-module-datascribe:name', new Message(
                'The name "%s" is already taken.', // @translate
                $entity->getName()
            ));
        }
        if (null === $entity->getName()) {
            $errorStore->addError('o-module-datascribe:name', 'A dataset name must not be null'); // @translate
        }
        if (null === $entity->getProject()) {
            $errorStore->addError('o-module-datascribe:project', 'A project must not be null'); // @translate
        }
    }
}
