<?php
namespace Datascribe\Api\Adapter;

use Datascribe\Entity\DatascribeUser;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class DatascribeProjectAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'name' => 'name',
        'created' => 'created',
    ];

    public function getResourceName()
    {
        return 'datascribe_projects';
    }

    public function getRepresentationClass()
    {
        return 'Datascribe\Api\Representation\DatascribeProjectRepresentation';
    }

    public function getEntityClass()
    {
        return 'Datascribe\Entity\DatascribeProject';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['is_public'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.isPublic',
                $this->createNamedParameter($qb, (bool) $query['is_public'])
            ));
        }
        if (isset($query['owner_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.owner', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['has_user_id'])) {
            $alias = $this->createAlias();
            $qb->innerJoin('omeka_root.users', $alias);
            $qb->andWhere($qb->expr()->eq(
                "$alias.user",
                $this->createNamedParameter($qb, $query['has_user_id']))
            );
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['o:owner']) && !isset($data['o:owner']['o:id'])) {
            $errorStore->addError('o:owner', 'An owner must have an ID'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if ($this->shouldHydrate($request, 'o-module-datascribe:name')) {
            $entity->setName($request->getValue('o-module-datascribe:name'));
        }
        if ($this->shouldHydrate($request, 'o-module-datascribe:description')) {
            $entity->setDescription($request->getValue('o-module-datascribe:description'));
        }
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o-module-datascribe:user')) {
            $oUserAdapter = $this->getAdapter('users');
            $users = $entity->getUsers();
            $usersNew = $request->getValue('o-module-datascribe:user');
            $usersNew = is_array($usersNew) ? $usersNew : [];

            // Add users to the project.
            $usersToRetain = [];
            foreach ($usersNew as $userNew) {
                if (!isset($userNew['o:user']['o:id'])) {
                    continue;
                }
                if (!isset($userNew['o-module-datascribe:role'])) {
                    continue;
                }

                $oUser = $oUserAdapter->findEntity($userNew['o:user']['o:id']);
                // The $users collection is indexed by user_id.
                $user = $users->get($oUser->getId());
                if (!$user) {
                    $user = new DatascribeUser;
                    $user->setUser($oUser);
                    $user->setProject($entity);
                    $users->set($oUser->getId(), $user);
                }
                $user->setRole($userNew['o-module-datascribe:role']);
                $usersToRetain[] = $user;
            }

            // Remove users from the project.
            foreach ($users as $user) {
                if (!in_array($user, $usersToRetain)) {
                    $users->removeElement($user);
                }
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getName()) {
            $errorStore->addError('o-module-datascribe:name', 'A project name must not be null'); // @translate
        }
    }
}
