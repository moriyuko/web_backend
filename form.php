<?php
// Список допустимых языков (id совпадает с таблицей language)
$languages = [
    1  => 'Pascal',
    2  => 'C',
    3  => 'C++',
    4  => 'JavaScript',
    5  => 'PHP',
    6  => 'Python',
    7  => 'Java',
    8  => 'Haskell',
    9  => 'Clojure',
    10 => 'Prolog',
    11 => 'Scala',
    12 => 'Go',
];

// Для восстановления значений формы после ошибки (передаются из index.php)
$old = $old ?? [];
function old($key, $default = '') {
    global $old;
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
function oldChecked($key, $value) {
    global $old;
    return isset($old[$key]) && $old[$key] === $value ? 'checked' : '';
}
function oldSelected($key, $value) {
    global $old;
    if (!isset($old[$key])) return '';
    return in_array($value, (array)$old[$key]) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Анкета</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sage:       #7D9B76;
      --sage-light: #B2C9AD;
      --sage-pale:  #EEF3ED;
      --sage-dark:  #4F6B4A;
      --text:       #2E3A2C;
      --muted:      #6B7F69;
      --error:      #C0392B;
      --radius:     8px;
      --shadow:     0 2px 8px rgba(0,0,0,.08);
    }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: var(--sage-pale);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 2rem 1rem;
    }

    .card {
      background: #fff;
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 2.4rem 2.8rem;
      width: 100%;
      max-width: 600px;
    }

    h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--sage-dark);
      margin-bottom: 1.8rem;
      text-align: center;
      letter-spacing: .02em;
    }

    .field {
      margin-bottom: 1.2rem;
      display: flex;
      flex-direction: column;
      gap: .35rem;
    }

    label {
      font-size: .9rem;
      font-weight: 500;
      color: var(--muted);
    }

    label .req { color: var(--sage); margin-left: 2px; }

    input[type="text"],
    input[type="tel"],
    input[type="email"],
    input[type="date"],
    textarea,
    select {
      width: 100%;
      padding: .55rem .8rem;
      border: 1.5px solid var(--sage-light);
      border-radius: var(--radius);
      font-size: .95rem;
      font-family: inherit;
      color: var(--text);
      background: #fff;
      outline: none;
      transition: border-color .18s, box-shadow .18s;
    }

    input:focus,
    textarea:focus,
    select:focus {
      border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(125,155,118,.18);
    }

    textarea { resize: vertical; min-height: 100px; }

    select[multiple] {
      min-height: 160px;
    }

    /* Radio buttons */
    .radio-group {
      display: flex;
      gap: 1.4rem;
      margin-top: .1rem;
    }
    .radio-group label {
      display: flex;
      align-items: center;
      gap: .4rem;
      cursor: pointer;
      color: var(--text);
      font-weight: 400;
    }
    input[type="radio"] {
      accent-color: var(--sage);
      width: 16px; height: 16px;
    }

    /* Checkbox */
    .checkbox-label {
      display: flex;
      align-items: center;
      gap: .55rem;
      cursor: pointer;
      font-size: .92rem;
      color: var(--text);
    }
    input[type="checkbox"] {
      accent-color: var(--sage);
      width: 17px; height: 17px;
      flex-shrink: 0;
    }

    /* Submit button */
    .btn-save {
      display: block;
      width: 100%;
      margin-top: 1.6rem;
      padding: .75rem;
      background: var(--sage);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      letter-spacing: .03em;
      transition: background .18s, transform .14s, box-shadow .14s;
      box-shadow: 0 2px 6px rgba(79,107,74,.25);
    }
    .btn-save:hover {
      background: var(--sage-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(79,107,74,.30);
    }
    .btn-save:active {
      transform: translateY(0);
      box-shadow: 0 2px 6px rgba(79,107,74,.20);
    }

    /* Errors */
    .errors {
      background: #fdf0ef;
      border: 1.5px solid #e8b4b0;
      border-radius: var(--radius);
      padding: .8rem 1rem;
      margin-bottom: 1.4rem;
      font-size: .9rem;
      color: var(--error);
    }
    .errors p { margin-bottom: .3rem; }
    .errors p:last-child { margin-bottom: 0; }

    /* Success */
    .success {
      background: #f0f6ef;
      border: 1.5px solid var(--sage-light);
      border-radius: var(--radius);
      padding: .9rem 1.1rem;
      margin-bottom: 1.4rem;
      font-size: .95rem;
      color: var(--sage-dark);
      font-weight: 500;
    }

    small { font-size: .78rem; color: var(--muted); }
  </style>
</head>
<body>
<div class="card">
  <h1>Анкета участника</h1>

  <?php if (!empty($successMessage)): ?>
    <div class="success"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="errors">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form action="" method="POST">

    <div class="field">
      <label for="fio">ФИО <span class="req">*</span></label>
      <input type="text" id="fio" name="fio"
             placeholder="Иванов Иван Иванович"
             maxlength="150"
             value="<?= old('fio') ?>">
    </div>

    <div class="field">
      <label for="phone">Телефон <span class="req">*</span></label>
      <input type="tel" id="phone" name="phone"
             placeholder="+7 (999) 123-45-67"
             value="<?= old('phone') ?>">
    </div>

    <div class="field">
      <label for="email">E-mail <span class="req">*</span></label>
      <input type="email" id="email" name="email"
             placeholder="example@mail.ru"
             value="<?= old('email') ?>">
    </div>

    <div class="field">
      <label for="birthdate">Дата рождения <span class="req">*</span></label>
      <input type="date" id="birthdate" name="birthdate"
             value="<?= old('birthdate') ?>">
    </div>

    <div class="field">
      <label>Пол <span class="req">*</span></label>
      <div class="radio-group">
        <label>
          <input type="radio" name="gender" value="male"   <?= oldChecked('gender','male') ?>>
          Мужской
        </label>
        <label>
          <input type="radio" name="gender" value="female" <?= oldChecked('gender','female') ?>>
          Женский
        </label>
      </div>
    </div>

    <div class="field">
      <label for="languages">Любимый язык программирования <span class="req">*</span></label>
      <select id="languages" name="languages[]" multiple="multiple">
        <?php foreach ($languages as $id => $name): ?>
          <option value="<?= $id ?>" <?= oldSelected('languages', $id) ?>>
            <?= htmlspecialchars($name) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small>Удерживайте Ctrl (⌘ на Mac) для выбора нескольких</small>
    </div>

    <div class="field">
      <label for="biography">Биография <span class="req">*</span></label>
      <textarea id="biography" name="biography"
                placeholder="Расскажите о себе..."><?= old('biography') ?></textarea>
    </div>

    <div class="field">
      <label class="checkbox-label">
        <input type="checkbox" name="agreed" value="1"
               <?= isset($old['agreed']) ? 'checked' : '' ?>>
        С контрактом ознакомлен(а) <span class="req">*</span>
      </label>
    </div>

    <button type="submit" class="btn-save">Сохранить</button>
  </form>
</div>
</body>
</html>
