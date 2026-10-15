<?php
class Delivery {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
  public function getDeliveryByOrderID($order_id) {
      return $this->db->fetchOne("SELECT * FROM delivery_methods WHERE order_id = ?", [$order_id]);
  }
  public function updateDeliveryStatus($order_id, $status) {
      return $this->db->update('delivery_methods', ['status' => $status], 'order_id = ?', [$order_id]);
  }

  public function addTrackingNumber($order_id, $tracking_number) {
      return $this->db->update('delivery_methods', ['tracking_number' => $tracking_number], 'order_id = ?', [$order_id]);
  }
}
?>