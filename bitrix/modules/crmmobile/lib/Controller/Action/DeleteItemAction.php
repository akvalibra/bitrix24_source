<?php

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\Main\Result;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Kanban\Entity;

class DeleteItemAction extends Action
{

	public function run(int $id, string $entityType, array $params = [])
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$result = $this->delete($id, $entityType, $params);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	private function delete(int $id, string $entityType, array $params = []): Result
	{
		$params = $this->getPreparedParams($params);
		return Entity::getInstance($entityType)->deleteItem($id, $params);
	}

	private function getPreparedParams(array $params = []): array
	{
		if (empty($params['eventId']))
		{
			return [];
		}

		return [
			'eventId' => $params['eventId'],
		];
	}
}