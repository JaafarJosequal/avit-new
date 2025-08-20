<?php
namespace Josequal\APIMobile\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\Webapi\Rest\Request;
use Josequal\APIMobile\Api\Data\ApiResponseInterface;
use Josequal\APIMobile\Api\Data\ApiResponseInterfaceFactory;
use Josequal\APIMobile\Api\Data\AccountDeletionDataInterface;
use Josequal\APIMobile\Model\Data\AccountDeletionData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class AccountDeletionService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected CustomerSession $customerSession;
    protected TokenFactory $tokenFactory;
    protected Request $request;
    protected ApiResponseInterfaceFactory $responseFactory;
    protected ResourceConnection $resourceConnection;
    protected DateTime $dateTime;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        TokenFactory $tokenFactory,
        Request $request,
        ApiResponseInterfaceFactory $responseFactory,
        ResourceConnection $resourceConnection,
        DateTime $dateTime
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->tokenFactory = $tokenFactory;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
    }

    /**
     * Request account deletion
     */
    public function requestAccountDeletion(?string $reason = null): ApiResponseInterface
    {
        try {
            $customerId = $this->validateToken();
            if (!$customerId) {
                return $this->buildResponse(false, 'Invalid or expired token', null, 401);
            }

            // Check if deletion already requested
            if ($this->isDeletionRequested($customerId)) {
                return $this->buildResponse(false, 'Account deletion already requested', null, 400);
            }

            // Create deletion request
            $this->createDeletionRequest($customerId, $reason);

            // Disable customer account
            $this->disableCustomerAccount($customerId);

            // Create structured data object
            $deletionData = $this->createDeletionData(
                $this->dateTime->date('Y-m-d H:i:s'),
                90,
                $reason
            );

            return $this->buildResponse(true, 'Account deletion requested successfully. Account will be deleted in 90 days.', $deletionData);

        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Error requesting account deletion: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Cancel account deletion request
     */
    public function cancelAccountDeletion(): ApiResponseInterface
    {
        try {
            $customerId = $this->validateToken();
            if (!$customerId) {
                return $this->buildResponse(false, 'Invalid or expired token', null, 401);
            }

            if (!$this->isDeletionRequested($customerId)) {
                return $this->buildResponse(false, 'No deletion request found for this account', null, 400);
            }

            // Cancel deletion request
            $this->cancelDeletionRequest($customerId);

            // Re-enable customer account
            $this->enableCustomerAccount($customerId);

            return $this->buildResponse(true, 'Account deletion cancelled successfully. Account is now active again.', null);

        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Error cancelling account deletion: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get deletion status
     */
    public function getDeletionStatus(): ApiResponseInterface
    {
        try {
            $customerId = $this->validateToken();
            if (!$customerId) {
                return $this->buildResponse(false, 'Invalid or expired token', null, 401);
            }

            $deletionData = $this->getDeletionData($customerId);

            if (!$deletionData) {
                return $this->buildResponse(true, 'No deletion request found', [
                    'status' => 'active',
                    'deletion_requested' => false
                ]);
            }

            $daysRemaining = $this->calculateDaysRemaining($deletionData['scheduled_deletion_at']);

            // Create structured data object
            $statusData = new AccountDeletionData();
            $statusData->setStatus($deletionData['status'] == 1 ? 'pending' : ($deletionData['status'] == 2 ? 'cancelled' : 'completed'));
            $statusData->setDeletionRequestedAt($deletionData['deletion_requested_at']);
            $statusData->setScheduledDeletionAt($deletionData['scheduled_deletion_at']);
            $statusData->setDaysRemaining($daysRemaining);
            $statusData->setReason($deletionData['reason']);

            return $this->buildResponse(true, 'Deletion status retrieved successfully', $statusData);

        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Error retrieving deletion status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Validate authentication token
     */
    private function validateToken(): ?int
    {
        $authorization = $this->request->getHeader('Authorization');
        if (!$authorization) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authorization);

        // Try to decode JWT token first
        try {
            $payload = $this->decodeJwtToken($token);
            if ($payload && isset($payload['uid'])) {
                return (int) $payload['uid'];
            }
        } catch (\Exception $e) {
            // Fallback to oauth token
        }

        // Fallback to oauth token validation
        $tokenModel = $this->tokenFactory->create();
        $tokenModel->load($token, 'token');

        if (!$tokenModel->getId() || $tokenModel->getRevoked()) {
            return null;
        }

        return (int) $tokenModel->getCustomerId();
    }

    /**
     * Decode JWT token
     */
    private function decodeJwtToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if deletion is already requested
     */
    private function isDeletionRequested(int $customerId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $select = $connection->select()
            ->from($table, ['entity_id'])
            ->where('customer_id = ?', $customerId)
            ->where('status = ?', 1); // Pending status

        return (bool) $connection->fetchOne($select);
    }

    /**
     * Create deletion request
     */
    private function createDeletionRequest(int $customerId, ?string $reason): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $data = [
            'customer_id' => $customerId,
            'deletion_requested_at' => $this->dateTime->date('Y-m-d H:i:s'),
            'scheduled_deletion_at' => $this->dateTime->date('Y-m-d H:i:s', strtotime('+90 days')),
            'status' => 1, // Pending
            'reason' => $reason
        ];

        $connection->insert($table, $data);
    }

    /**
     * Cancel deletion request
     */
    private function cancelDeletionRequest(int $customerId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $data = [
            'status' => 2, // Cancelled
            'cancelled_at' => $this->dateTime->date('Y-m-d H:i:s')
        ];

        $connection->update($table, $data, ['customer_id = ?' => $customerId]);
    }

    /**
     * Get deletion data
     */
    private function getDeletionData(int $customerId): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $select = $connection->select()
            ->from($table, ['*'])
            ->where('customer_id = ?', $customerId)
            ->where('status IN (?)', [1, 2]) // Pending or Cancelled
            ->order('entity_id DESC')
            ->limit(1);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Calculate days remaining
     */
    private function calculateDaysRemaining(string $scheduledDate): int
    {
        $scheduled = strtotime($scheduledDate);
        $now = time();
        $diff = $scheduled - $now;

        return max(0, (int) ceil($diff / (24 * 60 * 60)));
    }

    /**
     * Disable customer account
     */
    private function disableCustomerAccount(int $customerId): void
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('account_disabled', '1');
            $customer->setCustomAttribute('disabled_reason', 'Account deletion requested');
            $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            // Log error but don't fail the request
        }
    }

    /**
     * Enable customer account
     */
    private function enableCustomerAccount(int $customerId): void
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('account_disabled', '0');
            $customer->setCustomAttribute('disabled_reason', null);
            $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            // Log error but don't fail the request
        }
    }

        /**
     * Build API response
     */
    private function buildResponse(bool $success, string $message, $data = null, int $statusCode = 200): ApiResponseInterface
    {
        $response = $this->responseFactory->create();
        $response->setStatus($success);
        $response->setMessage($message);
        $response->setDataField($data);
        $response->setCode($statusCode);

        return $response;
    }

    private function createDeletionData(string $deletionDate, int $daysRemaining, ?string $reason = null): AccountDeletionDataInterface
    {
        $deletionData = new AccountDeletionData();
        $deletionData->setStatus('pending');
        $deletionData->setDeletionRequestedAt($deletionDate);
        $deletionData->setScheduledDeletionAt($this->dateTime->date('Y-m-d H:i:s', strtotime('+90 days')));
        $deletionData->setDaysRemaining($daysRemaining);
        $deletionData->setReason($reason);

        return $deletionData;
    }
}
