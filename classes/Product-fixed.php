<?php
class Product {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllProducts($limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.stock_quantity > 0";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        } else {
            return $this->db->fetchAll($sql);
        }
    }

    public function getProductById($id) {
        return $this->db->fetchOne(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.category_id
             WHERE p.product_id = ?", 
            [$id]
        );
    }

    public function getProductsByCategory($categoryId, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.category_id = ? AND p.stock_quantity > 0";
        
        $params = [$categoryId];
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getCategories() {
        return $this->db->fetchAll("SELECT * FROM categories ORDER BY name");
    }

    public function searchProducts($term, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.stock_quantity > 0";
        
        $params = ["%$term%", "%$term%"];
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getFeaturedProducts($limit = 8) {
        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.category_id
             WHERE p.is_featured = 1 AND p.stock_quantity > 0 
             ORDER BY p.created_at DESC 
             LIMIT ?", 
            [$limit]
        );
    }

    public function getLowStockProducts($threshold = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE stock_quantity <= ? AND stock_quantity > 0 
             ORDER BY stock_quantity ASC", 
            [$threshold]
        );
    }

    public function getTopSellingProducts($limit = 10) {
        // This would typically join with order_items table
        // For now, return featured products as top selling
        return $this->getFeaturedProducts($limit);
    }

    public function createProduct($data) {
        return $this->db->insert('products', [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'stock_quantity' => $data['stock_quantity'],
            'image_url' => $data['image_url'] ?? null,
            'sku' => $data['sku'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'is_featured' => $data['is_featured'] ?? 0
        ]);
    }

    public function updateProduct($id, $data) {
        return $this->db->update('products', $data, 'product_id = ?', [$id]);
    }

    public function deleteProduct($id) {
        return $this->db->delete('products', 'product_id = ?', [$id]);
    }

    public function updateStock($productId, $quantity) {
        return $this->db->query(
            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?",
            [$quantity, $productId]
        );
    }

    public function getProductCount() {
        return $this->db->count('products', 'stock_quantity > 0');
    }

    public function getCategoryProductCount($categoryId) {
        return $this->db->count('products', 'category_id = ? AND stock_quantity > 0', [$categoryId]);
    }

    public function getProductStatistics() {
        $stats = [];
        
        $stats['total_products'] = $this->db->count('products');
        $stats['active_products'] = $this->db->count('products', 'is_active = 1');
        $stats['in_stock_products'] = $this->db->count('products', 'stock_quantity > 0');
        $stats['low_stock_products'] = $this->db->count('products', 'stock_quantity <= 10 AND stock_quantity > 0');
        $stats['out_of_stock_products'] = $this->db->count('products', 'stock_quantity = 0');
        
        // Get total value
        $result = $this->db->fetchOne("SELECT SUM(price * stock_quantity) as total_value FROM products");
        $stats['total_inventory_value'] = $result['total_value'] ?? 0;
        
        return $stats;
    }
}
?>
