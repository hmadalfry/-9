<?php
$host = "localhost";
$db = "smart_ac";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// الحالة الحالية
$current = $conn->query("SELECT status, created_at FROM ac_status_log ORDER BY created_at DESC LIMIT 1");
$state = "غير معروف";
$time = "";
if ($current->num_rows > 0) {
    $row = $current->fetch_assoc();
    $state = ($row['status'] == "ON") ? "تشغيل" : "إيقاف";
    $time = $row['created_at'];
}

// السجل
$log = $conn->query("SELECT * FROM ac_status_log ORDER BY created_at DESC LIMIT 100");

// بيانات الرسم
$chart = $conn->query("SELECT created_at, status FROM ac_status_log ORDER BY created_at ASC");
$timestamps = [];
$statuses = [];
while ($row = $chart->fetch_assoc()) {
    $timestamps[] = $row['created_at'];
    $statuses[] = ($row['status'] == "ON") ? 1 : 0;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>نظام التكييف الذكي</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial; background: #f4f4f4; text-align: center; direction: rtl; }
    .status-box {
      margin: 20px auto; padding: 20px; width: 60%;
      background-color: <?= ($state == "تشغيل") ? "#4CAF50" : "#F44336" ?>;
      color: white; border-radius: 10px;
    }
    table { width: 80%; margin: auto; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; }
    th { background-color: #ddd; }
  </style>
</head>
<body>

<h2>نظام التكييف الذكي</h2>
<div class="status-box">
  الحالة الحالية: <strong><?= $state ?></strong><br>
  آخر تحديث: <?= $time ?>
</div>

<h3>سجل التشغيل</h3>
<table>
  <tr>
    <th>رقم</th>
    <th>معرّف</th>
    <th>الحالة</th>
    <th>الوقت</th>
  </tr>
  <?php while ($row = $log->fetch_assoc()): ?>
  <tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['device_id'] ?></td>
    <td><?= ($row['status'] == "ON") ? "تشغيل" : "إيقاف" ?></td>
    <td><?= $row['created_at'] ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<h3>رسم بياني لحالة المكيف</h3>
<canvas id="statusChart" width="800" height="400"></canvas>

<script>
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($timestamps) ?>,
    datasets: [{
      label: 'تشغيل = 1 | إيقاف = 0',
      data: <?= json_encode($statuses) ?>,
      borderColor: 'blue',
      borderWidth: 2,
      fill: false,
      tension: 0.3
    }]
  },
  options: {
    scales: {
      y: {
        min: -0.1,
        max: 1.1,
        ticks: {
          stepSize: 1,
          callback: (value) => value === 1 ? 'تشغيل' : 'إيقاف'
        }
      }
    }
  }
});
</script>

</body>
</html>
<?php $conn->close(); ?>
