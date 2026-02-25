<?php
header('Content-Type: text/html; charset=UTF-8');

// ─── Настройки БД ──────────────────────────────────────────────
// Замените значения на ваши реальные данные с учебного сервера
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXX');   // ваш логин, он же имя БД
define('DB_USER', 'uXXXXX');   // ваш логин
define('DB_PASS', 'your_pass'); // ваш пароль

// ─── Допустимые значения ───────────────────────────────────────
$validLanguageIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$validGenders     = ['male', 'female'];

// ─── GET-запрос: просто показываем форму ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $successMessage = !empty($_GET['saved']) ? 'Данные успешно сохранены!' : '';
    $errors = [];
    $old    = [];
    include 'form.php';
    exit();
}

// ─── POST-запрос: валидация ────────────────────────────────────
$errors = [];
$post   = $_POST;

// 1. ФИО
$fio = trim($post['fio'] ?? '');
if ($fio === '') {
    $errors[] = 'Укажите ФИО.';
} elseif (!preg_match('/^[\p{L} \-]+$/u', $fio)) {
    $errors[] = 'ФИО должно содержать только буквы, пробелы и дефисы.';
} elseif (mb_strlen($fio) > 150) {
    $errors[] = 'ФИО не должно превышать 150 символов.';
}

// 2. Телефон
$phone = trim($post['phone'] ?? '');
if ($phone === '') {
    $errors[] = 'Укажите телефон.';
} elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
    $errors[] = 'Введите корректный номер телефона.';
}

// 3. E-mail
$email = trim($post['email'] ?? '');
if ($email === '') {
    $errors[] = 'Укажите e-mail.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный e-mail адрес.';
} elseif (mb_strlen($email) > 255) {
    $errors[] = 'E-mail слишком длинный.';
}

// 4. Дата рождения
$birthdate = trim($post['birthdate'] ?? '');
if ($birthdate === '') {
    $errors[] = 'Укажите дату рождения.';
} else {
    $d = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) {
        $errors[] = 'Введите корректную дату рождения.';
    } else {
        $today = new DateTime();
        if ($d > $today) {
            $errors[] = 'Дата рождения не может быть в будущем.';
        }
    }
}

// 5. Пол
$gender = trim($post['gender'] ?? '');
if (!in_array($gender, $validGenders, true)) {
    $errors[] = 'Выберите корректный пол.';
}

// 6. Языки программирования
$rawLangs = $post['languages'] ?? [];
if (!is_array($rawLangs) || count($rawLangs) === 0) {
    $errors[] = 'Выберите хотя бы один язык программирования.';
} else {
    $langs = [];
    foreach ($rawLangs as $lid) {
        $lid = (int)$lid;
        if (!in_array($lid, $validLanguageIds, true)) {
            $errors[] = 'Выбран недопустимый язык программирования.';
            break;
        }
        $langs[] = $lid;
    }
    $langs = array_unique($langs);
}

// 7. Биография
$biography = trim($post['biography'] ?? '');
if ($biography === '') {
    $errors[] = 'Заполните биографию.';
} elseif (mb_strlen($biography) > 10000) {
    $errors[] = 'Биография слишком длинная (максимум 10 000 символов).';
}

// 8. Согласие с контрактом
$agreed = !empty($post['agreed']) && $post['agreed'] === '1';
if (!$agreed) {
    $errors[] = 'Подтвердите ознакомление с контрактом.';
}

// ─── Если есть ошибки — возвращаем форму ──────────────────────
if (!empty($errors)) {
    $old = $post; // передаём обратно в форму для восстановления значений
    include 'form.php';
    exit();
}

// ─── Сохранение в БД ──────────────────────────────────────────
try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_PERSISTENT  => true,
            PDO::ATTR_ERRMODE     => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Вставляем основную запись
    $stmt = $db->prepare(
        "INSERT INTO application (name, phone, email, birthdate, gender, biography, agreed)
         VALUES (:name, :phone, :email, :birthdate, :gender, :biography, :agreed)"
    );
    $stmt->execute([
        ':name'      => $fio,
        ':phone'     => $phone,
        ':email'     => $email,
        ':birthdate' => $birthdate,
        ':gender'    => $gender,
        ':biography' => $biography,
        ':agreed'    => 1,
    ]);

    $applicationId = (int)$db->lastInsertId();

    // Вставляем связи с языками программирования
    $stmtLang = $db->prepare(
        "INSERT INTO application_language (application_id, language_id) VALUES (:app_id, :lang_id)"
    );
    foreach ($langs as $langId) {
        $stmtLang->execute([
            ':app_id'  => $applicationId,
            ':lang_id' => $langId,
        ]);
    }

} catch (PDOException $e) {
    // В продакшене не стоит показывать текст ошибки пользователю
    $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
    $old = $post;
    include 'form.php';
    exit();
}

// ─── Редирект после успешного сохранения ──────────────────────
header('Location: ?saved=1');
exit();
