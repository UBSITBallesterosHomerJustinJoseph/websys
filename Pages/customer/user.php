<?php
include '../../db_connect.php';

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "farmcart"; // replace with your DB name

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

     // Fetch user + farm profile by user ID
   public function getById($id) {
    $stmt = $this->conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.address,
               f.farm_name, f.farm_location, f.farm_size, f.farming_method,
               f.years_experience, f.certification_details, f.bio, f.is_verified_farmer,
               f.created_at, f.updated_at
        FROM users u
        LEFT JOIN farmer_profiles f ON u.user_id = f.user_id
        WHERE u.user_id = ?
    ");
    if (!$stmt) {
        die("Prepare failed: " . $this->conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
public function updateUser($id, $data) {
    $sql = "UPDATE users SET 
                email = ?, first_name = ?, last_name = ?, phone_number = ?, address = ?, updated_at = NOW()
            WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $this->conn->error);
    }
    $stmt->bind_param(
        "sssssi",
        $data['email'],
        $data['first_name'],
        $data['last_name'],
        $data['phone_number'],
        $data['address'],
        $id
    );
    return $stmt->execute();
}
public function updateFarmProfile($userId, $data) {
    $sql = "UPDATE farmer_profiles SET 
                farm_name = ?, farm_location = ?, farm_size = ?, farming_method = ?, 
                years_experience = ?, certification_details = ?, bio = ?, updated_at = NOW()
            WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $this->conn->error);
    }
    $stmt->bind_param(
        "ssissssi",
        $data['farm_name'],
        $data['farm_location'],
        $data['farm_size'],
        $data['farming_method'],
        $data['years_experience'],
        $data['certification_details'],
        $data['bio'],
        $userId
    );
    return $stmt->execute();
}


    public function update($id, $data) {
        $sql = "UPDATE users SET 
                    email = ?, first_name = ?, last_name = ?, phone_number = ?, address = ?, 
                    store_name = ?, store_description = ?, store_address = ?, store_phone = ?, updated_at = NOW()
                WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssi",
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['phone_number'],
            $data['address'],
            $data['store_name'],
            $data['store_description'],
            $data['store_address'],
            $data['store_phone'],
            $id
        );
        return $stmt->execute();
    }

    

}
