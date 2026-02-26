<?php
header('Content-Type: text/html; charset=UTF-8');

// ─── Настройки БД ──────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXX');   // ваш логин, он же имя БД
define('DB_USER', 'uXXXXX');   // ваш логин
define('DB_PASS', 'your_pass'); // ваш пароль

// ─── Допустимые значения ───────────────────────────────────────
$validLanguageIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$validGenders     = ['male', 'female'];

// ─── Хелпер: подсчёт символов без mbstring ────────────────────
function str_char_len($s) {
    if (function_exists('iconv_strlen')) return iconv_strlen($s, 'UTF-8');
    if (function_exists('mb_strlen'))   return mb_strlen($s, 'UTF-8');
    return strlen($s);
}

// ─── Хелперы cookies ──────────────────────────────────────────
function set_error_cookie($name, $message) {
    // expires=0 → живёт до закрытия браузера (сессионная кука)
    setcookie($name, $message, 0, '/');
}
function set_temp_cookie($name, $value) {
    setcookie($name, $value, 0, '/');
}
function set_perm_cookie($name, $value) {
    // 1 год — для заполнения формы по умолчанию при следующем визите
    setcookie($name, $value, time() + 365 * 24 * 3600, '/');
}
function del_cookie($name) {
    setcookie($name, '', 100000, '/');
}

// ══════════════════════════════════════════════════════════════
//  GET — читаем cookies, строим $errors / $values, показываем форму
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $messages = [];

    // Сообщение об успешном сохранении
    if (!empty($_COOKIE['save'])) {
        del_cookie('save');
        $messages['success'] = 'Данные успешно сохранены!';
    }

    // Описание всех полей: ключ ошибки и ключ значения в cookies
    $fieldMap = [
        'fio'       => ['err' => 'err_fio',       'val' => 'val_fio'],
        'phone'     => ['err' => 'err_phone',     'val' => 'val_phone'],
        'email'     => ['err' => 'err_email',     'val' => 'val_email'],
        'birthdate' => ['err' => 'err_birthdate', 'val' => 'val_birthdate'],
        'gender'    => ['err' => 'err_gender',    'val' => 'val_gender'],
        'languages' => ['err' => 'err_languages', 'val' => 'val_languages'],
        'biography' => ['err' => 'err_biography', 'val' => 'val_biography'],
        'agreed'    => ['err' => 'err_agreed',    'val' => null],
    ];

    $errors = [];   // [field => 'текст ошибки'] или [field => '']
    $values = [];   // [field => значение]
    $anyErr = false;

    foreach ($fieldMap as $field => $keys) {
        $errCookie = $keys['err'];
        $valCookie = $keys['val'];

        if (!empty($_COOKIE[$errCookie])) {
            // Есть ошибка — читаем и сразу удаляем (показать один раз)
            $errors[$field] = $_COOKIE[$errCookie];
            del_cookie($errCookie);
            $anyErr = true;

            // Временное значение (при ошибке) — тоже читаем и удаляем
            if ($valCookie) {
                $values[$field] = $_COOKIE[$valCookie] ?? '';
                del_cookie($valCookie);
            }
        } else {
            $errors[$field] = '';
            // Постоянное значение (после успеха) — оставляем, не удаляем
            if ($valCookie) {
                $values[$field] = $_COOKIE[$valCookie] ?? '';
            }
        }
    }

    // languages хранится как JSON-массив
    $values['languages'] = !empty($values['languages'])
        ? (json_decode($values['languages'], true) ?? [])
        : [];

    if ($anyErr) {
        $messages['error_hint'] = 'Исправьте ошибки в форме.';
    }

    include 'form.php';
    exit();
}

// ══════════════════════════════════════════════════════════════
//  POST — валидируем, раскладываем в cookies, redirect → GET
// ══════════════════════════════════════════════════════════════
$post   = $_POST;
$hasErr = false;

// ── 1. ФИО ────────────────────────────────────────────────────
$fio = trim($post['fio'] ?? '');
if ($fio === '') {
    set_error_cookie('err_fio', 'Укажите ФИО.');
    set_temp_cookie('val_fio', '');
    $hasErr = true;
} elseif (!preg_match('/^[\p{L} \-]+$/u', $fio)) {
    set_error_cookie('err_fio', 'ФИО может содержать только буквы, пробелы и дефисы.');
    set_temp_cookie('val_fio', $fio);
    $hasErr = true;
} elseif (str_char_len($fio) > 150) {
    set_error_cookie('err_fio', 'ФИО не должно превышать 150 символов.');
    set_temp_cookie('val_fio', $fio);
    $hasErr = true;
} else {
    set_perm_cookie('val_fio', $fio);
}

// ── 2. Телефон ────────────────────────────────────────────────
$phone = trim($post['phone'] ?? '');
if ($phone === '') {
    set_error_cookie('err_phone', 'Укажите номер телефона.');
    set_temp_cookie('val_phone', '');
    $hasErr = true;
} elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
    set_error_cookie('err_phone', 'Телефон: допустимы цифры, +, (, ), пробел, дефис (7–20 знаков).');
    set_temp_cookie('val_phone', $phone);
    $hasErr = true;
} else {
    set_perm_cookie('val_phone', $phone);
}

