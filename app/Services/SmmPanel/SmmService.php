<?php

namespace App\Services\SmmPanel;

use Illuminate\Support\Facades\Http;
use Exception;

class SmmService
{
    private string $apiUrl;
    private string $apiKey;
    private int $timeout;

    /**
     * Initialize the service with provider credentials
     */
    public function __construct(string $apiUrl, string $apiKey, int $timeout = 30)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Make HTTP request to SMM API
     */
    public function makeRequest(array $data, bool $asArray = false)
    {
        // Add API key to all requests
        $data['key'] = $this->apiKey;

        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->withoutVerifying() // For SSL issues (like in the original code)
                ->post($this->apiUrl, $data);

            if ($response->failed()) {
                throw new Exception('API request failed: HTTP ' . $response->status());
            }

            $result = $asArray ? $response->json() : $response->object();

            // Check for API-level errors
            if (is_object($result) && isset($result->error)) {
                throw new Exception('API Error: ' . $result->error);
            }

            if (is_array($result) && isset($result['error'])) {
                throw new Exception('API Error: ' . $result['error']);
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception('SMM API Request Failed: ' . $e->getMessage());
        }
    }

    /**
     * Get all available services
     *
     * @return array List of services
     */
    public function getServices(): array
    {
        return $this->makeRequest([
            'action' => 'services',
        ], true);
    }

    /**
     * Get provider balance
     *
     * @return object {balance: string, currency: string}
     */
    public function getBalance(): object
    {
        return $this->makeRequest([
            'action' => 'balance',
        ]);
    }

    /**
     * Create a new order
     *
     * @param array $orderData Order parameters
     * @return object {order: int}
     */
    public function createOrder(array $orderData): object
    {
        $data = array_merge(['action' => 'add'], $orderData);
        return $this->makeRequest($data);
    }

    /**
     * Get single order status
     *
     * @param string|int $orderId
     * @return object {charge: string, start_count: string, status: string, remains: string, currency: string}
     */
    public function getOrderStatus($orderId): object
    {
        return $this->makeRequest([
            'action' => 'status',
            'order' => $orderId,
        ]);
    }

    /**
     * Get multiple orders status
     *
     * @param array $orderIds Array of order IDs
     * @return array Associative array with order_id as key
     */
    public function getMultipleOrdersStatus(array $orderIds): array
    {
        return $this->makeRequest([
            'action' => 'status',
            'orders' => implode(',', $orderIds),
        ], true);
    }

    /**
     * Refill single order
     *
     * @param string|int $orderId
     * @return object {refill: int}
     */
    public function refillOrder($orderId): object
    {
        return $this->makeRequest([
            'action' => 'refill',
            'order' => $orderId,
        ]);
    }

    /**
     * Refill multiple orders
     *
     * @param array $orderIds
     * @return array [{order: int, refill: int|object}, ...]
     */
    public function refillMultipleOrders(array $orderIds): array
    {
        return $this->makeRequest([
            'action' => 'refill',
            'orders' => implode(',', $orderIds),
        ], true);
    }

    /**
     * Get refill status
     *
     * @param string|int $refillId
     * @return object {status: string}
     */
    public function getRefillStatus($refillId): object
    {
        return $this->makeRequest([
            'action' => 'refill_status',
            'refill' => $refillId,
        ]);
    }

    /**
     * Get multiple refill statuses
     *
     * @param array $refillIds
     * @return array [{refill: int, status: string|object}, ...]
     */
    public function getMultipleRefillStatuses(array $refillIds): array
    {
        return $this->makeRequest([
            'action' => 'refill_status',
            'refills' => implode(',', $refillIds),
        ], true);
    }

    /**
     * Cancel multiple orders
     *
     * @param array $orderIds
     * @return array [{order: int, cancel: int|object}, ...]
     */
    public function cancelOrders(array $orderIds): array
    {
        return $this->makeRequest([
            'action' => 'cancel',
            'orders' => implode(',', $orderIds),
        ], true);
    }

    // ============================================
    // HELPER METHODS FOR SPECIFIC ORDER TYPES
    // ============================================

    /**
     * Create a default order (followers, likes, views, etc.)
     */
    public function createDefaultOrder(int $serviceId, string $link, int $quantity, array $extras = []): object
    {
        return $this->createOrder(array_merge([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
        ], $extras));
    }

