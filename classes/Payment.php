<?php
class Payment {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    public function createPayment($order_id, $amount, $method, $transaction_id, $status = 'pending') {
        return $this->db->insert('payments', [
            'order_id' => $order_id,
            'amount' => $amount,
            'method' => $method,
            'transaction_id' => $transaction_id,
            'status' => $status
        ]);
    }

    public function processKpay($order_id, $amount, $transaction_id) {
        // Simulate Kpay payment processing
        // In a real application, you would integrate with Kpay's API here
        $payment_success = $this->simulatePayment('Kpay');
        // Create payment record in the database

        if ($payment_success) {
            return $this->createPayment($order_id, $amount, 'Kpay', $transaction_id, 'completed');
        } else {
           $this->createPayment($order_id, $amount, 'Kpay', $transaction_id, 'failed');
           return false;
        }
    }

    public function processAyapay($order_id, $amount, $transaction_id) {
        // Simulate Ayapay payment processing
        // In a real application, you would integrate with Ayapay's API here
        $payment_success = $this->simulatePayment('Ayapay');

        if ($payment_success) {
            return $this->createPayment($order_id, $amount, 'Ayapay', $transaction_id, 'completed');
        } else {
            $this->createPayment($order_id, $amount, 'Ayapay', $transaction_id, 'failed');
            return false;
        }
    }
    public function getPaymentByOrderID($order_id) {
        return $this->db->fetchOne("SELECT * FROM payments WHERE order_id = ?", [$order_id]);
    }
    private function simulatePayment($method) {
        // Simulate a payment success or failure
        // In a real application, this would involve API calls to the payment gateway
        return rand(1, 10) <= 9; // Randomly return true or false
    }
}
?>