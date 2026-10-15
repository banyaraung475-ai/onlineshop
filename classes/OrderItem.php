<?php
class OrderItem {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all order items for a specific order
     * @param int $order_id
     * @return array
     */
    public function getItemsByOrderId($order_id) {
        return $this->db->fetchAll("
            SELECT oi.*, p.name, p.image_url 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ", [$order_id]);
    }

    /**
     * Add multiple items to an order
     * @param int $order_id
     * @param array $items Array of product items
     * @return bool
     */
    public function addItemsToOrder($order_id, $items) {
        $this->db->beginTransaction();
        
        try {
            foreach ($items as $item) {
                $this->db->insert('order_items', [
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error adding order items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order item details including product information
     * @param int $item_id
     * @return array|null
     */
    public function getItemDetails($item_id) {
        return $this->db->fetchOne("
            SELECT oi.*, p.name, p.description, p.image_url 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.item_id = ?
        ", [$item_id]);
    }

    /**
     * Get the total quantity of items in an order
     * @param int $order_id
     * @return int
     */
    public function getOrderItemCount($order_id) {
        $row = $this->db->fetchOne("
            SELECT SUM(quantity) as total_quantity 
            FROM order_items 
            WHERE order_id = ?
        ", [$order_id]);
        
        return $row['total_quantity'] ?? 0;
    }

    /**
     * Get the total value of items in an order
     * @param int $order_id
     * @return float
     */
    public function getOrderItemsTotal($order_id) {
        $row = $this->db->fetchOne("
            SELECT SUM(price * quantity) as items_total 
            FROM order_items 
            WHERE order_id = ?
        ", [$order_id]);
        
        return $row['items_total'] ?? 0.00;
    }
}