<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait Document
{
	private ?\Bitrix\DocumentGenerator\Document $document = null;

	public static function isActive(): bool
	{
		return DocumentGeneratorManager::getInstance()->isEnabled();
	}

	private function getDocument(): \Bitrix\DocumentGenerator\Document
	{
		if (!$this->document)
		{
			Loader::requireModule('documentgenerator');

			$this->document = \Bitrix\DocumentGenerator\Document::loadById($this->getDocumentId());
			if (!$this->document)
			{
				throw new ObjectNotFoundException('Could not find document with ID=' . $this->getDocumentId());
			}
		}

		return $this->document;
	}

	private function getDocumentId(): int
	{
		return (int)$this->getModel()->getAssociatedEntityId();
	}

	private function getOpenDocumentAction(): Layout\Action
	{
		return
			(new Layout\Action\JsEvent('Document:Open'))
				->addActionParamInt('documentId', $this->getDocumentId())
		;
	}
}