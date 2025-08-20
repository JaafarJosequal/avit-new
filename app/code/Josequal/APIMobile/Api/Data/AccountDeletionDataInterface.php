<?php
declare(strict_types=1);

namespace Josequal\APIMobile\Api\Data;

/**
 * Account Deletion Data Interface
 */
interface AccountDeletionDataInterface
{
    /**
     * Get deletion status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set deletion status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * Get deletion requested date
     *
     * @return string|null
     */
    public function getDeletionRequestedAt(): ?string;

    /**
     * Set deletion requested date
     *
     * @param string|null $deletionRequestedAt
     * @return $this
     */
    public function setDeletionRequestedAt(?string $deletionRequestedAt);

    /**
     * Get scheduled deletion date
     *
     * @return string|null
     */
    public function getScheduledDeletionAt(): ?string;

    /**
     * Set scheduled deletion date
     *
     * @param string|null $scheduledDeletionAt
     * @return $this
     */
    public function setScheduledDeletionAt(?string $scheduledDeletionAt);

    /**
     * Get days remaining
     *
     * @return int|null
     */
    public function getDaysRemaining(): ?int;

    /**
     * Set days remaining
     *
     * @param int|null $daysRemaining
     * @return $this
     */
    public function setDaysRemaining(?int $daysRemaining);

    /**
     * Get deletion reason
     *
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * Set deletion reason
     *
     * @param string|null $reason
     * @return $this
     */
    public function setReason(?string $reason);

    /**
     * Get cancelled date
     *
     * @return string|null
     */
    public function getCancelledAt(): ?string;

    /**
     * Set cancelled date
     *
     * @param string|null $cancelledAt
     * @return $this
     */
    public function setCancelledAt(?string $cancelledAt);
}
