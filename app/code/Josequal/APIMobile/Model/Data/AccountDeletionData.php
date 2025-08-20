<?php
namespace Josequal\APIMobile\Model\Data;

use Josequal\APIMobile\Api\Data\AccountDeletionDataInterface;

class AccountDeletionData implements AccountDeletionDataInterface, \JsonSerializable
{
    private string $status = 'pending';
    private ?string $deletionRequestedAt = null;
    private ?string $scheduledDeletionAt = null;
    private ?int $daysRemaining = null;
    private ?string $reason = null;
    private ?string $cancelledAt = null;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $value): AccountDeletionDataInterface
    {
        $this->status = $value;
        return $this;
    }

    public function getDeletionRequestedAt(): ?string
    {
        return $this->deletionRequestedAt;
    }

    public function setDeletionRequestedAt(?string $value): AccountDeletionDataInterface
    {
        $this->deletionRequestedAt = $value;
        return $this;
    }

    public function getScheduledDeletionAt(): ?string
    {
        return $this->scheduledDeletionAt;
    }

    public function setScheduledDeletionAt(?string $value): AccountDeletionDataInterface
    {
        $this->scheduledDeletionAt = $value;
        return $this;
    }

    public function getDaysRemaining(): ?int
    {
        return $this->daysRemaining;
    }

    public function setDaysRemaining(?int $value): AccountDeletionDataInterface
    {
        $this->daysRemaining = $value;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $value): AccountDeletionDataInterface
    {
        $this->reason = $value;
        return $this;
    }

    public function getCancelledAt(): ?string
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?string $value): AccountDeletionDataInterface
    {
        $this->cancelledAt = $value;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'deletion_requested_at' => $this->getDeletionRequestedAt(),
            'scheduled_deletion_at' => $this->getScheduledDeletionAt(),
            'days_remaining' => $this->getDaysRemaining(),
            'reason' => $this->getReason(),
            'cancelled_at' => $this->getCancelledAt()
        ];
    }
}
