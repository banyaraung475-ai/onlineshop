
<?php
class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserById($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$id]);
    }

    public function updateUser($user_id, $fullname, $email,$address, $phone) {
        return $this->db->update('users', [
            'full_name' => $fullname,
            'email' => $email,
            'address' => $address,
            'phone' => $phone
        ], 'user_id = ?', [$user_id]);
    }

    public function changePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        return $this->db->update('users', ['password_hash' => $hashed_password], 'user_id = ?', [$user_id]);
    }
}