// ── 3. E-mail ─────────────────────────────────────────────────
$email = trim($post['email'] ?? '');
if ($email === '') {
    set_error_cookie('err_email', 'Укажите e-mail.');
    set_temp_cookie('val_email', '');
    $hasErr = true;
} elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
    set_error_cookie('err_email', 'E-mail: введите адрес вида name@domain.ru — допустимы латинские буквы, цифры, точка, дефис, _.');
    set_temp_cookie('val_email', $email);
    $hasErr = true;
} elseif (str_char_len($email) > 255) {
    set_error_cookie('err_email', 'E-mail слишком длинный (максимум 255 символов).');
    set_temp_cookie('val_email', $email);
    $hasErr = true;
} else {
    set_perm_cookie('val_email', $email);
}

// ── 4. Дата рождения ─────────────────────────────────────────
$birthdate = trim($post['birthdate'] ?? '');
if ($birthdate === '') {
    set_error_cookie('err_birthdate', 'Укажите дату рождения.');
    set_temp_cookie('val_birthdate', '');
    $hasErr = true;
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    set_error_cookie('err_birthdate', 'Дата рождения: ожидается формат ГГГГ-ММ-ДД, только цифры.');
    set_temp_cookie('val_birthdate', $birthdate);
    $hasErr = true;
} else {
    $d = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) {
        set_error_cookie('err_birthdate', 'Дата рождения: введите существующую дату.');
        set_temp_cookie('val_birthdate', $birthdate);
        $hasErr = true;
    } elseif ($d > new DateTime()) {
        set_error_cookie('err_birthdate', 'Дата рождения не может быть в будущем.');
        set_temp_cookie('val_birthdate', $birthdate);
        $hasErr = true;
    } else {
        set_perm_cookie('val_birthdate', $birthdate);
    }
}

// ── 5. Пол ────────────────────────────────────────────────────
$gender = trim($post['gender'] ?? '');
if (!in_array($gender, $validGenders, true)) {
    set_error_cookie('err_gender', 'Выберите пол: допустимые значения — «Мужской» или «Женский».');
    set_temp_cookie('val_gender', '');
    $hasErr = true;
} else {
    set_perm_cookie('val_gender', $gender);
}

// ── 6. Языки программирования ─────────────────────────────────
$rawLangs = $post['languages'] ?? [];
if (!is_array($rawLangs) || count($rawLangs) === 0) {
    set_error_cookie('err_languages', 'Выберите хотя бы один язык программирования из предложенного списка.');
    set_temp_cookie('val_languages', '[]');
    $hasErr = true;
} else {
    $langs    = [];
    $langsBad = false;
    foreach ($rawLangs as $lid) {
        $lid = (int)$lid;
        if (!in_array($lid, $validLanguageIds, true)) {
            $langsBad = true;
            break;
        }
        $langs[] = $lid;
    }
    if ($langsBad) {
        set_error_cookie('err_languages', 'Языки программирования: выбрано недопустимое значение.');
        set_temp_cookie('val_languages', json_encode($langs));
        $hasErr = true;
    } else {
        $langs = array_unique($langs);
        set_perm_cookie('val_languages', json_encode($langs));
    }
}

// ── 7. Биография ─────────────────────────────────────────────
$biography = trim($post['biography'] ?? '');
if ($biography === '') {
    set_error_cookie('err_biography', 'Заполните биографию.');
    set_temp_cookie('val_biography', '');
    $hasErr = true;
} elseif (str_char_len($biography) > 10000) {
    set_error_cookie('err_biography', 'Биография слишком длинная (максимум 10 000 символов).');
    set_temp_cookie('val_biography', $biography);
    $hasErr = true;
} else {
    set_perm_cookie('val_biography', $biography);
}

// ── 8. Согласие с контрактом ─────────────────────────────────
$agreed = !empty($post['agreed']) && $post['agreed'] === '1';
if (!$agreed) {
    set_error_cookie('err_agreed', 'Необходимо подтвердить ознакомление с контрактом.');
    $hasErr = true;
}

// ── Редирект (с ошибками или без) ────────────────────────────
if ($hasErr) {
    header('Location: index.php');
    exit();
}

// ══════════════════════════════════════════════════════════════
//  Сохранение в БД
// ══════════════════════════════════════════════════════════════
try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_PERSISTENT       => true,
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

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

    $stmtLang = $db->prepare(
        "INSERT INTO application_language (application_id, language_id)
         VALUES (:app_id, :lang_id)"
    );
    foreach ($langs as $langId) {
        $stmtLang->execute([':app_id' => $applicationId, ':lang_id' => $langId]);
    }

} catch (PDOException $e) {
    // Показываем ошибку БД через механизм ошибок формы
    set_error_cookie('err_fio', 'Ошибка базы данных: ' . $e->getMessage());
    header('Location: index.php');
    exit();
}

// Успех
setcookie('save', '1', 0, '/');
header('Location: index.php');
exit();