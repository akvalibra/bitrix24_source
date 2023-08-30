<?php
declare(strict_types=1);

namespace Rarus\Sms4b\Sendings\SendingInQueue\Message;

use Bitrix\Main\Localization\Loc;

class Status {
    /**
     * ��������� � ������� �� ��������, �� ����������
     */
    public const NOT_PROCESSED = 1;
    /**
     * ��������� �������� ��������
     */
    public const IN_PROCESS = 2;
    /**
     * ��������� ����������
     */
    public const PROCESSED = 3;

    private $status;

    /**
     * Status constructor.
     *
     * @param int $status
     */
    public function __construct(int $status)
    {
        $this->setStatus($status);
    }

    /**
     * @param int $status
     *
     * @throws \InvalidArgumentException
     */
    private function setStatus(int $status): void
    {
        if (!\in_array($status,
            [self::NOT_PROCESSED, self::IN_PROCESS, self::PROCESSED], true)) {
            throw new \InvalidArgumentException(Loc::getMessage('SMS4B_QUEUE_STATUS_ERROR'));
        }
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

}