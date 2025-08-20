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

    public function requestAccountDeletion(?string $reason = null): ApiResponseInterface
    {
        return $this->accountDeletionService->requestAccountDeletion($reason);
    }

    public function cancelAccountDeletion(): ApiResponseInterface
    {
        return $this->accountDeletionService->cancelAccountDeletion();
    }

    public function getDeletionStatus(): ApiResponseInterface
    {
        return $this->accountDeletionService->getDeletionStatus();
    }
}
