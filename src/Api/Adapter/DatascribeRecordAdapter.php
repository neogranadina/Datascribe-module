<?php
namespace Datascribe\Api\Adapter;

use Datascribe\DatascribeDataType\Unknown;
use Datascribe\Entity\DatascribeRecord;
use Datascribe\Entity\DatascribeValue;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class DatascribeRecordAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [];

    public function getResourceName()
    {
        return 'datascribe_records';
    }

    public function getRepresentationClass()
    {
        return 'Datascribe\Api\Representation\DatascribeRecordRepresentation';
    }

    public function getEntityClass()
    {
        return 'Datascribe\Entity\DatascribeRecord';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['datascribe_item_id']) && is_numeric($query['datascribe_item_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.item', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['datascribe_item_id']))
            );
        }
        if (isset($query['needs_review'])) {
            if (in_array($query['needs_review'], [true, 1, '1'], true)) {
                $qb->andWhere($qb->expr()->eq('omeka_root.needsReview', 1));
            } elseif (in_array($query['needs_review'], [false, 0, '0'], true)) {
                $qb->andWhere($qb->expr()->eq('omeka_root.needsReview', 0));
            }
        }
        if (isset($query['needs_work'])) {
            if (in_array($query['needs_work'], [true, 1, '1'], true)) {
                $qb->andWhere($qb->expr()->eq('omeka_root.needsWork', 1));
            } elseif (in_array($query['needs_work'], [false, 0, '0'], true)) {
                $qb->andWhere($qb->expr()->eq('omeka_root.needsWork', 0));
            }
        }
        if (isset($query['created_by']) && is_numeric($query['created_by'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.createdBy',
                $this->createNamedParameter($qb, $query['created_by'])
            ));
        }
        if (isset($query['modified_by']) && is_numeric($query['modified_by'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.modifiedBy',
                $this->createNamedParameter($qb, $query['modified_by'])
            ));
        }
        if (isset($query['has_invalid_values'])) {
            $alias = $this->createAlias();
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select($alias)
                ->from('Datascribe\Entity\DatascribeValue', $alias)
                ->andWhere("$alias.record = omeka_root.id")
                ->andWhere($qb->expr()->eq("$alias.isInvalid", true));
            if (in_array($query['has_invalid_values'], [true, 1, '1'], true)) {
                $qb->andWhere($qb->expr()->exists($subQb->getDQL()));
            } elseif (in_array($query['has_invalid_values'], [false, 0, '0'], true)) {
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQb->getDQL())));
            }
        }
        if (isset($query['before_id']) && is_numeric($query['before_id'])) {
            $qb->andWhere($qb->expr()->lt('omeka_root.id', $query['before_id']));
            // Setting ORDER BY DESC here so a LIMIT won't cut off expected
            // rows. It's the consumer's responsibility to reverse the result
            // set if ORDER BY ASC is needed.
            $qb->orderBy('omeka_root.id', 'desc');
        } elseif (isset($query['after_id']) && is_numeric($query['after_id'])) {
            $qb->andWhere($qb->expr()->gt('omeka_root.id', $query['after_id']));
            $qb->orderBy('omeka_root.id', 'asc');
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()) {
            if (!isset($data['o-module-datascribe:item'])
                || !isset($data['o-module-datascribe:item']['o:id'])
                || !is_numeric($data['o-module-datascribe:item']['o:id'])
            ) {
                $errorStore->addError('o-module-datascribe:item', 'Invalid item format passed in request.'); // @translate
            }
        }
        if (isset($data['o:owner']) && !isset($data['o:owner']['o:id'])) {
            $errorStore->addError('o:owner', 'Invalid owner format passed in request.'); // @translate
        }
        if (isset($data['o-module-datascribe:value'])) {
            if (!is_array($data['o-module-datascribe:value'])) {
                $errorStore->addError('o-module-datascribe:value', 'Invalid values format passed in request.'); // @translate
            } else {
                foreach ($data['o-module-datascribe:value'] as $fieldId => $valueData) {
                    if (!isset($valueData['is_missing'])) {
                        $errorStore->addError('is_missing', sprintf('Invalid value format passed in request. Missing "is_missing" for field #%s.', $fieldId));
                    }
                    if (!isset($valueData['is_illegible'])) {
                        $errorStore->addError('is_illegible', sprintf('Invalid value format passed in request. Missing "is_illegible" for field #%s.', $fieldId));
                    }
                    if (!isset($valueData['data'])) {
                        $errorStore->addError('data', sprintf('Invalid value format passed in request. Missing "data" for field #%s.', $fieldId));
                    } elseif (!is_array($valueData['data'])) {
                        $errorStore->addError('data', sprintf('Invalid value format passed in request. Invalid "data" format for field #%s.', $fieldId));
                    }
                }
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $dataTypes = $services->get('Datascribe\DataTypeManager');
        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        $acl = $services->get('Omeka\Acl');

        $this->hydrateOwner($request, $entity);
        if (Request::CREATE === $request->getOperation()) {
            $itemData = $request->getValue('o-module-datascribe:item');
            $item = $this->getAdapter('datascribe_items')->findEntity($itemData['o:id']);
            $entity->setItem($item);
            $entity->setCreatedBy($user);
            $entity->setNeedsReview(false);
            $entity->setNeedsWork(false);
        } else {
            $entity->setModifiedBy($user);
            $entity->setModified(new DateTime('now'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:transcriber_notes') && $acl->userIsAllowed($entity->getItem(), 'datascribe_edit_transcriber_notes')) {
            $entity->setTranscriberNotes($request->getValue('o-module-datascribe:transcriber_notes'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:reviewer_notes') && $acl->userIsAllowed($entity->getItem(), 'datascribe_edit_reviewer_notes')) {
            $entity->setReviewerNotes($request->getValue('o-module-datascribe:reviewer_notes'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:needs_review') && $acl->userIsAllowed($entity->getItem(), 'datascribe_flag_record_needs_review')) {
            $entity->setNeedsReview($request->getValue('o-module-datascribe:needs_review'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:needs_work') && $acl->userIsAllowed($entity->getItem(), 'datascribe_flag_record_needs_work')) {
            $entity->setNeedsWork($request->getValue('o-module-datascribe:needs_work'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:value')) {
            $values = $entity->getValues();
            $valuesToRetain = new ArrayCollection;
            foreach ($request->getValue('o-module-datascribe:value') as $fieldId => $valueData) {
                $field = $em->getReference('Datascribe\Entity\DatascribeField', $fieldId);
                if ($values->containsKey($fieldId)) {
                    // This is an existing value.
                    $value = $values->get($field->getId());
                } else {
                    // This is a new value.
                    $value = new DatascribeValue;
                    $value->setField($field);
                    $value->setRecord($entity);
                    $values->set($fieldId, $value);
                }
                $isMissing = (bool) $valueData['is_missing'];
                $isIllegible = (bool) $valueData['is_illegible'];
                $value->setIsMissing($isMissing);
                $value->setIsIllegible($isIllegible);
                $value->setIsInvalid(false);
                $dataType = $dataTypes->get($field->getDataType());
                $valueText = $dataType->getValueTextFromUserData($valueData['data']);
                if ((null === $valueText) && $field->getIsRequired() && !$isMissing && !$isIllegible) {
                    // Null text is invalid if the field is required and the
                    // value is not missing and not illegible.
                    $value->setIsInvalid(true);
                }
                if (!($dataType instanceof Unknown)) {
                    // Set value text only when the data type is known.
                    $value->setText($valueText);
                }
                $valuesToRetain->add($value);
            }
            // Remove values not passed in the request.
            foreach ($values as $value) {
                if (!$valuesToRetain->contains($value)) {
                    $values->removeElement($value);
                }
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $services = $this->getServiceLocator();
        $dataTypes = $services->get('Datascribe\DataTypeManager');

        $item = $entity->getItem();
        if (null === $item) {
            $errorStore->addError('o-module-datascribe:item', 'Missing item.'); // @translate
        }
        $fields = $item->getDataset()->getFields();
        foreach ($entity->getValues() as $value) {
            $field = $value->getField();
            // Validate the field. It must be assigned to the item's dataset.
            if (!$fields->containsKey($field->getId())) {
                $errorStore->addError('data', 'Invalid field. Field not in dataset.'); // @translate
            }

            // Validate the value text. Null values should never raise an error.
            if (null !== $value->getText()) {
                $dataType = $dataTypes->get($field->getDataType());
                if (!$dataType->valueTextIsValid($field->getData(), $value->getText())) {
                    $errorStore->addError('data', sprintf('Invalid value text for field "%s".', $field->getName())); // @translate
                }
            }
        }
    }

    public function getInvalidValueCount(DatascribeRecord $record)
    {
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $dql = '
            SELECT COUNT(v.id)
            FROM Datascribe\Entity\DatascribeValue v
            WHERE v.record = :recordId
            AND v.isInvalid = true';
        $query = $em->createQuery($dql);
        $query->setParameter('recordId', $record->getId());
        return $query->getSingleScalarResult();
    }

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $data = parent::preprocessBatchUpdate($data, $request);
        $rawData = $request->getContent();
        if (in_array($rawData['needs_review_action'], [true, 1, '1'], true)) {
            $data['o-module-datascribe:needs_review'] = 1;
        } elseif (in_array($rawData['needs_review_action'], [false, 0, '0'], true)) {
            $data['o-module-datascribe:needs_review'] = 0;
        }
        if (in_array($rawData['needs_work_action'], [true, 1, '1'], true)) {
            $data['o-module-datascribe:needs_work'] = 1;
        } elseif (in_array($rawData['needs_work_action'], [false, 0, '0'], true)) {
            $data['o-module-datascribe:needs_work'] = 0;
        }
        return $data;
    }
}
