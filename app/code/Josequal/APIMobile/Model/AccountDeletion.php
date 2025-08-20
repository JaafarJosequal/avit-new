<?php
namespace Josequal\APIMobile\Model;

use Josequal\APIMobile\Api\V1\AccountDeletionInterface;
use Josequal\APIMobile\Service\AccountDeletionService;
use Josequal\APIMobile\Api\Data\ApiResponseInterface;

class AccountDeletion implements AccountDeletionInterface
{
    private AccountDeletionService $accountDeletionService;

    public function __construct(AccountDeletionService $accountDeletionService)
    {
        $this->accountDeletionService = $accountDeletionService;
    }

    /**
     * Request account deletion
     *
     * @param string|null $reason
     * @return ApiResponseInterface
     */
    public function requestAccountDeletion(?string $reason = null): ApiResponseInterface
    {
        return $this->accountDeletionService->requestAccountDeletion($reason);
    }

    /**
     * Cancel account deletion request
     *
     * @return ApiResponseInterface
     */
    public function cancelAccountDeletion(): ApiResponseInterface
    {
        return $this->accountDeletionService->cancelAccountDeletion();
    }

    /**
     * Get deletion status for current customer
     *
     * @return ApiResponseInterface
     */
    public function getDeletionStatus(): ApiResponseInterface
    {
        return $this->accountDeletionService->getDeletionStatus();
    }
}
