<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class TaskTagTable
 *
 * Fields:
 * <ul>
 * <li> TAG_ID int mandatory
 * <li> TASK_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TaskTag_Query query()
 * @method static EO_TaskTag_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TaskTag_Result getById($id)
 * @method static EO_TaskTag_Result getList(array $parameters = [])
 * @method static EO_TaskTag_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection wakeUpCollection($rows)
 */

class TaskTagTable extends TaskDataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_task_tag';
	}

	public static function getRelationName(): string
	{
		return 'Bitrix\Tasks\Internals\Task\TaskTag';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'TAG_ID',
				[
					'primary' => true,
					'required' => true,
					'title' => Loc::getMessage('TASK_TAG_ENTITY_TAG_ID_FIELD'),
				]
			),
			new IntegerField(
				'TASK_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('TASK_TAG_ENTITY_TASK_ID_FIELD'),
				]
			),

			(new ReferenceField(
				'TASK',
				TaskTable::getEntity(),
				['this.TASK_ID' => 'ref.ID']
			))->configureJoinType(Join::TYPE_LEFT),

			(new ReferenceField(
				'TAG',
				LabelTable::getEntity(),
				['this.TAG_ID' => 'ref.ID']
			))->configureJoinType(Join::TYPE_LEFT),
		];
	}
}