<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\Mapping\Type;

use Exception;
use OpenDxp\Model\DataObject;

class ClassificationStoreDataTypeService
{
    /**
     * @var TransformationDataTypeService
     */
    protected $transformationDataTypeService;

    public function __construct(TransformationDataTypeService $transformationDataTypeService)
    {
        $this->transformationDataTypeService = $transformationDataTypeService;
    }

    public function listClassificationStoreKeyList(string $classId, string $fieldName, string $transformationResultType, string $orderKey = 'name', string $order = 'ASC', int $start = 0, int $limit = 15, ?string $searchString = null, ?string $filterString = null): DataObject\Classificationstore\KeyGroupRelation\Listing
    {
        $classDefinition = DataObject\ClassDefinition::getById($classId);
        $field = $classDefinition->getFieldDefinition($fieldName);
        if ($field instanceof DataObject\ClassDefinition\Data\Classificationstore) {
            $storeId = $field->getStoreId();
        } else {
            throw new Exception("Invalid field, `$fieldName` is not a classification store");
        }

        $mapping = [
            'groupName' => DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS .'.name',
            'keyName' => DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS .'.name',
            'keyDescription' => DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS. '.description',
        ];

        if ($orderKey == 'keyName') {
            $orderKey = 'name';
        }

        $list = new DataObject\Classificationstore\KeyGroupRelation\Listing();
        $list->setLimit($limit);
        $list->setOffset($start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $conditionParts = [];

        if ($filterString) {
            $filters = json_decode($filterString);
            $count = 0;
            foreach ($filters as $f) {
                $count++;
                $fieldname = $mapping[$f->property];
                $conditionParts[] = $fieldname . ' LIKE ' . $list->quote('%' . $f->value . '%');
            }
        }

        $conditionParts[] = '  groupId IN (select id from classificationstore_groups where storeId = ' . $list->quote($storeId) . ')';

        if ($searchString) {
            $conditionParts[] = '('
                . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.name LIKE ' . $list->quote('%' . $searchString . '%')
                . ' OR ' . DataObject\Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.name LIKE ' . $list->quote('%' . $searchString . '%')
                . ' OR ' . DataObject\Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.description LIKE ' . $list->quote('%' . $searchString . '%') . ')';
        }

        if ($transformationResultType) {
            $opendxpTypes = $this->transformationDataTypeService->getOpenDxpTypesByTransformationTargetType($transformationResultType);
            if (!empty($opendxpTypes)) {
                //                $conditionParts[] = '';
                $list->addConditionParam(sprintf('type IN (%s)', "'" . implode("','", $opendxpTypes) . "'"));
                //                $list->addConditionParam('type IN (?)', $opendxpTypes);
            }
        }

        $condition = implode(' AND ', $conditionParts);
        $list->setCondition($condition);
        $list->setResolveGroupName(true);

        return $list;
    }
}
