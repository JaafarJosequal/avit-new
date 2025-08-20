<?php
namespace Josequal\APIMobile\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Cron job to process account deletions after 90 days
 */
class ProcessAccountDeletion
{
    protected ResourceConnection $resourceConnection;
    protected CustomerRepositoryInterface $customerRepository;
    protected Monolog $logger;
    protected DateTime $dateTime;

    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerRepositoryInterface $customerRepository,
        Monolog $logger,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute the cron job
     */
    public function execute()
    {
        try {
            $this->logger->info('Starting account deletion processing cron job');

            $accountsToDelete = $this->getAccountsToDelete();

            if (empty($accountsToDelete)) {
                $this->logger->info('No accounts to delete');
                return;
            }

            $this->logger->info('Found ' . count($accountsToDelete) . ' accounts to delete');

            foreach ($accountsToDelete as $account) {
                $this->processAccountDeletion($account);
            }

            $this->logger->info('Account deletion processing completed');

        } catch (\Exception $e) {
            $this->logger->error('Error in account deletion cron job: ' . $e->getMessage());
        }
    }

    /**
     * Get accounts that are ready for deletion
     */
    private function getAccountsToDelete(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $select = $connection->select()
            ->from($table, ['*'])
            ->where('status = ?', 1) // Pending status
            ->where('scheduled_deletion_at <= ?', $this->dateTime->date('Y-m-d H:i:s'));

        return $connection->fetchAll($select);
    }

    /**
     * Process deletion for a single account
     */
    private function processAccountDeletion(array $account): void
    {
        try {
            $customerId = $account['customer_id'];

            $this->logger->info("Processing deletion for customer ID: {$customerId}");

            // Actually delete the customer
            $this->deleteCustomer($customerId);

            // Update deletion record
            $this->updateDeletionRecord($account['entity_id']);

            $this->logger->info("Successfully deleted customer ID: {$customerId}");

        } catch (\Exception $e) {
            $this->logger->error("Error deleting customer ID {$account['customer_id']}: " . $e->getMessage());
        }
    }

    /**
     * Delete customer from Magento
     */
    private function deleteCustomer(int $customerId): void
    {
        try {
            $this->customerRepository->deleteById($customerId);
        } catch (\Exception $e) {
            $this->logger->error("Error deleting customer {$customerId} from Magento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update deletion record as completed
     */
    private function updateDeletionRecord(int $entityId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_account_deletion');

        $data = [
            'status' => 3, // Completed
            'deleted_at' => $this->dateTime->date('Y-m-d H:i:s')
        ];

        $connection->update($table, $data, ['entity_id = ?' => $entityId]);
    }
}
