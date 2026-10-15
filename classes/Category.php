<?php
class Category {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all main categories (parent_id IS NULL)
     */
    public function getMainCategories() {
        return $this->db->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
    }
    
    /**
     * Get subcategories for a specific parent category
     */
    public function getSubCategories($parentId, $limit = null) {
        $query = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC";
        $params = [$parentId];

        if ($limit !== null) {
            $query .= " LIMIT ?";
            $params[] = (int)$limit;
        }

        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Get all categories with hierarchy
     */
    public function getCategoryTree() {
        $mainCategories = $this->getMainCategories();
        
        foreach ($mainCategories as &$category) {
            $category['subcategories'] = $this->getSubCategories($category['id']);
        }
        
        return $mainCategories;
    }
    
    /**
     * Get category by slug
     */
    public function getCategoryBySlug($slug) {
        return $this->db->fetchOne("SELECT * FROM categories WHERE slug = ?", [$slug]);
    }
    
    /**
     * Get products count for a category
     */
    public function getProductCount($categoryId) {
        return $this->db->count('products', 'category_id = ?', [$categoryId]);
    }
    
    /**
     * Get featured categories
     */
    public function getFeaturedCategories($limit = 6) {
        $query = "SELECT c.* FROM categories c 
                 JOIN products p ON p.category_id = c.id 
                 GROUP BY c.id 
                 ORDER BY COUNT(p.id) DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$limit]);
    }
}
?>
