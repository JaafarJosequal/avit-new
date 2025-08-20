<?php
declare(strict_types=1);

namespace Josequal\APIMobile\Api\V1;

/**
 * Account Deletion Interface
 */
interface AccountDeletionInterface
{
    /**
     * Request account deletion
     *
     * @param string|null $reason
     * @return \Josequal\APIMobile\Api\Data\ApiResponseInterface
     */
    public function requestAccountDeletion(?string $reason = null);

    /**
     * Cancel account deletion request
     *
     * @return \Josequal\APIMobile\Api\Data\ApiResponseInterface
     */
    public function cancelAccountDeletion();

    /**
     * Get deletion status for current customer
     *
     * @return \Josequal\APIMobile\Api\Data\ApiResponseInterface
     */
    public function getDeletionStatus();
}
