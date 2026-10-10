<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../includes/player-profile-data.php';
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
if (!Auth::check()) { header('Location: /pikvero/public/login.php'); exit; }
$user = Auth::user(); $id = (int)Auth::id(); $extra = playerProfileData($id); $error = '';
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf'] = bin2hex(random_bytes(24));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 try {
  if (!hash_equals($_SESSION['profile_csrf'], $_POST['csrf'] ?? '')) throw new RuntimeException('Please reload and try again.');
  $name = trim($_POST['full_name'] ?? ''); if ($name === '') throw new RuntimeException('Enter your full name.');
  $parts = preg_split('/\s+/', $name, 2); $phone = trim($_POST['phone'] ?? '');
  $dob = trim($_POST['date_of_birth'] ?? '');
  if ($dob && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || strtotime($dob) > time())) throw new RuntimeException('Enter a valid date of birth.');
  $image = $extra['image_url'] ?? null;
  if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
   $file = $_FILES['photo']; if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5*1024*1024) throw new RuntimeException('Choose an image smaller than 5 MB.');
   $type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if (!isset($extensions[$type]) || !getimagesize($file['tmp_name'])) throw new RuntimeException('Choose a JPG, PNG or WebP image.');
   $dir = __DIR__ . '/../../uploads/player-photos'; if (!is_dir($dir)) mkdir($dir,0755,true);
   $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$type];
   if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) throw new RuntimeException('Unable to save your image.');
   $image = '/pikvero/uploads/player-photos/' . $filename;
  }
  $db = Connection::getInstance();
  $db->execute('UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?', [$parts[0],$parts[1] ?? '',$phone,$id], 'sssi');
  $db->execute('INSERT INTO player_profiles (user_id,date_of_birth,gender,city,playing_level,image_url) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE date_of_birth=VALUES(date_of_birth),gender=VALUES(gender),city=VALUES(city),playing_level=VALUES(playing_level),image_url=VALUES(image_url)', [$id,$dob ?: null,substr($_POST['gender'] ?? '',0,30),substr($_POST['city'] ?? '',0,100),substr($_POST['playing_level'] ?? '',0,30),$image], 'isssss');
  $user['first_name']=$parts[0];$user['last_name']=$parts[1] ?? '';$user['phone']=$phone; Auth::login($user);
  $_SESSION['player_profile_updated'] = true;
  header('Location: /pikvero/public/customer/profile.php'); exit;
 } catch (Throwable $e) { $error = $e->getMessage(); }
}
function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Profile — Pikvero</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__.'/../../assets/css/streetside-theme.css') ?>">
<link rel="stylesheet" href="/pikvero/assets/css/player-profile.css?v=<?= filemtime(__DIR__.'/../../assets/css/player-profile.css') ?>"><link rel="manifest" href="/pikvero/public/manifest.php">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head>
<body class="customer-portal profile-edit"><header class="edit-profile-header"><a href="/pikvero/public/customer/profile.php" aria-label="Back to profile"><i class="bi bi-chevron-left"></i></a><strong>Edit Profile</strong><span></span></header>
<main class="profile-edit-main"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= esc($_SESSION['profile_csrf']) ?>">
<div class="edit-photo"><label for="profile-photo" class="profile-avatar-wrap"><div class="profile-avatar"><?php if (!empty($extra['image_url'])): ?><img id="photo-preview" src="<?= esc($extra['image_url']) ?>" alt="Your profile photo"><?php else: ?><i class="bi bi-person"></i><img id="photo-preview" alt="Selected profile photo" hidden><?php endif; ?></div><span class="profile-camera"><i class="bi bi-camera-fill"></i></span></label><input id="profile-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><small>Tap the camera to change your photo</small></div>
<?php if ($error): ?><p role="alert" class="profile-error"><?= esc($error) ?></p><?php endif; ?>
<h2>Personal information</h2>
<label>Full name<input name="full_name" required maxlength="150" autocomplete="name" value="<?= esc($_POST['full_name'] ?? trim($user['first_name'].' '.$user['last_name'])) ?>"></label>
<label>Email address<input type="email" value="<?= esc($user['email']) ?>" readonly></label>
<label>Phone number<input name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= esc($_POST['phone'] ?? $user['phone'] ?? '') ?>"></label>
<label>Date of birth<input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= esc($_POST['date_of_birth'] ?? $extra['date_of_birth'] ?? '') ?>"></label>
<label>Gender<select name="gender"><?php foreach([''=>'Prefer not to say','Male'=>'Male','Female'=>'Female','Other'=>'Other'] as $v=>$text): ?><option value="<?= esc($v) ?>" <?= ($_POST['gender'] ?? $extra['gender'] ?? '')===$v?'selected':'' ?>><?= esc($text) ?></option><?php endforeach; ?></select></label>
<h2>Location &amp; play</h2><label>City<input name="city" maxlength="100" autocomplete="address-level2" value="<?= esc($_POST['city'] ?? $extra['city'] ?? '') ?>"></label>
<label>Playing level<select name="playing_level"><?php foreach(['','Beginner','Intermediate','Advanced'] as $v): ?><option <?= ($_POST['playing_level'] ?? $extra['playing_level'] ?? '')===$v?'selected':'' ?>><?= esc($v ?: 'Choose your level') ?></option><?php endforeach; ?></select></label>
<button class="profile-save" type="submit"><i class="bi bi-check-circle"></i> Save changes</button></form></main>
<script>document.getElementById('profile-photo').addEventListener('change',function(){const file=this.files[0];if(!file)return;const img=document.getElementById('photo-preview');if(img.dataset.preview)URL.revokeObjectURL(img.dataset.preview);img.dataset.preview=URL.createObjectURL(file);img.src=img.dataset.preview;img.hidden=false;const icon=img.previousElementSibling;if(icon)icon.hidden=true;});</script></body></html>
