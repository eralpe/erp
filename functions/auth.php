<?php
function login($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Kullanıcı bulunamadı: $username");
        }
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Şifre hatalı.");
        }
        
        return $user;
    } catch (Exception $e) {
        throw new Exception("Giriş hatası: " . $e->getMessage());
    }
}
// Rol kontrol fonksiyonu
function checkRoleAccess($allowed_roles) {
    // Oturum yoksa veya kullanıcı ID'si tanımlı değilse login'e yönlendir
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Rol tanımı kontrolü
    if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
        die("Rol tanımı bulunamadı.");
    }

    // İzin verilen rolleri kontrol et
    $user_role = $_SESSION['role'];
    if (!in_array($user_role, $allowed_roles)) {
        die("Bu sayfaya erişim izniniz yok.");
    }
}

// Kullanıcının rolünü oturuma yükle (mevcut yapıya uyum)
function loadUserRoles($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();
        if ($role) {
            $_SESSION['role'] = $role;
        } else {
            $_SESSION['role'] = ''; // Rol yoksa boş bırak
        }
    } catch (PDOException $e) {
        error_log("Rol yükleme hatası: " . $e->getMessage());
        $_SESSION['role'] = '';
    }
}

// Oturumu sonlandırmak için
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>