    /**
     * Create a drip-feed order
     */
    public function createDripFeedOrder(int $serviceId, string $link, int $quantity, int $runs, int $interval): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'runs' => $runs,
            'interval' => $interval,
        ]);
    }

    /**
     * Create a custom comments order
     */
    public function createCustomCommentsOrder(int $serviceId, string $link, string $comments): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'comments' => $comments,
        ]);
    }

    /**
     * Create a package order
     */
    public function createPackageOrder(int $serviceId, string $link): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
        ]);
    }

    /**
     * Create a subscription order
     */
    public function createSubscriptionOrder(
        int $serviceId,
        string $username,
        int $min,
        int $max,
        int $posts,
        int $delay,
        string $expiry,
        ?int $oldPosts = null
    ): object {
        $data = [
            'service' => $serviceId,
            'username' => $username,
            'min' => $min,
            'max' => $max,
            'posts' => $posts,
            'delay' => $delay,
            'expiry' => $expiry,
        ];

        if ($oldPosts !== null) {
            $data['old_posts'] = $oldPosts;
        }

        return $this->createOrder($data);
    }

    /**
     * Create a comment likes order
     */
    public function createCommentLikesOrder(int $serviceId, string $link, int $quantity, string $username): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'username' => $username,
        ]);
    }

    /**
     * Create a poll order
     */
    public function createPollOrder(int $serviceId, string $link, int $quantity, string $answerNumber): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'answer_number' => $answerNumber,
        ]);
    }

    /**
     * Create a comment replies order
     */
    public function createCommentRepliesOrder(int $serviceId, string $link, string $username, string $comments): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'username' => $username,
            'comments' => $comments,
        ]);
    }

    /**
     * Create a mentions custom list order
     */
    public function createMentionsOrder(int $serviceId, string $link, string $usernames): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'usernames' => $usernames,
        ]);
    }

    /**
     * Create an invites from groups order
     */
    public function createInvitesOrder(int $serviceId, string $link, int $quantity, string $groups): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'groups' => $groups,
        ]);
    }

    /**
     * Create an SEO order
     */
    public function createSeoOrder(int $serviceId, string $link, int $quantity, string $keywords): object
    {
        return $this->createOrder([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'keywords' => $keywords,
        ]);
    }

    /**
     * Create a web traffic order
     */
    public function createWebTrafficOrder(
        int $serviceId,
        string $link,
        int $quantity,
        int $runs,
        int $interval,
        array $options = []
    ): object {
        return $this->createOrder(array_merge([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'runs' => $runs,
            'interval' => $interval,
        ], $options));
    }
}


// ============================================
// USAGE EXAMPLES
// ============================================

/*

// Initialize for BulkMedya
$bulkmedya = new SmmService(
    'https://bulkmedya.org/api/v2',
    'your_api_key_here'
);

// Initialize for TopSMM
$topsmm = new SmmService(
    'https://topsmm.uz/api/v2',
    'your_api_key_here'
);

// ============================================
// BASIC OPERATIONS
// ============================================

// Get all services
$services = $bulkmedya->getServices();
// Returns: [
//     {service: 1, name: "Followers", type: "Default", rate: "0.90", ...},
//     {service: 2, name: "Likes", type: "Default", rate: "0.50", ...},
// ]

// Get balance
$balance = $bulkmedya->getBalance();
// Returns: {balance: "100.84292", currency: "USD"}

// ============================================
// CREATE ORDERS (DIFFERENT TYPES)
// ============================================

// 1. Default Order (followers, likes, views)
$order = $bulkmedya->createDefaultOrder(
    serviceId: 1,
    link: 'https://instagram.com/p/abc123',
    quantity: 1000
);
// Returns: {order: 23501}

// 2. Drip-feed Order
$order = $bulkmedya->createDripFeedOrder(
    serviceId: 1,
    link: 'https://instagram.com/p/abc123',
    quantity: 1000,
    runs: 10,
    interval: 60
);

// 3. Custom Comments
$order = $bulkmedya->createCustomCommentsOrder(
    serviceId: 2,
    link: 'https://instagram.com/p/abc123',
    comments: "Nice post!\nGreat content\nAmazing! 😍"
);

// 4. Package Order
$order = $bulkmedya->createPackageOrder(
    serviceId: 3,
    link: 'https://instagram.com/username'
);

// 5. Poll Order
$order = $topsmm->createPollOrder(
    serviceId: 5,
    link: 'https://instagram.com/p/abc123',
    quantity: 100,
    answerNumber: '1'
);

// ============================================
// CHECK ORDER STATUS
// ============================================

// Single order
$status = $bulkmedya->getOrderStatus(23501);
// Returns: {
//     charge: "0.90",
//     start_count: "1000",
//     status: "Completed",
//     remains: "0",
//     currency: "USD"
// }

// Multiple orders
$statuses = $bulkmedya->getMultipleOrdersStatus([23501, 23502, 23503]);
// Returns: {
//     "23501": {charge: "0.90", status: "Completed", ...},
//     "23502": {charge: "1.50", status: "In progress", ...},
//     "23503": {error: "Incorrect order ID"}
// }

// ============================================
// REFILL & CANCEL
// ============================================

// Refill single order
$refill = $bulkmedya->refillOrder(23501);
// Returns: {refill: 1}

// Refill multiple orders
$refills = $bulkmedya->refillMultipleOrders([23501, 23502]);
// Returns: [
//     {order: 23501, refill: 1},
//     {order: 23502, refill: 2}
// ]

// Check refill status
$refillStatus = $bulkmedya->getRefillStatus(1);
// Returns: {status: "Completed"}

// Cancel orders
$cancels = $bulkmedya->cancelOrders([23501, 23502]);
// Returns: [
//     {order: 23501, cancel: 1},
//     {order: 23502, cancel: {error: "Cannot cancel completed order"}}
// ]

// ============================================
// ERROR HANDLING
// ============================================

try {
    $order = $bulkmedya->createDefaultOrder(1, 'invalid-link', 1000);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    // Output: "SMM API Request Failed: API Error: Invalid link format"
}

// ============================================
// ADVANCED: Generic Order Creation
// ============================================

// For any custom order type not covered by helper methods
$order = $bulkmedya->createOrder([
    'service' => 10,
    'link' => 'https://example.com/post',
    'quantity' => 500,
    'custom_param' => 'value',
    'another_param' => 'another_value',
]);

*/
