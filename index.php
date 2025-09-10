<?php
session_start();
require_once 'config/db.php';
require_once 'config/api.php';
require_once 'functions.php';
$page_title = "Ana Sayfa";

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => '']
];

ob_start();

// Hata ve başarı mesajları
$error = '';
$success = '';

// Örnek dashboard verileri
try {
    $low_stock_items = $pdo->query("
        SELECT COUNT(*) as count 
        FROM inventory 
        WHERE stock_quantity < min_stock_level
    ")->fetch(PDO::FETCH_ASSOC);

    $overdue_invoices = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sales_invoices 
        WHERE status = 'pending' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
        AND due_date >= CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Veriler yüklenemedi: " . $e->getMessage();
}

// Şehir ve koordinat eşleştirmesi
$city = isset($_POST['city']) ? trim($_POST['city']) : 'Istanbul';
$coordinates = [
    'Canakkale' => ['lat' => 41.0082, 'lon' => 28.9784, 'name' => 'Çanakkale'],
    'Kabatepe' => ['lat' => 40.1980561, 'lon' => 26.2693921, 'name' => 'Kabatepe'],
    'Alcitepe' => ['lat' => 40.094272, 'lon' => 26.2239741, 'name' => 'Alçıtepe']
];
$lat = $coordinates[$city]['lat'] ?? 41.0082;
$lon = $coordinates[$city]['lon'] ?? 28.9784;
$city_name = $coordinates[$city]['name'] ?? $city;

// Hava durumu verisi çekme
$weather_data = null;
if (isset($_SESSION['weather_data']) && $_SESSION['weather_data']['timestamp'] > time() - CACHE_DURATION && $_SESSION['weather_data']['city'] === $city) {
    $weather_data = $_SESSION['weather_data']['data'];
} else {
    unset($_SESSION['weather_data']); // Eski önbelleği temizle
    $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=" . OPENWEATHERMAP_API_KEY . "&units=metric&lang=tr";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Geçici çözüm, üretimde dikkatli kullan
    $response = curl_exec($ch);
    if ($response === false) {
        $error = "Hava durumu verisi alınamadı: cURL hatası - " . curl_error($ch);
    } else {
        $weather_data = json_decode($response, true);
        if ($weather_data && isset($weather_data['cod']) && $weather_data['cod'] == "200") {
            $_SESSION['weather_data'] = [
                'data' => $weather_data,
                'timestamp' => time(),
                'city' => $city
            ];
        } else {
            $error = "Hava durumu verisi alınamadı: " . ($weather_data['message'] ?? 'Bilinmeyen hata');
        }
    }
    curl_close($ch);
}

// Hava durumu için ikon eşleştirmesi
$weather_icons = [
    'Clear' => 'fa-sun',
    'Clouds' => 'fa-cloud',
    'Rain' => 'fa-cloud-rain',
    'Drizzle' => 'fa-cloud-showers-heavy',
    'Thunderstorm' => 'fa-bolt',
    'Snow' => 'fa-snowflake',
    'Mist' => 'fa-smog'
];

// Günlük tahminleri filtreleme (öğlen saatlerini seç)
$daily_forecasts = [];
if ($weather_data) {
    foreach ($weather_data['list'] as $forecast) {
        if (strpos($forecast['dt_txt'], '12:00:00') !== false) {
            $daily_forecasts[] = $forecast;
        }
    }
}
$recent_notifications = cacheQuery($pdo, "
    SELECT id, type, message, created_at, is_read, priority 
    FROM notifications 
    WHERE user_id = ? AND is_archived = 0 
    ORDER BY created_at DESC 
    LIMIT 5
", [$_SESSION['user_id']], 'notifications_' . $_SESSION['user_id']);
?>

<div class="container-fluid">
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Şehir Seçimi Formu -->
        <div class="col-12 mb-4">
            <form method="POST" class="input-group w-auto">
                <select name="city" class="form-select" onchange="this.form.submit()">
                    <option value="Canakkale" <?php echo $city === 'Canakkale' ? 'selected' : ''; ?>>Çanakkale</option>
                    <option value="Kabatepe" <?php echo $city === 'Kabatepe' ? 'selected' : ''; ?>>Kabatepe</option>
                    <option value="Alcitepe" <?php echo $city === 'Alcitepe' ? 'selected' : ''; ?>>Alcitepe</option>
                </select>
            </form>
        </div>
        <!-- Düşük Stok Widget -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Düşük Stok</h5>
                    <p class="card-text"><?php echo $low_stock_items['count']; ?> ürün stokta düşük.</p>
                    <a href="inventory.php" class="btn btn-primary">Detayları Gör</a>
                </div>
            </div>
        </div>
        <!-- Vadesi Yaklaşan Faturalar Widget -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Vadesi Yaklaşan Faturalar</h5>
                    <p class="card-text"><?php echo $overdue_invoices['count']; ?> fatura vadesi yaklaşıyor.</p>
                    <a href="sales.php" class="btn btn-primary">Detayları Gör</a>
                </div>
            </div>
        </div>
        <!-- Hava Durumu Widget -->
        <div class="col-md-4 mb-4">
            <div class="weather-card card">
                <div class="top" style="background: url('<?php
                    echo ($weather_data && $weather_data['list'][0]['weather'][0]['main'] === 'Rain')
                        ? 'http://img.freepik.com/free-vector/girl-with-umbrella_1325-5.jpg?size=338&ext=jpg'
                        : 'https://s-media-cache-ak0.pinimg.com/564x/cf/1e/c4/cf1ec4b0c96e59657a46867a91bb0d1e.jpg';
                    ?>') no-repeat center center; background-size: cover;">
                    <div class="wrapper">
                        <h1 class="heading"><?php echo $weather_data ? htmlspecialchars(ucfirst($weather_data['list'][0]['weather'][0]['description'])) : 'Veri yok'; ?></h1>
                        <h3 class="location"><?php echo htmlspecialchars($city_name); ?></h3>
                        <p class="temp">
                            <span class="temp-value"><?php echo $weather_data ? round($weather_data['list'][0]['main']['temp']) : '-'; ?></span>
                            <span class="deg">°</span>
                            <span class="temp-type">C</span>
                        </p>
                    </div>
                </div>
                <div class="bottom">
                    <div class="wrapper">
                        <ul class="forecast">
                            <?php if ($weather_data && !empty($daily_forecasts)): ?>
                                <?php for ($i = 0; $i < min(2, count($daily_forecasts)); $i++): ?>
                                    <li class="<?php echo $i === 0 ? 'active' : ''; ?>">
                                        <span class="date"><?php echo date('d.m.Y', strtotime($daily_forecasts[$i]['dt_txt'])); ?></span>
                                        <span class="condition">
                                            <i class="fas <?php echo $weather_icons[$daily_forecasts[$i]['weather'][0]['main']] ?? 'fa-question'; ?>"></i>
                                            <span class="temp"><?php echo round($daily_forecasts[$i]['main']['temp']); ?><span class="deg">°</span><span class="temp-type">C</span></span>
                                        </span>
                                    </li>
                                <?php endfor; ?>
                            <?php else: ?>
                                <li><span class="date">Veri yok</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.weather-card {
    height: 400px;
    background: #fff;
    box-shadow: 0 1px 38px rgba(0, 0, 0, 0.15), 0 5px 12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}
.weather-card .top {
    position: relative;
    height: 250px;
    width: 100%;
    overflow: hidden;
    text-align: center;
}
.weather-card .top .wrapper {
    padding: 20px;
    position: relative;
    z-index: 1;
}
.weather-card .top .heading {
    margin-top: 10px;
    font-size: 24px;
    font-weight: 500;
    color: #fff;
}
.weather-card .top .location {
    margin-top: 10px;
    font-size: 18px;
    font-weight: 400;
    color: #fff;
}
.weather-card .top .temp {
    margin-top: 10px;
}
.weather-card .top .temp .temp-value {
    font-size: 48px;
    font-weight: 600;
    color: #fff;
}
.weather-card .top .temp .deg {
    font-size: 24px;
    font-weight: 600;
    color: #fff;
    vertical-align: top;
    margin-top: 5px;
}
.weather-card .top .temp .temp-type {
    font-size: 24px;
    color: #fff;
}
.weather-card .top:after {
    content: "";
    height: 100%;
    width: 100%;
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    background: rgba(0, 0, 0, 0.5);
}
.weather-card .bottom {
    padding: 0 20px;
    background: #fff;
}
.weather-card .bottom .wrapper .forecast {
    margin: 0;
    padding: 10px 0;
    max-height: 150px;
}
.weather-card .bottom .wrapper .forecast li {
    display: block;
    font-size: 16px;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.8);
    line-height: 1.5em;
    margin-bottom: 10px;
}
.weather-card .bottom .wrapper .forecast li .date {
    display: inline-block;
}
.weather-card .bottom .wrapper .forecast li .condition {
    display: inline-block;
    float: right;
}
.weather-card .bottom .wrapper .forecast li .condition .temp {
    font-size: 16px;
}
.weather-card .bottom .wrapper .forecast li.active {
    font-weight: 600;
}
body.dark-mode .weather-card .bottom {
    background: #343a40;
    color: #f8f9fa;
}
body.dark-mode .weather-card .bottom .wrapper .forecast li {
    color: #f8f9fa;
}
</style>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>