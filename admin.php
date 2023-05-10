<?php


if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    md5($_SERVER['PHP_AUTH_PW']) != md5('123')) {
  header('HTTP/1.1 401 Unanthorized');
  header('WWW-Authenticate: Basic realm="My site"');
  print('<h1>401 Требуется авторизация</h1>');
  exit();
}



// Подключение к базе данных
$servername = "localhost";
$username = "u52979";
$password = "2087021";
$dbname = "u52979";

try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (isset($_POST['delete_btn'])) {
    $userIdToDelete = $_POST['delete_id'];
 
    try {
        // Удаляем записи из таблицы user_abilities
        $stmt = $db->prepare("DELETE FROM user_abilities WHERE user_id = ?");
        $stmt->execute([$userIdToDelete]);
 
        // Удаляем пользователя из таблицы users
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userIdToDelete]);
 
        // Обновляем страницу для отображения изменений
        header("Location: admin.php");
        exit;
    } catch (PDOException $e) {
        echo "Ошибка при удалении пользователя: " . $e->getMessage();
    }
}
// Получение данных пользователей
$sql = "SELECT * FROM users";
$result = $db->query($sql);
$users = $result->fetchAll(PDO::FETCH_ASSOC);

// Получение всех возможных способностей
$abilities_sql = "SELECT * FROM abilities";
$abilities_result = $db->query($abilities_sql);
$abilities = $abilities_result->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Обновление данных пользователя
    foreach ($users as $user) {
        $id = $user['id'];
        $name = $_POST['name'][$id];
        $email = $_POST['email'][$id];
        $birth_year = $_POST['birth_year'][$id];
        $gender = $_POST['gender'][$id];
        $limbs = $_POST['limbs'][$id];
        $bio = $_POST['bio'][$id];
        $contract = 1;

        $update_sql = "UPDATE users SET name = ?, email = ?, birth_year = ?, gender = ?, limbs = ?, bio = ?, contract = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute([$name, $email, $birth_year, $gender, $limbs, $bio, $contract, $id]);

        // Обновление связей с таблицей user_abilities
        $delete_abilities_sql = "DELETE FROM user_abilities WHERE user_id = ?";
        $delete_abilities_stmt = $db->prepare($delete_abilities_sql);
        $delete_abilities_stmt->execute([$id]);

        foreach ($abilities as $ability) {
            if (isset($_POST['abilities'][$id]) && in_array($ability['id'], $_POST['abilities'][$id])) {
                $insert_abilities_sql = "INSERT INTO user_abilities (user_id, ability_id) VALUES (?, ?)";
                $insert_abilities_stmt = $db->prepare($insert_abilities_sql);
                $insert_abilities_stmt->execute([$id, $ability['id']]);
            }
        }
    }
    header("Location: admin.php");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Page</title>
<style>
table {
border-collapse: collapse;
width: 100%;
}
th, td {
border: 1px solid black;
padding: 8px;
text-align: left;
}
th {
background-color: #f2f2f2;
}
input[type="checkbox"] {
transform: scale(1.5);
}
.delete-button {
        background-color: red;
        color: white;
        border: none;
        padding: 5px 10px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        cursor: pointer;
    }
</style>
</head>
<body>
    <h1>Admin</h1>
<form action="admin.php" method="post">
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Birth year</th>
            <th>Gender</th>
            <th>Num of limbs</th>
            <th>Bio</th>
            <th>Abilities</th>
            <th>REMOVE</th>
        </tr>
        <?php foreach ($users as $user) : ?>
            <?php
            $user_id = $user['id'];
            $user_abilities_sql = "SELECT ability_id FROM user_abilities WHERE user_id = ?";
            $user_abilities_stmt = $db->prepare($user_abilities_sql);
            $user_abilities_stmt->execute([$user_id]);
            $user_abilities = $user_abilities_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            ?>
            <tr>
                <td><input type="text" name="name[<?= $user_id ?>]" value="<?= htmlspecialchars($user['name']) ?>"></td>
                <td><input type="text" name="email[<?= $user_id ?>]" value="<?= htmlspecialchars($user['email']) ?>"></td>
                <td><input type="number" name="birth_year[<?= $user_id ?>]" value="<?= $user['birth_year'] ?>" min="1900" max="2023"></td>
                <td>
                    <select name="gender[<?= $user_id ?>]">
                        <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>female</option>
                    </select>
                </td>
                <td><input type="number" name="limbs[<?= $user_id ?>]" value="<?= $user['limbs'] ?>" min="1" max="4"></td>
                <td><textarea name="bio[<?= $user_id ?>]"><?= htmlspecialchars($user['bio']) ?></textarea></td>
            
                <td>
                    <?php foreach ($abilities as $ability) : ?>
                        <div>
                            <input type="checkbox" name="abilities[<?= $user_id ?>][]" value="<?= $ability['id'] ?>" <?= in_array($ability['id'], $user_abilities) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($ability['ability_name']) ?>
                        </div>
                    <?php endforeach; ?>
                </td>
                <td>
                <form method="POST">
                  <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                  <button type="submit" name="delete_btn" class="delete-button">Remove</button>
                </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </table>
    <button type="submit">Save</button>
</form>

<h2>Stats</h2>
<table border="1">
    <tr>
        <th>Ability</th>
        <th>Num of users</th>
    </tr>
    <?php
    $sql = "SELECT a.ability_name, COUNT(ua.user_id) AS user_count
            FROM abilities a
            JOIN user_abilities ua ON a.id = ua.ability_id
            GROUP BY a.id";
    $stmt = $db->query($sql);
    $abilities_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    foreach ($abilities_stats as $ability_stat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ability_stat['ability_name']) . "</td>";
        echo "<td>" . htmlspecialchars($ability_stat['user_count']) . "</td>";
        echo "</tr>";
    }
    ?>
</table>

</body>
</html>
