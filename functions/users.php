<?php
function getUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kullanıcılar alınırken hata oluştu: " . $e->getMessage());
    }
}

function getUserById($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kullanıcı alınırken hata oluştu: " . $e->getMessage());
    }
}

function addUser($pdo, $username, $name, $email, $password, $role = 'user') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Bu kullanıcı adı zaten kullanılıyor.");
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $name, $email, $hashed_password, $role]);
    } catch (Exception $e) {
        throw new Exception("Kullanıcı eklenirken hata oluştu: " . $e->getMessage());
    }
}

function updateUser($pdo, $user_id, $name, $email, $password = null, $role = 'user') {
    try {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hashed_password, $role, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $user_id]);
        }
    } catch (Exception $e) {
        throw new Exception("Kullanıcı güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function deleteUser($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        throw new Exception("Kullanıcı silinirken hata oluştu: " . $e->getMessage());
    }
}
?>